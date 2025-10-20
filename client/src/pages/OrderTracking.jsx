import React, { useState, useEffect } from 'react';
import { useParams, Link } from 'react-router-dom';
import { Helmet } from 'react-helmet-async';
import { motion } from 'framer-motion';
import { ArrowLeft, Package, Truck, CheckCircle, Clock, MapPin, Phone, Mail } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { toast } from '@/components/ui/use-toast';
import { api } from '@/lib/api';
import LiveTracking from '@/components/tracking/LiveTracking';

const OrderTracking = () => {
  const { orderId } = useParams();
  const [order, setOrder] = useState(null);
  const [trackingStatus, setTrackingStatus] = useState('confirmed');

  useEffect(() => {
    const loadOrder = async () => {
      if (orderId) {
        try {
          const response = await api.getOrder(orderId);
          if (response.success) {
            setOrder(response.order);
          }
        } catch (error) {
          console.error('Failed to load order:', error);
        }
      }
    };

    loadOrder();
  }, [orderId]);

  useEffect(() => {
    // Simulate live tracking updates
    if (order && order.status !== 'delivered') {
      const interval = setInterval(() => {
        const statuses = ['confirmed', 'processing', 'shipped', 'out_for_delivery', 'delivered'];
        const currentIndex = statuses.indexOf(trackingStatus);

        if (currentIndex < statuses.length - 1 && Math.random() > 0.7) {
          const nextStatus = statuses[currentIndex + 1];
          setTrackingStatus(nextStatus);

          // Update localStorage
          const orders = JSON.parse(localStorage.getItem('sreemeditec_orders') || '[]');
          const orderIndex = orders.findIndex(o => o.id === orderId);
          if (orderIndex !== -1) {
            orders[orderIndex].status = nextStatus;
            localStorage.setItem('sreemeditec_orders', JSON.stringify(orders));
            setOrder(orders[orderIndex]);
          }
        }
      }, 30000); // Update every 30 seconds

      return () => clearInterval(interval);
    }
  }, [trackingStatus, order, orderId]);

  const getTrackingSteps = () => {
    const steps = [
      { id: 'confirmed', label: 'Order Confirmed', icon: CheckCircle },
      { id: 'processing', label: 'Processing', icon: Package },
      { id: 'shipped', label: 'Shipped', icon: Truck },
      { id: 'out_for_delivery', label: 'Out for Delivery', icon: Truck },
      { id: 'delivered', label: 'Delivered', icon: CheckCircle }
    ];

    const statusIndex = steps.findIndex(step => step.id === trackingStatus);

    return steps.map((step, index) => ({
      ...step,
      completed: index <= statusIndex,
      active: index === statusIndex
    }));
  };

  const getStatusMessage = () => {
    switch (trackingStatus) {
      case 'confirmed':
        return 'Your order has been confirmed and is being prepared.';
      case 'processing':
        return 'Your order is being processed and packaged.';
      case 'shipped':
        return 'Your order has been shipped and is on its way.';
      case 'out_for_delivery':
        return 'Your order is out for delivery and will arrive soon.';
      case 'delivered':
        return 'Your order has been delivered successfully.';
      default:
        return 'Tracking information is being updated.';
    }
  };

  if (!order) {
    return (
      <div className="min-h-screen flex items-center justify-center">
        <div className="text-center">
          <h1 className="text-2xl font-bold text-gray-900 mb-4">Order not found</h1>
          <Link to="/order-history">
            <Button className="btn-primary">View All Orders</Button>
          </Link>
        </div>
      </div>
    );
  }

  const trackingSteps = getTrackingSteps();

  return (
    <>
      <Helmet>
        <title>Track Order #{order.id} - Sreemeditec</title>
        <meta name="description" content={`Track your Sreemeditec order #${order.id} and get real-time delivery updates.`} />
      </Helmet>

      <div className="min-h-screen bg-gray-50 py-12">
        <div className="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
          <motion.div
            initial={{ opacity: 0, y: 20 }}
            animate={{ opacity: 1, y: 0 }}
            transition={{ duration: 0.6 }}
            className="space-y-8"
          >
            {/* Header */}
            <div className="flex items-center justify-between">
              <div>
                <h1 className="text-3xl font-bold text-gray-900">Track Your Order</h1>
                <p className="text-gray-600">Order #{order.id}</p>
              </div>
              <Link to="/order-history">
                <Button variant="outline">
                  <ArrowLeft className="w-4 h-4 mr-2" />
                  Back to Orders
                </Button>
              </Link>
            </div>

            {/* Current Status */}
            <Card>
              <CardHeader>
                <CardTitle className="flex items-center space-x-2">
                  <Package className="w-5 h-5" />
                  <span>Current Status</span>
                </CardTitle>
              </CardHeader>
              <CardContent>
                <div className="flex items-center space-x-4 mb-4">
                  <Badge className={`${
                    trackingStatus === 'delivered' ? 'bg-green-100 text-green-800' :
                    trackingStatus === 'out_for_delivery' ? 'bg-blue-100 text-blue-800' :
                    trackingStatus === 'shipped' ? 'bg-purple-100 text-purple-800' :
                    'bg-yellow-100 text-yellow-800'
                  }`}>
                    {trackingStatus.replace('_', ' ').replace(/\b\w/g, l => l.toUpperCase())}
                  </Badge>
                  {(order.trackingNumber || order.shipment?.awbNumber) && (
                    <span className="text-sm text-gray-600">
                      AWB: <span className="font-mono font-medium">{order.trackingNumber || order.shipment?.awbNumber}</span>
                    </span>
                  )}
                  {order.shipment?.courierName && (
                    <span className="text-sm text-gray-600">
                      via {order.shipment.courierName}
                    </span>
                  )}
                </div>
                <p className="text-gray-700">{getStatusMessage()}</p>
                {order.estimatedDelivery && trackingStatus !== 'delivered' && (
                  <p className="text-sm text-gray-600 mt-2">
                    Estimated delivery: {new Date(order.estimatedDelivery).toLocaleDateString()}
                  </p>
                )}
                {order.shipment?.status && (
                  <p className="text-sm text-gray-600 mt-2">
                    Shipment Status: <span className="font-medium capitalize">{order.shipment.status}</span>
                  </p>
                )}
              </CardContent>
            </Card>

            {/* Live Tracking */}
            <LiveTracking order={order} />

            {/* Delivery Information */}
            <div className="grid md:grid-cols-2 gap-6">
              <Card>
                <CardHeader>
                  <CardTitle className="flex items-center space-x-2">
                    <MapPin className="w-5 h-5" />
                    <span>Delivery Address</span>
                  </CardTitle>
                </CardHeader>
                <CardContent>
                  <div className="space-y-2">
                    <p className="font-medium">{order.customerName}</p>
                    <p className="text-gray-600">{order.address}</p>
                    <div className="flex items-center space-x-2 text-gray-600">
                      <Phone className="w-4 h-4" />
                      <span>{order.phone}</span>
                    </div>
                  </div>
                </CardContent>
              </Card>

              <Card>
                <CardHeader>
                  <CardTitle>Order Summary</CardTitle>
                </CardHeader>
                <CardContent>
                  <div className="space-y-2">
                    <div className="flex justify-between">
                      <span className="text-gray-600">Items:</span>
                      <span>{order.items?.length || 0}</span>
                    </div>
                    <div className="flex justify-between">
                      <span className="text-gray-600">Total:</span>
                      <span className="font-medium">₹{parseFloat(order.total || order.totalAmount || 0).toFixed(2)}</span>
                    </div>
                    <div className="flex justify-between">
                      <span className="text-gray-600">Payment:</span>
                      <span className="capitalize">
                        {['captured', 'completed', 'paid', 'success', 'succeeded'].includes(order.payment?.status) || 
                         ['captured', 'paid', 'completed', 'success', 'succeeded'].includes(order.paymentStatus) ? (
                          <Badge className="bg-green-100 text-green-800">Paid</Badge>
                        ) : order.paymentMethod === 'cod' || order.payment?.paymentMethod === 'cod' ? (
                          'Cash on Delivery'
                        ) : (
                          <Badge className="bg-yellow-100 text-yellow-800">
                            {(order.payment?.status || order.paymentStatus || 'Pending').replace('_', ' ')}
                          </Badge>
                        )}
                      </span>
                    </div>
                    {order.payment?.razorpayPaymentId && (
                      <div className="text-xs text-gray-500 mt-2">
                        Payment ID: <span className="font-mono">{order.payment.razorpayPaymentId}</span>
                      </div>
                    )}
                  </div>
                  <div className="mt-4">
                    <Link to={`/order-details/${order.id}`}>
                      <Button variant="outline" className="w-full">
                        View Full Details
                      </Button>
                    </Link>
                  </div>
                </CardContent>
              </Card>
            </div>

            {/* Contact Support */}
            <Card>
              <CardHeader>
                <CardTitle>Need Help?</CardTitle>
              </CardHeader>
              <CardContent>
                <p className="text-gray-600 mb-4">
                  If you have any questions about your order or delivery, our customer support team is here to help.
                </p>
                <div className="flex flex-col sm:flex-row space-y-2 sm:space-y-0 sm:space-x-4">
                  <Button variant="outline">Contact Support</Button>
                  <Button variant="outline">Report an Issue</Button>
                </div>
              </CardContent>
            </Card>
          </motion.div>
        </div>
      </div>
    </>
  );
};

export default OrderTracking;