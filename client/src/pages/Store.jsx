import React, { useState, useEffect } from 'react';
import { Helmet } from 'react-helmet-async';
import { motion } from 'framer-motion';
import { categories } from '@/data/products';
import StoreSidebar from '@/components/store/StoreSidebar';
import StoreFilters from '@/components/store/StoreFilters';
import ProductCard from '@/components/store/ProductCard';
import { api } from '@/lib/api';

const Store = () => {
  const [products, setProducts] = useState([]);
  const [filteredProducts, setFilteredProducts] = useState([]);
  const [searchTerm, setSearchTerm] = useState('');
  const [selectedCategory, setSelectedCategory] = useState('all');
  const [sortBy, setSortBy] = useState('default');
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);

  useEffect(() => {
    fetchProducts();
  }, []);

  const fetchProducts = async () => {
    try {
      setLoading(true);
      setError(null);
      const response = await api.getProducts();
      if (response.success && response.products) {
        setProducts(response.products);
        setFilteredProducts(response.products);
      } else {
        setError('Failed to load products');
      }
    } catch (err) {
      console.error('Error fetching products:', err);
      setError(err.message || 'Failed to load products');
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    let filtered = products;

    if (selectedCategory !== 'all') {
      filtered = filtered.filter(product => product.category === selectedCategory);
    }

    if (searchTerm) {
      filtered = filtered.filter(product =>
        product.name.toLowerCase().includes(searchTerm.toLowerCase())
      );
    }

    switch (sortBy) {
      case 'price-low':
        filtered = [...filtered].sort((a, b) => a.price - b.price);
        break;
      case 'price-high':
        filtered = [...filtered].sort((a, b) => b.price - a.price);
        break;
      case 'rating':
        filtered = [...filtered].sort((a, b) => b.rating - a.rating);
        break;
      case 'name':
        filtered = [...filtered].sort((a, b) => a.name.localeCompare(b.name));
        break;
      default:
        break;
    }

    setFilteredProducts(filtered);
  }, [products, selectedCategory, searchTerm, sortBy]);

  return (
    <>
      <Helmet>
        <title>Medical Equipment Store - Sreemeditec</title>
        <meta
          name="description"
          content="Explore our complete catalog of medical equipment including diagnostics, patient care tools, and hospital-grade solutions."
        />
      </Helmet>

      {/* Hero Section - Mobile Optimized */}
      <div className="bg-gradient-to-r from-[#448b78] to-[#0b6e5e] border-b">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 py-12 sm:py-16 md:py-20 text-center">
          <motion.div
            initial={{ opacity: 0, y: 20 }}
            animate={{ opacity: 1, y: 0 }}
            transition={{ duration: 0.6 }}
          >
            <h1 className="text-3xl sm:text-4xl md:text-5xl font-bold text-[#ffffff]">Medical Equipment Store</h1>
            <p className="text-base sm:text-lg text-gray-100 mt-3 sm:mt-4 max-w-2xl mx-auto px-4">
              Explore our certified, high-quality medical devices for diagnostics, monitoring, and patient care.
            </p>
          </motion.div>
        </div>
      </div>

      {/* Store Section - Mobile Enhanced */}
      <section className="bg-gray-50 py-8 sm:py-12 md:py-16">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 grid grid-cols-1 lg:grid-cols-4 gap-6 md:gap-8 lg:gap-10">
          {/* Sidebar - Mobile Collapsible */}
          <aside className="lg:col-span-1 bg-white p-4 sm:p-6 rounded-xl shadow-md lg:sticky lg:top-24 h-fit">
            <StoreSidebar
              categories={categories}
              selectedCategory={selectedCategory}
              onSelectCategory={setSelectedCategory}
            />
          </aside>

          {/* Main Content */}
          <div className="lg:col-span-3 space-y-6 md:space-y-8 lg:space-y-10">
            {/* Filters */}
            <StoreFilters
              searchTerm={searchTerm}
              onSearchChange={(e) => setSearchTerm(e.target.value)}
              sortBy={sortBy}
              onSortChange={setSortBy}
              productCount={filteredProducts.length}
            />

            {/* Loading State */}
            {loading && (
              <div className="text-center py-12 sm:py-16 bg-white border rounded-xl shadow-sm">
                <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-[#448b78] mx-auto mb-4"></div>
                <p className="text-gray-500 text-base sm:text-lg">Loading products...</p>
              </div>
            )}

            {/* Error State */}
            {error && !loading && (
              <motion.div
                initial={{ opacity: 0 }}
                animate={{ opacity: 1 }}
                className="text-center py-12 sm:py-16 bg-white border rounded-xl shadow-sm"
              >
                <p className="text-red-500 text-base sm:text-lg mb-4">{error}</p>
                <button 
                  onClick={fetchProducts}
                  className="px-6 py-2 bg-[#448b78] text-white rounded-lg hover:bg-[#357059] transition"
                >
                  Try Again
                </button>
              </motion.div>
            )}

            {/* Product Grid - Mobile Optimized */}
            {!loading && !error && (
              <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-2 xl:grid-cols-3 gap-5 md:gap-6 lg:gap-8">
                {filteredProducts.map((product, index) => (
                  <motion.div
                    key={product.id}
                    initial={{ opacity: 0, y: 30 }}
                    whileInView={{ opacity: 1, y: 0 }}
                    transition={{ duration: 0.4, delay: index * 0.05 }}
                    viewport={{ once: true }}
                  >
                    <ProductCard product={product} index={index} />
                  </motion.div>
                ))}
              </div>
            )}

            {/* No Products Found */}
            {!loading && !error && filteredProducts.length === 0 && (
              <motion.div
                initial={{ opacity: 0 }}
                animate={{ opacity: 1 }}
                className="text-center py-12 sm:py-16 bg-white border rounded-xl shadow-sm"
              >
                <p className="text-gray-500 text-base sm:text-lg">No products found matching your filters.</p>
              </motion.div>
            )}
          </div>
        </div>
      </section>
    </>
  );
};

export default Store;
