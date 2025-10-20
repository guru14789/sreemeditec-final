import React, { useState, useEffect } from 'react';
import { motion } from 'framer-motion';
import { Package, Eye, Edit, Truck, CheckCircle, Clock, Phone, MapPin } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { toast } from '@/components/ui/use-toast';
import { api } from '@/lib/api';

const OrderList = () => {
  const [orders, setOrders] = useState([]);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    loadOrders();
  }, []);

  const transformOrder = (order) => {
    const shippingAddr = order.shippingAddress || order.shipping_address || {};
    
    let addressString = 'N/A';
    let city = null;
    
    if (typeof shippingAddr === 'string') {
      addressString = shippingAddr;
    } else if (typeof shippingAddr === 'object' && Object.keys(shippingAddr).length > 0) {
      // Build formatted address only if we have actual data
      const addressParts = [
        shippingAddr.address,
        shippingAddr.city,
        shippingAddr.state,
        shippingAddr.pincode
      ].filter(Boolean);
      
      if (addressParts.length > 0) {
        addressString = addressParts.join(', ');
      }
      
      city = shippingAddr.city || null;
    }
    
    return {
      ...order,
      id: order.id || order.order_id || order.orderId,
      userId: order.userId || order.user_id,
      customerName: order.customer_name || order.customerName || 'N/A',
      email: order.email || 'N/A',
      phone: order.phone || 'N/A',
      address: addressString,
      city: city,
      state: typeof shippingAddr === 'object' ? shippingAddr.state || '' : '',
      pincode: typeof shippingAddr === 'object' ? shippingAddr.pincode || '' : '',
      shippingAddress: shippingAddr,
      total: parseFloat(order.total || order.total_amount || order.totalAmount || 0),
      subtotal: parseFloat(order.subtotal || 0),
      tax: parseFloat(order.tax || 0),
      status: order.status || order.order_status || order.orderStatus || 'pending',
      date: order.date || order.created_at || order.createdAt || new Date().toISOString(),
      items: (order.items || []).map(item => ({
        ...item,
        price: parseFloat(item.price || 0),
        quantity: parseInt(item.quantity || 1, 10)
      })),
    };
  };

  const loadOrders = async () => {
    try {
      setLoading(true);
      const response = await api.request('/admin/orders');
      if (response.success) {
        const transformedOrders = (response.orders || []).map(transformOrder);
        setOrders(transformedOrders);
      }
    } catch (error) {
      console.error('Failed to load orders:', error);
      toast({
        title: "Error",
        description: "Failed to load orders.",
        variant: "destructive",
      });
    } finally {
      setLoading(false);
    }
  };

  const updateOrderStatus = async (orderId, newStatus) => {
    try {
      const response = await api.updateOrderStatus(orderId, newStatus);
      if (response.success) {
        setOrders(orders.map(order => 
          order.id === orderId ? { ...order, status: newStatus } : order
        ));

        toast({
          title: "Order updated",
          description: `Order status updated to ${newStatus}`,
        });
      }
    } catch (error) {
      toast({
        title: "Error",
        description: "Failed to update order status.",
        variant: "destructive",
      });
    }
  };

  const getStatusColor = (status) => {
    switch (status) {
      case 'pending':
        return 'bg-yellow-100 text-yellow-800';
      case 'confirmed':
        return 'bg-blue-100 text-blue-800';
      case 'processing':
        return 'bg-purple-100 text-purple-800';
      case 'shipped':
        return 'bg-indigo-100 text-indigo-800';
      case 'out_for_delivery':
        return 'bg-orange-100 text-orange-800';
      case 'delivered':
        return 'bg-green-100 text-green-800';
      case 'cancelled':
        return 'bg-red-100 text-red-800';
      default:
        return 'bg-gray-100 text-gray-800';
    }
  };

  const handleUpdateStatus = (orderId, newStatus) => {
    updateOrderStatus(orderId, newStatus);
  };

  const [selectedOrder, setSelectedOrder] = useState(null);

  const handleViewOrder = (order) => {
    setSelectedOrder(order);
  };

return (
    <>
      <Card>
        <CardHeader>
          <CardTitle>Orders Management</CardTitle>
        </CardHeader>
        <CardContent>
          {loading ? (
            <p className="text-gray-500 text-center py-8">Loading orders...</p>
          ) : orders.length === 0 ? (
            <p className="text-gray-500 text-center py-8">No orders found</p>
          ) : (
            <div className="space-y-4">
              {orders.map((order) => (
                <div key={order.id} className="border rounded-lg p-4">
                  <div className="flex justify-between items-start mb-4">
                    <div>
                      <h3 className="font-medium">Order #{order.id}</h3>
                      <p className="text-sm text-gray-600">{order.customerName}</p>
                      <p className="text-sm text-gray-600">
                        {new Date(order.date).toLocaleDateString()}
                      </p>
                      {order.trackingNumber && (
                        <p className="text-xs text-gray-500 font-mono">
                          Tracking: {order.trackingNumber}
                        </p>
                      )}
                    </div>
                    <Badge className={getStatusColor(order.status)}>
                      {order.status.replace('_', ' ').toUpperCase()}
                    </Badge>
                  </div>

                  <div className="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                    <div>
                      <p className="font-medium">₹{order.total.toFixed(2)}</p>
                      <p className="text-sm text-gray-600">{order.items.length} items</p>
                      <p className="text-sm text-gray-600">
                        Payment: {order.paymentMethod === 'cod' ? 'Cash on Delivery' : 'Card'}
                      </p>
                    </div>
                    <div className="space-y-1">
                      <div className="flex items-center text-sm text-gray-600">
                        <Phone className="w-3 h-3 mr-1" />
                        {order.phone}
                      </div>
                      {order.city && (
                        <div className="flex items-center text-sm text-gray-600">
                          <MapPin className="w-3 h-3 mr-1" />
                          <span className="font-medium">{order.city}</span>
                        </div>
                      )}
                    </div>
                  </div>

                  <div className="flex flex-col sm:flex-row justify-between items-start sm:items-center space-y-2 sm:space-y-0">
                    <div className="flex items-center space-x-2">
                      <span className="text-sm text-gray-600">Status:</span>
                      <Select
                        value={order.status}
                        onValueChange={(value) => handleUpdateStatus(order.id, value)}
                      >
                        <SelectTrigger className="w-40 h-8">
                          <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                          <SelectItem value="pending">Pending</SelectItem>
                          <SelectItem value="confirmed">Confirmed</SelectItem>
                          <SelectItem value="processing">Processing</SelectItem>
                          <SelectItem value="shipped">Shipped</SelectItem>
                          <SelectItem value="out_for_delivery">Out for Delivery</SelectItem>
                          <SelectItem value="delivered">Delivered</SelectItem>
                          <SelectItem value="cancelled">Cancelled</SelectItem>
                        </SelectContent>
                      </Select>
                    </div>

                    <div className="flex space-x-2">
                      <Button
                        variant="outline"
                        size="sm"
                        onClick={() => handleViewOrder(order)}
                      >
                        <Eye className="w-4 h-4 mr-1" />
                        Details
                      </Button>
                    </div>
                  </div>
                </div>
              ))}
            </div>
          )}
        </CardContent>
      </Card>

      {/* Order Details Modal */}
      {selectedOrder && (
        <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4 z-50">
          <div className="bg-white rounded-lg max-w-2xl w-full max-h-[90vh] overflow-y-auto">
            <div className="p-6">
              <div className="flex justify-between items-center mb-4">
                <h2 className="text-xl font-bold">Order Details #{selectedOrder.id}</h2>
                <Button variant="outline" onClick={() => setSelectedOrder(null)}>
                  Close
                </Button>
              </div>

              <div className="space-y-4">
                <div className="grid grid-cols-2 gap-4">
                  <div>
                    <h3 className="font-medium mb-2">Customer Information</h3>
                    <p>{selectedOrder.customerName || selectedOrder.userId || 'N/A'}</p>
                    <p className="text-sm text-gray-600">{selectedOrder.email || 'N/A'}</p>
                    <p className="text-sm text-gray-600">{selectedOrder.phone || 'N/A'}</p>
                  </div>
                  <div>
                    <h3 className="font-medium mb-2 flex items-center gap-2">
                      <MapPin className="w-4 h-4" />
                      Delivery Address
                    </h3>
                    {typeof selectedOrder.shippingAddress === 'object' ? (
                      <div className="text-sm text-gray-600 space-y-1">
                        {selectedOrder.shippingAddress.name && (
                          <p className="font-medium text-gray-900">{selectedOrder.shippingAddress.name}</p>
                        )}
                        {selectedOrder.shippingAddress.address && (
                          <p>{selectedOrder.shippingAddress.address}</p>
                        )}
                        <p>
                          {[selectedOrder.shippingAddress.city, selectedOrder.shippingAddress.state, selectedOrder.shippingAddress.pincode]
                            .filter(Boolean)
                            .join(', ')}
                        </p>
                        {selectedOrder.shippingAddress.phone && (
                          <p className="flex items-center gap-1">
                            <Phone className="w-3 h-3" />
                            {selectedOrder.shippingAddress.phone}
                          </p>
                        )}
                      </div>
                    ) : (
                      <p className="text-sm text-gray-600">{selectedOrder.address || 'N/A'}</p>
                    )}
                  </div>
                </div>

                <div>
                  <h3 className="font-medium mb-2">Order Items</h3>
                  <div className="space-y-2">
                    {(selectedOrder.items || []).map((item, idx) => (
                      <div key={item.id || idx} className="flex justify-between items-center p-2 bg-gray-50 rounded">
                        <div>
                          <p className="font-medium">{item.name || 'Item'}</p>
                          <p className="text-sm text-gray-600">Qty: {item.quantity || 1}</p>
                        </div>
                        <p className="font-medium">₹{((item.price || 0) * (item.quantity || 1)).toFixed(2)}</p>
                      </div>
                    ))}
                  </div>
                </div>

                <div className="border-t pt-4">
                  {selectedOrder.subtotal && (
                    <div className="flex justify-between mb-2">
                      <span>Subtotal:</span>
                      <span>₹{selectedOrder.subtotal.toFixed(2)}</span>
                    </div>
                  )}
                  {selectedOrder.tax && (
                    <div className="flex justify-between mb-2">
                      <span>Tax:</span>
                      <span>₹{selectedOrder.tax.toFixed(2)}</span>
                    </div>
                  )}
                  <div className="flex justify-between font-bold text-lg">
                    <span>Total:</span>
                    <span>₹{(selectedOrder.total || selectedOrder.totalAmount || 0).toFixed(2)}</span>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      )}
    </>
  );
};

export default OrderList;