import React, { useState, useEffect } from 'react';
import { useNavigate } from 'react-router-dom';
import { Helmet } from 'react-helmet-async';
import { motion } from 'framer-motion';
import { useAuth } from '@/contexts/AuthContext';
import { useCart } from '@/contexts/CartContext';
import { toast } from '@/components/ui/use-toast';
import { api } from '@/lib/api';
import ContactInfo from '@/components/checkout/ContactInfo';
import ShippingInfo from '@/components/checkout/ShippingInfo';
import OrderSummary from '@/components/checkout/OrderSummary';

const Checkout = () => {
  const { cartItems, getCartTotal, clearCart } = useCart();
  const { user } = useAuth();
  const navigate = useNavigate();
  const [isProcessing, setIsProcessing] = useState(false);
  const [formData, setFormData] = useState({
    email: '',
    firstName: '',
    lastName: '',
    address: '',
    city: '',
    state: '',
    zipCode: '',
    phone: ''
  });

  useEffect(() => {
    if (user) {
      setFormData(prev => ({
        ...prev,
        email: user.email || '',
        firstName: user.name?.split(' ')[0] || '',
        lastName: user.name?.split(' ').slice(1).join(' ') || '',
        address: user.address || '',
        phone: user.phone || ''
      }));
    }
  }, [user]);

  useEffect(() => {
    if (cartItems.length === 0 && !isProcessing) {
      navigate('/store');
    }
  }, [cartItems, navigate, isProcessing]);

  const handleInputChange = (e) => {
    const { name, value } = e.target;
    setFormData(prev => ({ ...prev, [name]: value }));
  };

  const handleSelectChange = (name, value) => {
    setFormData(prev => ({ ...prev, [name]: value }));
  };

  const handleSubmit = async (e) => {
    e.preventDefault();

    if (!user) {
      toast({
        title: "Please log in",
        description: "You need to be logged in to place an order.",
        variant: "destructive",
      });
      navigate('/login');
      return;
    }

    setIsProcessing(true);
    await handleRazorpayPayment();
  };

  const loadRazorpaySDK = () => {
    return new Promise((resolve, reject) => {
      // Check if already loaded
      if (window.Razorpay) {
        resolve(true);
        return;
      }

      // Create script element
      const script = document.createElement('script');
      script.src = 'https://checkout.razorpay.com/v1/checkout.js';
      script.async = true;
      script.onload = () => resolve(true);
      script.onerror = () => reject(new Error('Failed to load Razorpay SDK'));
      document.body.appendChild(script);
    });
  };

  const handleRazorpayPayment = async () => {
    try {
      // Load Razorpay SDK dynamically
      await loadRazorpaySDK();
      
      const orderData = {
        items: cartItems,
        total_amount: getCartTotal(),
        shipping_address: {
          name: `${formData.firstName} ${formData.lastName}`,
          address: formData.address,
          city: formData.city,
          state: formData.state,
          pincode: formData.zipCode,
          phone: formData.phone
        },
        billing_address: {
          name: `${formData.firstName} ${formData.lastName}`,
          address: formData.address,
          city: formData.city,
          state: formData.state,
          pincode: formData.zipCode,
          phone: formData.phone
        },
        payment_method: 'razorpay'
      };

      const orderResponse = await api.createOrder(orderData);

      if (!orderResponse.success) {
        throw new Error(orderResponse.errors?.[0] || 'Failed to create order');
      }

      const paymentOrderResponse = await api.createPaymentOrder({
        order_id: orderResponse.order_id
      });

      if (!paymentOrderResponse.success) {
        throw new Error(paymentOrderResponse.errors?.[0] || 'Failed to create payment order');
      }

      const options = {
        key: paymentOrderResponse.razorpay_key_id,
        amount: paymentOrderResponse.amount * 100,
        currency: paymentOrderResponse.currency,
        name: 'Sreemeditec',
        description: 'Medical Equipment Purchase',
        image: '/logo.png',
        order_id: paymentOrderResponse.razorpay_order_id,
        handler: async (response) => {
          await processPaymentSuccess(response, orderResponse.order_id);
        },
        prefill: {
          name: `${formData.firstName} ${formData.lastName}`,
          email: formData.email,
          contact: formData.phone,
        },
        config: {
          display: {
            blocks: {
              banks: {
                name: 'All payment methods',
                instruments: [
                  {
                    method: 'card'
                  },
                  {
                    method: 'netbanking'
                  },
                  {
                    method: 'upi'
                  },
                  {
                    method: 'wallet'
                  }
                ]
              }
            },
            sequence: ['block.banks'],
            preferences: {
              show_default_blocks: true
            }
          }
        },
        theme: {
          color: '#0d9488',
        },
        modal: {
          ondismiss: function() {
            setIsProcessing(false);
          }
        }
      };

      if (!window.Razorpay) {
        throw new Error('Razorpay SDK not loaded. Please check your internet connection and try again.');
      }

      const rzp = new window.Razorpay(options);
      
      rzp.on('payment.failed', function (response){
        toast({
          title: "Payment failed",
          description: response.error.description,
          variant: "destructive",
        });
        setIsProcessing(false);
      });
      
      // Add error handler for connection failures
      rzp.on('ready', function(){
        console.log('Razorpay checkout ready');
      });

      try {
        rzp.open();
      } catch (rzpError) {
        console.error('Razorpay open error:', rzpError);
        
        // If Razorpay fails due to iframe restrictions, show helpful message
        toast({
          title: "Payment Gateway Issue",
          description: "Unable to open payment popup. This may be due to browser restrictions. Please open this page in a new tab to complete payment.",
          variant: "destructive",
          duration: 8000,
        });
        
        // Provide option to open in new window
        const currentUrl = window.location.href;
        const newWindow = window.open(currentUrl, '_blank');
        if (!newWindow) {
          toast({
            title: "Pop-up Blocked",
            description: "Please allow pop-ups for this site or copy the URL and open it in a new tab.",
            variant: "destructive",
            duration: 10000,
          });
        }
        
        setIsProcessing(false);
      }

    } catch (error) {
      console.error('Razorpay error:', error);
      
      let errorMessage = error.message || "Could not initiate payment.";
      
      // Check if error is related to connection issues
      if (error.message && error.message.includes('refused to connect')) {
        errorMessage = "Payment gateway connection blocked. Please open this page in a new browser tab (not in the iframe) to complete your purchase.";
      }
      
      toast({
        title: "Payment Error",
        description: errorMessage,
        variant: "destructive",
        duration: 8000,
      });
      setIsProcessing(false);
    }
  };

  const processPaymentSuccess = async (razorpayResponse, orderId) => {
    try {
      const verificationResponse = await api.verifyPayment({
        razorpay_order_id: razorpayResponse.razorpay_order_id,
        razorpay_payment_id: razorpayResponse.razorpay_payment_id,
        razorpay_signature: razorpayResponse.razorpay_signature,
        auto_create_shipment: true
      });

      if (!verificationResponse.success) {
        throw new Error('Payment verification failed');
      }

      clearCart();
      
      const successMessage = verificationResponse.shipment 
        ? `Your order #${orderId} has been confirmed and shipment has been created! AWB: ${verificationResponse.shipment.awb_number}`
        : `Your order #${orderId} has been confirmed.`;
      
      toast({
        title: "Payment successful!",
        description: successMessage,
      });
      
      navigate(`/order-confirmation/${orderId}`);
    } catch (error) {
      console.error('Payment verification error:', error);
      toast({
        title: "Payment Processing Error",
        description: error.message || "There was an error verifying your payment. Please contact support.",
        variant: "destructive",
      });
      setIsProcessing(false);
    }
  };


  return (
    <>
      <Helmet>
        <title>Checkout - Complete Your Order | Sreemeditec</title>
        <meta name="description" content="Complete your medical equipment purchase with secure checkout and fast delivery options." />
      </Helmet>

      <div className="min-h-screen bg-gray-50 py-12">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <motion.div
            initial={{ opacity: 0, y: 20 }}
            animate={{ opacity: 1, y: 0 }}
            transition={{ duration: 0.6 }}
            className="space-y-6 sm:space-y-8"
          >
            <div className="text-center">
              <h1 className="text-2xl sm:text-3xl font-bold text-gray-900">Checkout</h1>
              <p className="text-sm sm:text-base text-gray-600 mt-1">Complete your order securely</p>
            </div>

            <form onSubmit={handleSubmit}>
              <div className="grid lg:grid-cols-2 gap-6 md:gap-8 items-start">
                <div className="space-y-5 sm:space-y-6">
                  <ContactInfo formData={formData} handleInputChange={handleInputChange} />
                  <ShippingInfo formData={formData} handleInputChange={handleInputChange} handleSelectChange={handleSelectChange} />
                </div>

                <div className="lg:sticky lg:top-24">
                  <OrderSummary cartItems={cartItems} getCartTotal={getCartTotal} isProcessing={isProcessing} />
                </div>
              </div>
            </form>
          </motion.div>
        </div>
      </div>
    </>
  );
};

export default Checkout;
