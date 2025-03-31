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
