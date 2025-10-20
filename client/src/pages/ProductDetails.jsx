import React, { useState, useEffect } from 'react';
import { useParams, Link, useNavigate } from 'react-router-dom';
import { Helmet } from 'react-helmet-async';
import { motion } from 'framer-motion';
import {
  Star, ShoppingCart, Heart, Minus, Plus, ArrowLeft, Shield, Truck, RotateCcw
} from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent } from '@/components/ui/card';
import { useCart } from '@/contexts/CartContext';
import { toast } from '@/components/ui/use-toast';
import { api } from '@/lib/api';

const ProductDetails = () => {
  const { id } = useParams();
  const navigate = useNavigate();
  const [product, setProduct] = useState(null);
  const [loading, setLoading] = useState(true);
  const [quantity, setQuantity] = useState(1);
  const [selectedImage, setSelectedImage] = useState(0);
  const [imageErrors, setImageErrors] = useState({});
  const { addToCart } = useCart();

  useEffect(() => {
    const fetchProduct = async () => {
      try {
        setLoading(true);
        const response = await api.getProduct(id);
        if (response.success && response.product) {
          const p = response.product;
          
          const images = p.image 
            ? [p.image] 
            : ['/placeholder-product.svg'];
          
          const keyFeatures = Array.isArray(p.key_features) 
            ? p.key_features 
            : (p.key_features ? [p.key_features] : []);
          
          const specs = p.specifications || {};
          const specsObject = {};
          if (specs.model) specsObject['Model'] = specs.model;
          if (specs.power) specsObject['Power'] = specs.power;
          if (specs.max_vacuum) specsObject['Max Vacuum'] = specs.max_vacuum;
          if (specs.flow_rate) specsObject['Flow Rate'] = specs.flow_rate;
          if (specs.jar_capacity) specsObject['Jar Capacity'] = specs.jar_capacity;
          if (specs.noise_level) specsObject['Noise Level'] = specs.noise_level;
          if (specs.weight) specsObject['Weight'] = specs.weight;
          if (specs.dimensions) specsObject['Dimensions'] = specs.dimensions;
          
          setProduct({
            id: p.id,
            name: p.name,
            price: parseFloat(p.price) || 0,
            originalPrice: p.original_price ? parseFloat(p.original_price) : null,
            images: images,
            category: p.category || 'Medical Equipment',
            rating: 4.5,
            reviews: p.reviews_count || 0,
            inStock: p.stock > 0,
            description: p.description || '',
            features: keyFeatures,
            specifications: specsObject,
            warranty: p.warranty_info || '',
            shipping: p.shipping_info || '',
            returnPolicy: p.return_policy || '30-day return policy',
            stock: p.stock || 0
          });
        } else {
          toast({
            title: "Product not found",
            description: "This product doesn't exist or has been removed",
            variant: "destructive"
          });
          navigate('/store');
        }
      } catch (error) {
        console.error('Error fetching product:', error);
        toast({
          title: "Error",
          description: "Failed to load product details",
          variant: "destructive"
        });
      } finally {
        setLoading(false);
      }
    };

    fetchProduct();
  }, [id, navigate]);

  const handleAddToCart = () => {
    if (product) addToCart(product, quantity);
  };

  const handleRequestQuote = () => {
    toast({
      title: "Quote Request",
      description: "Your quote request has been submitted. We'll contact you soon!",
    });
  };

  const handleAddToWishlist = () => {
    toast({ title: "🚧 Feature not implemented yet." });
  };

  const incrementQuantity = () => setQuantity(prev => prev + 1);
  const decrementQuantity = () => setQuantity(prev => (prev > 1 ? prev - 1 : 1));

  const handleImageError = (index) => {
    setImageErrors(prev => ({ ...prev, [index]: true }));
  };

  const getImageSrc = (index) => {
    return imageErrors[index] ? '/placeholder-product.svg' : product.images[index];
  };

  if (loading || !product) {
    return (
      <div className="min-h-screen flex items-center justify-center">
        <div className="text-center">
          <div className="w-16 h-16 border-4 border-teal-600 border-t-transparent rounded-full animate-spin mx-auto mb-4"></div>
          <p className="text-gray-600">Loading product details...</p>
        </div>
      </div>
    );
  }

  return (
    <>
      <Helmet>
        <title>{`${product.name} - Medical Equipment | Sreemeditec`}</title>
        <meta name="description" content={product.description} />
      </Helmet>

      <div className="min-h-screen bg-gray-50">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 sm:py-8">
          <div className="mb-6 sm:mb-8">
            <Link to="/store" className="inline-flex items-center text-teal-600 hover:text-teal-700 text-sm sm:text-base">
              <ArrowLeft className="w-4 h-4 mr-2" /> Back to Store
            </Link>
          </div>

          <div className="grid lg:grid-cols-2 gap-8 md:gap-10 lg:gap-12">
            {/* Image Section - Mobile Optimized */}
            <motion.div 
              initial={{ opacity: 0, x: -50 }} 
              animate={{ opacity: 1, x: 0 }} 
              transition={{ duration: 0.8 }} 
              className="space-y-4"
            >
              <div className="relative">
                <img 
                  src={getImageSrc(selectedImage)} 
                  alt={product.name}
                  onError={() => handleImageError(selectedImage)}
                  className="w-full h-64 sm:h-80 md:h-96 object-cover rounded-xl shadow-2xl" 
                />
                {product.badge && (
                  <Badge className="absolute top-4 left-4 bg-teal-600 text-white text-sm">
                    {product.badge}
                  </Badge>
                )}
              </div>
              <div className="grid grid-cols-4 gap-2">
                {product.images.map((img, i) => (
                  <button
                    key={i}
                    onClick={() => setSelectedImage(i)}
                    className={`rounded-lg ring-2 transition-all active:scale-95 ${
                      selectedImage === i ? 'ring-teal-600 ring-offset-2' : 'ring-gray-200 hover:ring-teal-300'
                    }`}
                  >
                    <img 
                      src={getImageSrc(i)} 
                      alt={`View ${i}`}
                      onError={() => handleImageError(i)}
                      className="w-full h-16 sm:h-20 object-cover rounded-md" 
                    />
                  </button>
                ))}
              </div>
            </motion.div>

            {/* Details Section - Mobile Enhanced */}
            <motion.div 
              initial={{ opacity: 0, x: 50 }} 
              animate={{ opacity: 1, x: 0 }} 
              transition={{ duration: 0.8 }} 
              className="space-y-5 md:space-y-6"
            >
              <div>
                <p className="text-xs sm:text-sm text-gray-600 mb-2">{product.category}</p>
                <h1 className="text-2xl sm:text-3xl md:text-4xl font-bold text-gray-900 mb-4">{product.name}</h1>
                <div className="flex flex-wrap items-center gap-3 sm:gap-4">
                  <div className="flex">
                    {[...Array(5)].map((_, i) => (
                      <Star
                        key={i}
                        className={`w-4 h-4 sm:w-5 sm:h-5 ${i < Math.floor(product.rating) ? 'text-yellow-400' : 'text-gray-300'}`}
                        fill={i < Math.floor(product.rating) ? 'currentColor' : 'none'}
                      />
                    ))}
                  </div>
                  <span className="text-sm text-gray-600">({product.reviews} reviews)</span>
                  <Badge variant="outline" className="text-green-600 border-green-600 text-xs sm:text-sm">
                    {product.inStock ? 'In Stock' : 'Out of Stock'}
                  </Badge>
                </div>
              </div>

              <div>
                <div className="flex flex-wrap items-center gap-2 sm:gap-3">
                  <span className="text-2xl sm:text-3xl font-bold text-gray-900">₹{product.price.toFixed(2)}</span>
                  {product.originalPrice && (
                    <>
                      <span className="text-lg sm:text-xl text-gray-500 line-through">₹{product.originalPrice.toFixed(2)}</span>
                      <Badge className="bg-red-100 text-red-600 text-xs sm:text-sm">
                        Save ₹{(product.originalPrice - product.price).toFixed(2)}
                      </Badge>
                    </>
                  )}
                </div>
                <p className="text-xs sm:text-sm text-gray-600 mt-1">Inclusive of all taxes</p>
              </div>

              <p className="text-sm sm:text-base text-gray-600 leading-relaxed">{product.description}</p>

              <div className="flex flex-col sm:flex-row sm:items-center gap-3 sm:gap-4">
                <span className="text-sm font-medium text-gray-700">Quantity:</span>
                <div className="flex border rounded-lg shadow-sm w-fit">
                  <button onClick={decrementQuantity} className="px-5 py-3 bg-gray-50 hover:bg-gray-100 rounded-l-lg active:scale-95 transition-all min-h-[44px]">
                    <Minus className="w-4 h-4" />
                  </button>
                  <span className="px-6 py-3 border-x text-center font-semibold text-gray-700 min-w-[60px]">{quantity}</span>
                  <button onClick={incrementQuantity} className="px-5 py-3 bg-gray-50 hover:bg-gray-100 rounded-r-lg active:scale-95 transition-all min-h-[44px]">
                    <Plus className="w-4 h-4" />
                  </button>
                </div>
              </div>

              <div className="flex flex-col gap-3 pt-2">
                <Button 
                  onClick={handleAddToCart} 
                  className="w-full bg-[#1F8F76] text-white h-12 sm:h-14 text-base sm:text-lg font-medium shadow-lg hover:shadow-xl transform hover:-translate-y-0.5 transition-all"
                >
                  <ShoppingCart className="w-5 h-5 mr-2" /> Add to Cart
                </Button>
                <div className="flex gap-3">
                  <Button onClick={handleRequestQuote} variant="outline" className="flex-1 h-12 sm:h-14">
                    Request Quote
                  </Button>
                  <Button onClick={handleAddToWishlist} variant="ghost" className="h-12 sm:h-14 px-6">
                    <Heart className="w-5 h-5 text-gray-500" />
                  </Button>
                </div>
              </div>

              <div className="grid grid-cols-1 sm:grid-cols-3 gap-4 pt-6 border-t">
                {[{
                  icon: <Shield className="w-5 h-5 sm:w-6 sm:h-6 text-teal-600" />, 
                  label: 'Warranty', 
                  text: product.warranty
                }, {
                  icon: <Truck className="w-5 h-5 sm:w-6 sm:h-6 text-teal-600" />, 
                  label: 'Free Shipping', 
                  text: product.shipping
                }, {
                  icon: <RotateCcw className="w-5 h-5 sm:w-6 sm:h-6 text-teal-600" />, 
                  label: 'Returns', 
                  text: product.returnPolicy || '30-day return policy'
                }].map(({ icon, label, text }, idx) => (
                  <div key={idx} className="flex items-start space-x-3">
                    {icon}
                    <div>
                      <p className="text-xs sm:text-sm font-semibold text-gray-800">{label}</p>
                      <p className="text-xs text-gray-600">{text}</p>
                    </div>
                  </div>
                ))}
              </div>
            </motion.div>
          </div>

          {/* Features + Specifications - Mobile Optimized */}
          <motion.div 
            initial={{ opacity: 0, y: 50 }} 
            whileInView={{ opacity: 1, y: 0 }} 
            transition={{ duration: 0.8 }} 
            viewport={{ once: true }} 
            className="mt-12 sm:mt-16 grid md:grid-cols-2 gap-6 md:gap-8"
          >
            <Card className="shadow-lg">
              <CardContent className="p-5 sm:p-6">
                <h3 className="text-lg sm:text-xl font-bold text-gray-900 mb-4">Key Features</h3>
                <ul className="space-y-3">
                  {product.features.map((f, i) => (
                    <li key={i} className="flex items-start space-x-3">
                      <div className="w-2 h-2 bg-teal-600 rounded-full mt-2 flex-shrink-0" />
                      <span className="text-sm sm:text-base text-gray-600">{f}</span>
                    </li>
                  ))}
                </ul>
              </CardContent>
            </Card>

            <Card className="shadow-lg">
              <CardContent className="p-5 sm:p-6">
                <h3 className="text-lg sm:text-xl font-bold text-gray-900 mb-4">Specifications</h3>
                <div className="space-y-3">
                  {Object.entries(product.specifications).map(([k, v]) => (
                    <div key={k} className="flex justify-between py-2 border-b text-sm sm:text-base">
                      <span className="font-medium text-gray-700">{k}:</span>
                      <span className="text-gray-600 text-right">{v}</span>
                    </div>
                  ))}
                </div>
              </CardContent>
            </Card>
          </motion.div>
        </div>
      </div>
    </>
  );
};

export default ProductDetails;
