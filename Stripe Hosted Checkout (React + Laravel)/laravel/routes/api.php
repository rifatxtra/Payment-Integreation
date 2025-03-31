use App\Http\Controllers\StripeController;

Route::post('/create-checkout-session', [StripeController::class, 'createCheckoutSession']);
