import React from 'react';
import { Link, useNavigate } from 'react-router-dom';
import { Helmet } from 'react-helmet-async';
import { motion } from 'framer-motion';
import { Minus, Plus, Trash2, ShoppingBag, ArrowLeft } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { useCart } from '@/contexts/CartContext';
import { useAuth } from '@/contexts/AuthContext';

const Cart = () => {
  const { cartItems, updateQuantity, removeFromCart, getCartTotal, clearCart } = useCart();
  const { user } = useAuth();
  const navigate = useNavigate();

  if (cartItems.length === 0) {
    return (
      <>
        <Helmet>
          <title>Shopping Cart - Sreemeditec</title>
          <meta name="description" content="Review your selected medical equipment items in your shopping cart before checkout." />
        </Helmet>

        <div className="min-h-screen bg-gray-50 flex items-center justify-center px-4">
          <motion.div
            initial={{ opacity: 0, y: 50 }}
            animate={{ opacity: 1, y: 0 }}
            transition={{ duration: 0.8 }}
            className="text-center space-y-6 max-w-md w-full"
          >
            <div className="w-20 h-20 sm:w-24 sm:h-24 mx-auto bg-gray-200 rounded-full flex items-center justify-center">
              <ShoppingBag className="w-10 h-10 sm:w-12 sm:h-12 text-gray-400" />
            </div>
            <h1 className="text-2xl sm:text-3xl font-bold text-gray-900">Your cart is empty</h1>
            <p className="text-sm sm:text-base text-gray-600 max-w-md mx-auto px-4">
              Looks like you haven't added any medical equipment to your cart yet. Browse our store to find the products you need.
            </p>
            <Link to="/store">
              <Button className="btn-primary px-8 py-3 text-base">
                Continue Shopping
              </Button>
            </Link>
          </motion.div>
        </div>
      </>
    );
  }

  const subtotal = getCartTotal();
  const shipping = 0;
  const total = subtotal + shipping;

  return (
    <>
      <Helmet>
        <title>{`Your Cart (${cartItems.length} items) - Sreemeditec`}</title>
        <meta name="description" content="Review your selected medical equipment items in your shopping cart before checkout." />
      </Helmet>

      <div className="bg-gray-50 py-6 sm:py-8 md:py-12 min-h-screen">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <h1 className="text-2xl sm:text-3xl font-bold text-gray-900 mb-6 sm:mb-8">Your Cart</h1>

          <div className="grid grid-cols-1 lg:grid-cols-3 gap-6 md:gap-8 items-start">
            <div className="lg:col-span-2 bg-white p-4 sm:p-6 rounded-xl shadow-md space-y-4">
              <div className="hidden md:grid grid-cols-12 gap-4 font-medium text-sm text-gray-500 uppercase border-b pb-4">
                <div className="col-span-5">Product</div>
                <div className="col-span-2 text-right">Price</div>
                <div className="col-span-3 text-center">Quantity</div>
                <div className="col-span-2 text-right">Total</div>
              </div>

              {cartItems.map((item) => (
                <div key={item._id} className="grid grid-cols-1 md:grid-cols-12 gap-4 items-center border-b pb-4 last:border-b-0 last:pb-0">
                  <div className="md:col-span-5 flex items-center space-x-3 sm:space-x-4">
                    <img 
                      className="w-16 h-16 sm:w-20 sm:h-20 object-cover rounded-lg flex-shrink-0 shadow-sm" 
                      alt={item.name} 
                      src="https://images.unsplash.com/photo-1693264251393-d28f984ca283" 
                    />
                    <div className="flex-1 min-w-0">
                      <p className="font-semibold text-gray-900 text-sm sm:text-base line-clamp-2">{item.name}</p>
                      <p className="text-xs sm:text-sm text-gray-600 mt-0.5">{item.category}</p>
                    </div>
                  </div>

                  <div className="md:col-span-2 flex justify-between md:justify-end items-center">
                    <span className="md:hidden text-sm text-gray-500">Price:</span>
                    <span className="font-medium text-sm sm:text-base">₹{item.price.toFixed(2)}</span>
                  </div>
                  
                  <div className="md:col-span-3 flex justify-between md:justify-center items-center">
                    <span className="md:hidden text-sm text-gray-500">Quantity:</span>
                    <div className="flex items-center border rounded-lg shadow-sm">
                      <Button 
                        variant="ghost" 
                        size="icon" 
                        className="h-11 w-11 sm:h-12 sm:w-12 rounded-r-none hover:bg-gray-100" 
                        onClick={() => updateQuantity(item._id, item.quantity - 1)}
                      >
                        <Minus className="h-4 w-4" />
                      </Button>
                      <span className="px-4 sm:px-5 text-center font-medium min-w-[48px]">{item.quantity}</span>
                      <Button 
                        variant="ghost" 
                        size="icon" 
                        className="h-11 w-11 sm:h-12 sm:w-12 rounded-l-none hover:bg-gray-100" 
                        onClick={() => updateQuantity(item._id, item.quantity + 1)}
                      >
                        <Plus className="h-4 w-4" />
                      </Button>
                    </div>
                  </div>
                  
                  <div className="md:col-span-2 flex items-center justify-between md:justify-end">
                    <span className="md:hidden text-sm text-gray-500">Subtotal:</span>
                    <div className="flex items-center gap-2 sm:gap-3">
                      <span className="font-semibold text-base sm:text-lg">₹{(item.price * item.quantity).toFixed(2)}</span>
                      <Button 
                        variant="ghost" 
                        size="icon" 
                        className="text-red-500 hover:bg-red-50 hover:text-red-600 h-9 w-9 rounded-lg" 
                        onClick={() => removeFromCart(item._id)}
                      >
                        <Trash2 className="h-4 w-4 sm:h-5 sm:w-5" />
                      </Button>
                    </div>
                  </div>
                </div>
              ))}

              <div className="mt-6 pt-4 flex flex-col sm:flex-row justify-between items-center gap-3 border-t">
                <Link to="/store" className="w-full sm:w-auto">
                  <Button variant="outline" className="w-full sm:w-auto">
                    <ArrowLeft className="w-4 h-4 mr-2" />
                    Continue Shopping
                  </Button>
                </Link>
                <Button 
                  variant="outline" 
                  className="w-full sm:w-auto text-red-500 border-red-500 hover:bg-red-50 hover:text-red-600" 
                  onClick={clearCart}
                >
                  <Trash2 className="w-4 h-4 mr-2" />
                  Clear Cart
                </Button>
              </div>
            </div>

            <div className="lg:col-span-1">
              <Card className="sticky top-20 sm:top-24 shadow-lg">
                <CardHeader>
                  <CardTitle className="text-lg sm:text-xl">Order Summary</CardTitle>
                </CardHeader>
                <CardContent className="space-y-4">
                  <div className="space-y-3">
                    <div className="flex justify-between text-sm sm:text-base">
                      <p className="text-gray-600">Subtotal</p>
                      <p className="font-medium">₹{subtotal.toFixed(2)}</p>
                    </div>
                    <div className="flex justify-between text-sm sm:text-base">
                      <p className="text-gray-600">Shipping</p>
                      <p className="font-medium text-teal-600">Free</p>
                    </div>
                  </div>
                  <div className="border-t pt-4">
                    <div className="flex justify-between font-bold text-base sm:text-lg">
                      <p>Total</p>
                      <p>₹{total.toFixed(2)}</p>
                    </div>
                  </div>

                  {user ? (
                    <Link to="/checkout" className="w-full block">
                      <Button className="w-full btn-primary text-base sm:text-lg h-12 sm:h-14 shadow-lg hover:shadow-xl transform hover:-translate-y-0.5 transition-all">
                        Proceed to Checkout
                      </Button>
                    </Link>
                  ) : (
                    <div className="text-center space-y-3 pt-2">
                      <p className="text-xs sm:text-sm text-gray-500">Please sign in to checkout</p>
                      <Button 
                        className="w-full btn-primary h-12 sm:h-14 text-base shadow-lg" 
                        onClick={() => navigate('/login')}
                      >
                        Login to Checkout
                      </Button>
                    </div>
                  )}
                </CardContent>
              </Card>
            </div>
          </div>
        </div>
      </div>
    </>
  );
};

export default Cart;
