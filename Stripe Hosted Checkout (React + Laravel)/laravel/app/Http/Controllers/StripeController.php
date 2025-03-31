namespace App\Http\Controllers;

use App\Models\Deposit;
use Illuminate\Http\Request;
use Stripe\Stripe;
use Stripe\Checkout\Session;

class StripeController extends Controller
{
    public function createCheckoutSession(Request $request)
    {
        try {
            // Set your secret key for Stripe
            Stripe::setApiKey(env('STRIPE_SECRET'));

            // Create the Stripe Checkout session
            $checkoutSession = Session::create([
                'payment_method_types' => ['card'],
                'line_items' => [[
                    'price_data' => [
                        'currency' => 'gbp',
                        'product_data' => [
                            'name' => $request->input('product_name'),
                        ],
                        'unit_amount' => $request->input('amount'),
                    ],
                    'quantity' => 1,
                ]],
                'mode' => 'payment',
                'success_url' => env('STRIPE_SUCCESS_URL') . "?session_id={CHECKOUT_SESSION_ID}",
                'cancel_url' => env('STRIPE_CANCEL_URL'),
            ]);

            // Save deposit details in the database
            $deposit = Deposit::create([
                'amount' => $request->amount / 100, // Convert from cents to GBP
                'session_id' => $checkoutSession->id,
                'status' => 'Pending',
            ]);

            return response()->json(['url' => $checkoutSession->url, 'session_id' => $checkoutSession->id]);

        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
