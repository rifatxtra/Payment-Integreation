use App\Http\Controllers\StripeController;

Route::post('api/create-checkout-session', [StripeController::class, 'createCheckoutSession']);

use App\Http\Controllers\StripeWebhookController;

Route::post('stripe/webhook', [StripeWebhookController::class, 'handleWebhook']);
