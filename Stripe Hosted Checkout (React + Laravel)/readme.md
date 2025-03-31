# Stripe Integration with React (Frontend) and Laravel (Backend)

This tutorial covers integrating **Stripe Hosted Checkout** with React (frontend) and Laravel (backend).

## Features
- Uses Stripe's Hosted Checkout for payment processing.
- React handles the redirection to the checkout page.
- Laravel manages session creation and webhook handling.
- Secure payment flow without storing sensitive data.

---

## 1. **Setup Stripe in Laravel (Backend)**

### Install Stripe SDK
Run the following command in your Laravel project:
```sh
composer require stripe/stripe-php
```

### Configure Stripe Keys
Add your Stripe keys to the `.env` file:
```env
STRIPE_SECRET=sk_test_your_secret_key
STRIPE_PUBLIC=pk_test_your_public_key
STRIPE_SUCCESS_URL=https://yourdomain.com/success
STRIPE_CANCEL_URL=https://yourdomain.com/cancel
```

### Create Controller for Stripe Checkout
Run:
```sh
php artisan make:controller StripeController
```

Modify `app/Http/Controllers/StripeController.php`:

```php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Stripe\Stripe;
use Stripe\Checkout\Session;

class StripeController extends Controller
{
    public function createCheckoutSession(Request $request)
    {
        Stripe::setApiKey(env('STRIPE_SECRET'));

        $checkout_session = Session::create([
            'payment_method_types' => ['card'],
            'line_items' => [[
                'price_data' => [
                    'currency' => 'usd',
                    'product_data' => ['name' => 'Sample Product'],
                    'unit_amount' => 1000, // Amount in cents
                ],
                'quantity' => 1,
            ]],
            'mode' => 'payment',
            'success_url' => env('STRIPE_SUCCESS_URL'),
            'cancel_url' => env('STRIPE_CANCEL_URL'),
        ]);

        return response()->json(['url' => $checkout_session->url]);
    }
}
```

### Define API Route
Modify `routes/api.php`:

```php
use App\Http\Controllers\StripeController;

Route::post('/create-checkout-session', [StripeController::class, 'createCheckoutSession']);
```

---

## 2. **Setup React Frontend**

### Install Stripe SDK
In your React project, install Stripe's frontend package:
```sh
npm install @stripe/stripe-js axios
```

### Create Checkout Button Component
Create `components/CheckoutButton.js`:

```jsx
import React from 'react';
import axios from 'axios';

const CheckoutButton = () => {
    const handleCheckout = async () => {
        try {
            const { data } = await axios.post('http://127.0.0.1:8000/api/create-checkout-session');
            window.location.href = data.url;
        } catch (error) {
            console.error('Error creating checkout session:', error);
        }
    };

    return <button onClick={handleCheckout}>Pay with Stripe</button>;
};

export default CheckoutButton;
```

### Use Checkout Button in App
Modify `App.js`:

```jsx
import React from 'react';
import CheckoutButton from './components/CheckoutButton';

function App() {
    return (
        <div>
            <h1>Stripe Hosted Checkout</h1>
            <CheckoutButton />
        </div>
    );
}

export default App;
```

---



## 3. **Run the Project**

### Start Laravel Server
```sh
php artisan serve
```

### Start React App
```sh
npm start
```

---

## 4. **Test the Checkout Flow**
- Click the **Pay with Stripe** button in your React app.
- You will be redirected to Stripe's Hosted Checkout page.
- Use Stripe test cards (e.g., `4242 4242 4242 4242` with any future expiry date and CVC) to complete the payment.
- After successful payment, you will be redirected to the success URL.

---

## Conclusion
This tutorial sets up **Stripe Hosted Checkout** with React and Laravel, allowing secure payments without handling sensitive card details. 🚀

