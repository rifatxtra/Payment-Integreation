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
