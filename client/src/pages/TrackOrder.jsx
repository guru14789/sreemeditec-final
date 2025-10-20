import React, { useState, useEffect } from 'react';
import { useParams, Link } from 'react-router-dom';
import { Helmet } from 'react-helmet-async';
import { motion } from 'framer-motion';
import { Package, Truck, CheckCircle, Clock, MapPin, ChevronRight, ArrowLeft, RefreshCw } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { toast } from '@/components/ui/use-toast';
import { api } from '@/lib/api';

const TrackOrder = () => {
  const { orderId } = useParams();
  const [order, setOrder] = useState(null);
  const [shipment, setShipment] = useState(null);
  const [trackingData, setTrackingData] = useState(null);
  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);

  useEffect(() => {
    loadOrderDetails();
  }, [orderId]);

  const loadOrderDetails = async () => {
    try {
      setLoading(true);
      const orderResponse = await api.getOrderById(orderId);
      
      if (orderResponse.success) {
        setOrder(orderResponse.order);
        
        // Load shipment data if available
        if (orderResponse.order.shipment) {
          setShipment(orderResponse.order.shipment);
          await loadTrackingData(orderResponse.order.shipment.awbNumber);
        }
      }
    } catch (error) {
      console.error('Failed to load order:', error);
      toast({
        title: "Error",
        description: "Failed to load order details.",
        variant: "destructive",
      });
    } finally {
      setLoading(false);
    }
  };

  const loadTrackingData = async (awbNumber) => {
    try {
      const trackingResponse = await api.trackShipment(awbNumber);
      if (trackingResponse.success) {
        setTrackingData(trackingResponse.tracking_data);
      }
    } catch (error) {
      console.error('Failed to load tracking:', error);
    }
  };

  const handleRefreshTracking = async () => {
    if (!shipment?.awbNumber) return;
    
    try {
      setRefreshing(true);
      await loadTrackingData(shipment.awbNumber);
      toast({
        title: "Tracking Updated",
        description: "Latest tracking information has been loaded.",
      });
    } catch (error) {
      toast({
        title: "Error",
        description: "Failed to refresh tracking data.",
        variant: "destructive",
      });
    } finally {
      setRefreshing(false);
    }
  };

  const getStatusIcon = (status) => {
    switch (status?.toLowerCase()) {
      case 'pending':
      case 'created':
        return <Clock className="w-5 h-5" />;
      case 'shipped':
      case 'in_transit':
      case 'in transit':
        return <Truck className="w-5 h-5" />;
      case 'delivered':
        return <CheckCircle className="w-5 h-5" />;
      default:
        return <Package className="w-5 h-5" />;
    }
  };

  const getStatusColor = (status) => {
    switch (status?.toLowerCase()) {
      case 'pending':
      case 'created':
        return 'bg-yellow-100 text-yellow-800';
      case 'shipped':
      case 'in_transit':
      case 'in transit':
        return 'bg-blue-100 text-blue-800';
      case 'delivered':
        return 'bg-green-100 text-green-800';
      default:
        return 'bg-gray-100 text-gray-800';
    }
  };

  if (loading) {
    return (
      <div className="min-h-screen flex items-center justify-center">
        <div className="loading-spinner"></div>
      </div>
    );
  }

  if (!order) {
    return (
      <div className="min-h-screen flex items-center justify-center">
        <div className="text-center">
          <Package className="w-16 h-16 mx-auto text-gray-400 mb-4" />
          <h2 className="text-2xl font-bold text-gray-900 mb-2">Order Not Found</h2>
          <p className="text-gray-600 mb-6">The order you're looking for doesn't exist.</p>
          <Link to="/order-history">
            <Button className="btn-primary">Back to Order History</Button>
          </Link>
        </div>
      </div>
    );
  }

  return (
    <>
      <Helmet>
        <title>Track Order #{orderId} - Sreemeditec</title>
        <meta name="description" content={`Track your medical equipment order #${orderId} with live DTDC tracking.`} />
      </Helmet>

      <div className="min-h-screen bg-gray-50 py-8 sm:py-12">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <motion.div
            initial={{ opacity: 0, y: 20 }}
            animate={{ opacity: 1, y: 0 }}
            transition={{ duration: 0.6 }}
            className="space-y-6"
          >
            {/* Header */}
            <div className="flex items-center justify-between">
              <div>
                <Link to="/order-history" className="inline-flex items-center text-teal-600 hover:text-teal-700 mb-2">
                  <ArrowLeft className="w-4 h-4 mr-1" />
                  Back to Orders
                </Link>
                <h1 className="text-2xl sm:text-3xl font-bold text-gray-900">Track Order</h1>
                <p className="text-gray-600 mt-1">Order #{orderId}</p>
              </div>
              {shipment && (
                <Button
                  variant="outline"
                  onClick={handleRefreshTracking}
                  disabled={refreshing}
                  className="hidden sm:flex items-center"
                >
                  <RefreshCw className={`w-4 h-4 mr-2 ${refreshing ? 'animate-spin' : ''}`} />
                  Refresh
                </Button>
              )}
            </div>

            {/* Order Status */}
            <Card>
              <CardHeader>
                <div className="flex justify-between items-start">
                  <CardTitle>Order Status</CardTitle>
                  <Badge className={getStatusColor(order.orderStatus)}>
                    <div className="flex items-center space-x-1">
                      {getStatusIcon(order.orderStatus)}
                      <span className="capitalize">{order.orderStatus || 'Pending'}</span>
                    </div>
                  </Badge>
                </div>
              </CardHeader>
              <CardContent className="space-y-4">
                <div className="grid grid-cols-1 sm:grid-cols-3 gap-4">
                  <div>
                    <p className="text-sm text-gray-600">Order Date</p>
                    <p className="font-medium">
                      {new Date(order.createdAt?.seconds * 1000 || order.createdAt).toLocaleDateString('en-IN', {
                        year: 'numeric',
                        month: 'long',
                        day: 'numeric'
                      })}
                    </p>
                  </div>
                  <div>
                    <p className="text-sm text-gray-600">Total Amount</p>
                    <p className="font-medium">₹{parseFloat(order.totalAmount || 0).toFixed(2)}</p>
                  </div>
                  <div>
                    <p className="text-sm text-gray-600">Payment Status</p>
                    <Badge className={
                      ['captured', 'completed', 'paid', 'success', 'succeeded'].includes(order.payment?.status) ? 
                        'bg-green-100 text-green-800' : 
                        'bg-yellow-100 text-yellow-800'
                    }>
                      {(order.payment?.status || order.paymentStatus || 'pending').replace('_', ' ')}
                    </Badge>
                  </div>
                </div>
              </CardContent>
            </Card>

            {/* Shipment Tracking */}
            {shipment && (
              <Card>
                <CardHeader>
                  <div className="flex justify-between items-start">
                    <div>
                      <CardTitle>Shipment Tracking</CardTitle>
                      <p className="text-sm text-gray-600 mt-1">
                        AWB: <span className="font-mono font-medium">{shipment.awbNumber}</span>
                      </p>
                    </div>
                    <Button
                      variant="outline"
                      onClick={handleRefreshTracking}
                      disabled={refreshing}
                      className="sm:hidden"
                      size="sm"
                    >
                      <RefreshCw className={`w-4 h-4 ${refreshing ? 'animate-spin' : ''}`} />
                    </Button>
                  </div>
                </CardHeader>
                <CardContent className="space-y-4">
                  {/* Courier Info */}
                  <div className="bg-purple-50 p-4 rounded-lg">
                    <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
                      <div>
                        <p className="text-sm text-gray-600">Courier Partner</p>
                        <p className="font-medium">{shipment.courierName || 'DTDC'}</p>
                      </div>
                      <div>
                        <p className="text-sm text-gray-600">Shipment Status</p>
                        <Badge className="bg-purple-100 text-purple-800">
                          {shipment.status || 'In Transit'}
                        </Badge>
                      </div>
                    </div>
                  </div>

                  {/* Tracking Timeline */}
                  {trackingData?.tracking_history && trackingData.tracking_history.length > 0 && (
                    <div className="space-y-4">
                      <h4 className="font-medium text-gray-900">Tracking History</h4>
                      <div className="space-y-3">
                        {trackingData.tracking_history.map((event, index) => (
                          <div key={index} className="flex items-start space-x-3">
                            <div className={`mt-1 w-8 h-8 rounded-full flex items-center justify-center ${
                              index === 0 ? 'bg-teal-100' : 'bg-gray-100'
                            }`}>
                              <MapPin className={`w-4 h-4 ${index === 0 ? 'text-teal-600' : 'text-gray-600'}`} />
                            </div>
                            <div className="flex-1">
                              <p className="font-medium text-gray-900">{event.status || event.message}</p>
                              <p className="text-sm text-gray-600">
                                {event.location && `${event.location} • `}
                                {new Date(event.date || event.timestamp).toLocaleString('en-IN')}
                              </p>
                              {event.remarks && (
                                <p className="text-sm text-gray-500 mt-1">{event.remarks}</p>
                              )}
                            </div>
                          </div>
                        ))}
                      </div>
                    </div>
                  )}

                  {/* External Tracking Link */}
                  {shipment.trackingUrl && (
                    <div className="pt-4 border-t">
                      <a 
                        href={shipment.trackingUrl} 
                        target="_blank" 
                        rel="noopener noreferrer"
                        className="inline-flex items-center text-teal-600 hover:text-teal-700 font-medium"
                      >
                        Track on DTDC Website
                        <ChevronRight className="w-4 h-4 ml-1" />
                      </a>
                    </div>
                  )}
                </CardContent>
              </Card>
            )}

            {/* Shipping Address */}
            <Card>
              <CardHeader>
                <CardTitle>Shipping Address</CardTitle>
              </CardHeader>
              <CardContent>
                <div className="space-y-2">
                  <p className="font-medium text-gray-900">
                    {order.shippingAddress?.name || order.shipping_name}
                  </p>
                  <p className="text-gray-600">
                    {order.shippingAddress?.address || order.shipping_address}
                  </p>
                  <p className="text-gray-600">
                    {order.shippingAddress?.city && `${order.shippingAddress.city}, `}
                    {order.shippingAddress?.state && `${order.shippingAddress.state} - `}
                    {order.shippingAddress?.pincode || order.shipping_pincode}
                  </p>
                  <p className="text-gray-600">
                    Phone: {order.shippingAddress?.phone || order.shipping_phone}
                  </p>
                </div>
              </CardContent>
            </Card>

            {/* Order Items */}
            <Card>
              <CardHeader>
                <CardTitle>Order Items</CardTitle>
              </CardHeader>
              <CardContent className="space-y-3">
                {order.items && order.items.map((item, idx) => (
                  <div key={idx} className="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                    <div className="flex items-center space-x-3">
                      <img 
                        alt={item.product_name || item.name}
                        className="w-12 h-12 object-cover rounded-md"
                        src="https://images.unsplash.com/photo-1658204212985-e0126040f88f" 
                      />
                      <div>
                        <h4 className="font-medium text-gray-900">{item.product_name || item.name}</h4>
                        <p className="text-sm text-gray-600">Quantity: {item.quantity}</p>
                      </div>
                    </div>
                    <p className="font-medium">₹{(item.price * item.quantity).toFixed(2)}</p>
                  </div>
                ))}
                
                <div className="border-t pt-3 mt-3 space-y-2">
                  <div className="flex justify-between text-sm">
                    <span className="text-gray-600">Subtotal:</span>
                    <span>₹{parseFloat(order.subtotal || order.totalAmount || 0).toFixed(2)}</span>
                  </div>
                  {order.tax && (
                    <div className="flex justify-between text-sm">
                      <span className="text-gray-600">Tax (GST):</span>
                      <span>₹{parseFloat(order.tax || 0).toFixed(2)}</span>
                    </div>
                  )}
                  <div className="flex justify-between font-bold text-lg border-t pt-2">
                    <span>Total:</span>
                    <span>₹{parseFloat(order.total || order.totalAmount || 0).toFixed(2)}</span>
                  </div>
                </div>
              </CardContent>
            </Card>
          </motion.div>
        </div>
      </div>
    </>
  );
};

export default TrackOrder;
