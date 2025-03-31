# Stripe Integration with Laravel and React

This project provides a complete integration of **Stripe Checkout** in a web application with a **Laravel** backend and a **React** frontend. It allows users to make payments using Stripe's secure and seamless checkout experience. The following features are implemented:

- **Stripe Checkout Session**: The backend (Laravel) creates a Stripe checkout session where the user can make a payment for a specified product. The session is generated and the user is redirected to Stripe's secure payment page.
- **Payment Success and Failure Handling**: After a successful payment, the user is redirected to a success page, while they are redirected to a cancellation page if the payment fails.
- **Webhooks for Payment Updates**: Stripe webhooks are set up to listen for events such as `checkout.session.completed`, allowing the backend to process the successful payment, update the payment status, and manage any related data (e.g., user balance, order status).
- **Frontend and Backend Communication**: The frontend React application makes API requests to the Laravel backend to initiate the payment process and handle the Stripe checkout session.

This guide will help you set up and configure everything, from creating the Stripe session on the backend to handling Stripe's webhooks and integrating the payment flow in your React application.


## Prerequisites

- **Stripe account** (Sign up at [Stripe](https://stripe.com))
- **Laravel** installed on your backend
- **React** frontend with Axios for HTTP requests

---

## 1. Frontend Setup (React)

### **Install Axios**

In your React project, install **axios** to make API requests to the backend:

```bash
npm install axios
```

### **Create the PaymentPage Component**

Create a `PaymentPage.js` component to initiate the payment process.

```javascript
import React from "react";
import axios from "axios";

const PaymentPage = () => {
  const handleCheckout = async () => {
    try {
      // Call backend to create a Stripe Checkout session
      const response = await axios.post("http://localhost:8000/api/create-checkout-session", {
        product_name: "Test Product", // Example product
        amount: 5000, // Amount in cents (e.g., £50)
      });

      // Redirect to Stripe Checkout
      window.location.href = response.data.url;
    } catch (error) {
      console.error("Error creating checkout session:", error);
    }
  };

  return (
    <div>
      <h2>Checkout</h2>
      <button onClick={handleCheckout}>Proceed to Checkout</button>
    </div>
  );
};

export default PaymentPage;
```

### **Add Stripe Public Key to `.env`**

In your React project’s `.env` file, add the following line for the **Stripe public key**:

```bash
REACT_APP_STRIPE_PUBLIC_KEY=your_stripe_public_key
```

---

## 2. Backend Setup (Laravel)

### **Install Stripe PHP SDK**

Install the **Stripe PHP SDK** using **Composer**:

```bash
composer require stripe/stripe-php
```

### **Create the StripeController**

Create a new `StripeController` to handle creating a Stripe Checkout session.

**File**: `app/Http/Controllers/StripeController.php`

```php
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
```

### **Define Routes**

In your `routes/api.php` file, define the route for creating the Stripe checkout session:

```php
use App\Http\Controllers\StripeController;

Route::post('api/create-checkout-session', [StripeController::class, 'createCheckoutSession']);
```

### **Environment Variables**

In your `.env` file, add the following keys for **Stripe** configuration:

```bash
STRIPE_SECRET=your_stripe_secret_key
STRIPE_PUBLIC_KEY=your_stripe_public_key
STRIPE_SUCCESS_URL=http://yourdomain.com/payment-success
STRIPE_CANCEL_URL=http://yourdomain.com/payment-cancel
```

Replace `your_stripe_secret_key` and `your_stripe_public_key` with your actual keys from your Stripe account.

---

## 3. Webhook Handling (Laravel)

### **Create StripeWebhookController**

Create a controller to handle the webhook events from Stripe, such as when a payment session is completed.

**File**: `app/Http/Controllers/StripeWebhookController.php`

```php
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
```

### **Define Webhook Route**

In your `routes/web.php`, define the route for handling Stripe webhooks:

```php
use App\Http\Controllers\StripeWebhookController;

Route::post('stripe/webhook', [StripeWebhookController::class, 'handleWebhook']);
```

### **Environment Variables for Webhook Secret**

In your `.env` file, add the following line for the **Stripe webhook secret**:

```bash
STRIPE_WEBHOOK_SECRET=your_webhook_secret
```

---

## 4. Webhook Setup in Stripe Dashboard

1. **Go to the Stripe Dashboard**.
2. Navigate to **Developers > Webhooks**.
3. **Add a webhook endpoint** with the URL: `http://yourdomain.com/stripe/webhook`.
4. Select the **event type** `checkout.session.completed`.

---

## 5. `.env` Configuration

Ensure the following environment variables are set in your `.env` file:

```bash
STRIPE_SECRET=your_stripe_secret_key
STRIPE_PUBLIC_KEY=your_stripe_public_key
STRIPE_SUCCESS_URL=http://yourdomain.com/payment-success
STRIPE_CANCEL_URL=http://yourdomain.com/payment-cancel
STRIPE_WEBHOOK_SECRET=your_webhook_secret
```

---

## 6. Final Notes

- **Testing**: Use **Stripe’s test mode** to test your integration with fake credit card details (e.g., `4242 4242 4242 4242`).
- **Security**: Keep your **Stripe secret key** and **webhook secret** secure.
- **Frontend Redirection**: After successful payment, users will be redirected to the `STRIPE_SUCCESS_URL` you set in the backend.

---

### **That's it! Your Stripe integration with Laravel and React is complete.**

