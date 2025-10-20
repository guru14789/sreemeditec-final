
import React, { useState } from 'react';
import { CreditCard, Wallet, DollarSign } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { toast } from '@/components/ui/use-toast';

const PaymentGateway = ({ amount, onSuccess, onError, paymentMethod }) => {
  const [processing, setProcessing] = useState(false);
  const [cardDetails, setCardDetails] = useState({
    cardNumber: '',
    expiryDate: '',
    cvv: '',
    holderName: ''
  });

  const handleCardInputChange = (e) => {
    const { name, value } = e.target;
    setCardDetails(prev => ({
      ...prev,
      [name]: value
    }));
  };

  const initiateRazorpayPayment = async (orderId, totalAmount) => {
    return new Promise(async (resolve, reject) => {
      try {
        const response = await fetch('/api/payment/create-order', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'Authorization': `Bearer ${localStorage.getItem('idToken')}`
          },
          body: JSON.stringify({
            order_id: orderId
          })
        });

        const data = await response.json();

        if (!data.success) {
          reject(new Error(data.errors?.[0] || 'Failed to create Razorpay order'));
          return;
        }

        const options = {
          key: data.razorpay_key_id,
          amount: data.amount * 100,
          currency: data.currency,
          name: 'Sree Meditec',
          description: 'Medical Equipment Purchase',
          order_id: data.razorpay_order_id,
          handler: async function (response) {
            try {
              const verifyResponse = await fetch('/api/payment/verify', {
                method: 'POST',
                headers: {
                  'Content-Type': 'application/json',
                  'Authorization': `Bearer ${localStorage.getItem('idToken')}`
                },
                body: JSON.stringify({
                  razorpay_order_id: response.razorpay_order_id,
                  razorpay_payment_id: response.razorpay_payment_id,
                  razorpay_signature: response.razorpay_signature,
                  auto_create_shipment: true
                })
              });

              const verifyData = await verifyResponse.json();

              if (verifyData.success) {
                resolve({
                  razorpay_payment_id: response.razorpay_payment_id,
                  razorpay_order_id: response.razorpay_order_id,
                  razorpay_signature: response.razorpay_signature,
                  shipment: verifyData.shipment
                });
              } else {
                reject(new Error('Payment verification failed'));
              }
            } catch (error) {
              reject(error);
            }
          },
          prefill: {
            name: '',
            email: '',
            contact: ''
          },
          theme: {
            color: '#0d9488'
          }
        };

        const rzp = new window.Razorpay(options);
        rzp.on('payment.failed', function (response) {
          reject(new Error(response.error.description || 'Payment failed'));
        });
        rzp.open();
      } catch (error) {
        reject(error);
      }
    });
  };

  const simulateStripePayment = () => {
    return new Promise((resolve, reject) => {
      setTimeout(() => {
        // Simulate 90% success rate
        if (Math.random() < 0.9) {
          const paymentId = 'pi_' + Math.random().toString(36).substr(2, 12);
          resolve({
            id: paymentId,
            status: 'succeeded',
            amount: amount * 100 // Stripe uses cents
          });
        } else {
          reject(new Error('Payment failed'));
        }
      }, 3000);
    });
  };

  const processPayment = async () => {
    if (paymentMethod === 'cod') {
      onSuccess({ paymentMethod: 'cod' });
      return;
    }

    if (paymentMethod === 'card' && (!cardDetails.cardNumber || !cardDetails.expiryDate || !cardDetails.cvv)) {
      toast({
        title: "Invalid card details",
        description: "Please fill in all card details.",
        variant: "destructive",
      });
      return;
    }

    setProcessing(true);

    try {
      let paymentResult;
      
      if (paymentMethod === 'razorpay') {
        const orderId = sessionStorage.getItem('current_order_id');
        if (!orderId) {
          throw new Error('No order ID found. Please try again.');
        }
        
        paymentResult = await initiateRazorpayPayment(orderId, amount);
        onSuccess({
          paymentMethod: 'razorpay',
          paymentId: paymentResult.razorpay_payment_id,
          orderId: paymentResult.razorpay_order_id,
          shipment: paymentResult.shipment
        });
        
        toast({
          title: "Payment successful!",
          description: paymentResult.shipment 
            ? "Your payment has been processed and shipment has been created!" 
            : "Your payment has been processed successfully.",
        });
      } else if (paymentMethod === 'stripe' || paymentMethod === 'card') {
        paymentResult = await simulateStripePayment();
        onSuccess({
          paymentMethod: 'stripe',
          paymentId: paymentResult.id,
          status: paymentResult.status
        });
        
        toast({
          title: "Payment successful!",
          description: "Your payment has been processed successfully.",
        });
      }

    } catch (error) {
      onError(error);
      toast({
        title: "Payment failed",
        description: error.message || "Payment processing failed. Please try again.",
        variant: "destructive",
      });
    } finally {
      setProcessing(false);
    }
  };

  const renderPaymentForm = () => {
    if (paymentMethod === 'cod') {
      return (
        <div className="text-center py-8">
          <DollarSign className="w-16 h-16 mx-auto text-green-600 mb-4" />
          <h3 className="text-lg font-semibold mb-2">Cash on Delivery</h3>
          <p className="text-gray-600 mb-4">You will pay when your order is delivered.</p>
          <Button
            onClick={processPayment}
            disabled={processing}
            className="btn-primary"
          >
            {processing ? 'Processing...' : 'Confirm Order'}
          </Button>
        </div>
      );
    }

    return (
      <div className="space-y-4">
        <div className="grid grid-cols-1 gap-4">
          <div>
            <Label htmlFor="holderName">Cardholder Name</Label>
            <Input
              id="holderName"
              name="holderName"
              value={cardDetails.holderName}
              onChange={handleCardInputChange}
              placeholder="John Doe"
              required
            />
          </div>

          <div>
            <Label htmlFor="cardNumber">Card Number</Label>
            <Input
              id="cardNumber"
              name="cardNumber"
              value={cardDetails.cardNumber}
              onChange={handleCardInputChange}
              placeholder="1234 5678 9012 3456"
              maxLength={19}
              required
            />
          </div>

          <div className="grid grid-cols-2 gap-4">
            <div>
              <Label htmlFor="expiryDate">Expiry Date</Label>
              <Input
                id="expiryDate"
                name="expiryDate"
                value={cardDetails.expiryDate}
                onChange={handleCardInputChange}
                placeholder="MM/YY"
                maxLength={5}
                required
              />
            </div>
            <div>
              <Label htmlFor="cvv">CVV</Label>
              <Input
                id="cvv"
                name="cvv"
                value={cardDetails.cvv}
                onChange={handleCardInputChange}
                placeholder="123"
                maxLength={4}
                required
              />
            </div>
          </div>
        </div>

        <Button
          onClick={processPayment}
          disabled={processing}
          className="w-full btn-primary"
        >
          {processing ? (
            <>
              <div className="loading-spinner mr-2"></div>
              Processing Payment...
            </>
          ) : (
            <>
              <CreditCard className="w-4 h-4 mr-2" />
              Pay ₹{amount.toFixed(2)}
            </>
          )}
        </Button>
      </div>
    );
  };

  return (
    <Card>
      <CardHeader>
        <CardTitle className="flex items-center">
          {paymentMethod === 'cod' ? (
            <DollarSign className="w-5 h-5 mr-2" />
          ) : (
            <CreditCard className="w-5 h-5 mr-2" />
          )}
          Payment Details
        </CardTitle>
      </CardHeader>
      <CardContent>
        {renderPaymentForm()}
        
        <div className="mt-6 p-4 bg-gray-50 rounded-lg">
          <div className="flex justify-between items-center text-sm">
            <span>Subtotal:</span>
            <span>₹{(amount / 1.18).toFixed(2)}</span>
          </div>
          <div className="flex justify-between items-center text-sm">
            <span>GST (18%):</span>
            <span>₹{(amount - (amount / 1.18)).toFixed(2)}</span>
          </div>
          <div className="border-t pt-2 mt-2">
            <div className="flex justify-between items-center font-semibold">
              <span>Total:</span>
              <span>₹{amount.toFixed(2)}</span>
            </div>
          </div>
        </div>
      </CardContent>
    </Card>
  );
};

export default PaymentGateway;
