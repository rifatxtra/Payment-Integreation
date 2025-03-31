namespace App\Http\Controllers;

use App\Models\Deposit;
use Illuminate\Http\Request;
use Stripe\Stripe;
use Stripe\Webhook;
use Stripe\Exception\SignatureVerificationException;

class StripeWebhookController extends Controller
{
    public function handleWebhook(Request $request)
    {
        // Set your Stripe secret key
        Stripe::setApiKey(env('STRIPE_SECRET'));

        // Retrieve the payload and signature
        $sig_header = $request->header('Stripe-Signature');
        $payload = $request->getContent();
        $endpoint_secret = env('STRIPE_WEBHOOK_SECRET');

        try {
            // Verify the webhook signature
            $event = Webhook::constructEvent($payload, $sig_header, $endpoint_secret);
        } catch (SignatureVerificationException $e) {
            return response()->json(['error' => 'Invalid Signature'], 400);
        }

        // Handle the checkout session completion event
        if ($event->type === 'checkout.session.completed') {
            $session = $event->data->object;
            $session_id = $session->id;

            // Find the deposit and mark it as completed
            $deposit = Deposit::where('session_id', $session_id)->first();
            if ($deposit) {
                $deposit->status = 'Completed';
                $deposit->save();
            }
        }

        return response()->json(['status' => 'success']);
    }
}
