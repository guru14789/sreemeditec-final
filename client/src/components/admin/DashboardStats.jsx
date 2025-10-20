
import React, { useState, useEffect } from 'react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { DollarSign, ShoppingCart, Users, TrendingUp, CreditCard, Wallet, MapPin } from 'lucide-react';
import { LineChart, Line, XAxis, YAxis, CartesianGrid, Tooltip, ResponsiveContainer, AreaChart, Area, PieChart, Pie, Cell, Legend } from 'recharts';
import api from '@/lib/api';

const StatCard = ({ title, value, icon, description }) => {
  return (
    <Card>
      <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
        <CardTitle className="text-sm font-medium">{title}</CardTitle>
        {icon}
      </CardHeader>
      <CardContent>
        <div className="text-2xl font-bold">{value}</div>
        <p className="text-xs text-muted-foreground">{description}</p>
      </CardContent>
    </Card>
  );
};

const DashboardStats = () => {
  const [stats, setStats] = useState(null);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    const fetchStats = async () => {
      try {
        const response = await api.request('/admin/stats');
        if (response.success) {
          setStats(response.stats);
        }
      } catch (error) {
        console.error('Failed to fetch stats:', error);
      } finally {
        setLoading(false);
      }
    };

    fetchStats();
  }, []);

  if (loading) {
    return (
      <div className="space-y-6">
        <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
          {[1, 2, 3, 4].map((i) => (
            <Card key={i} className="animate-pulse">
              <CardHeader>
                <div className="h-4 bg-gray-200 rounded w-1/2"></div>
              </CardHeader>
              <CardContent>
                <div className="h-8 bg-gray-200 rounded w-3/4 mb-2"></div>
                <div className="h-3 bg-gray-200 rounded w-1/2"></div>
              </CardContent>
            </Card>
          ))}
        </div>
      </div>
    );
  }

  if (!stats) {
    return (
      <div className="text-center py-10">
        <p className="text-gray-500">Failed to load dashboard statistics</p>
      </div>
    );
  }

  // Format monthly revenue data for chart
  const chartData = stats.monthly_revenue?.map(item => {
    const date = new Date(item.month + '-01');
    const monthName = date.toLocaleDateString('en-US', { month: 'short' });
    const label = item.is_current ? `${monthName} (Current)` : 
                  item.is_future ? `${monthName} (Projected)` : monthName;
    
    return {
      name: label,
      revenue: parseFloat(item.revenue),
      isFuture: item.is_future || false
    };
  }) || [];

  // Format payment methods data for pie chart
  const paymentMethodsData = stats.payment_methods ? Object.entries(stats.payment_methods).map(([method, count]) => ({
    name: method.toUpperCase(),
    value: count
  })) : [];

  const COLORS = ['#1d7d69', '#15b097', '#60d5c0', '#a0e7d9'];

  return (
    <div className="space-y-6">
      <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
        <StatCard
          title="Total Revenue"
          value={`₹${stats.total_revenue?.toLocaleString() || '0'}`}
          icon={<DollarSign className="h-4 w-4 text-muted-foreground" />}
          description="Total revenue from all orders"
        />
        <StatCard
          title="Today's Revenue"
          value={`₹${stats.today_revenue?.toLocaleString() || '0'}`}
          icon={<TrendingUp className="h-4 w-4 text-muted-foreground" />}
          description="Revenue generated today"
        />
        <StatCard
          title="Total Orders"
          value={stats.total_orders?.toLocaleString() || '0'}
          icon={<ShoppingCart className="h-4 w-4 text-muted-foreground" />}
          description={`${stats.total_payments || 0} successful payments`}
        />
        <StatCard
          title="Avg. Order Value"
          value={`₹${stats.avg_order_value?.toLocaleString(undefined, { maximumFractionDigits: 0 }) || '0'}`}
          icon={<Wallet className="h-4 w-4 text-muted-foreground" />}
          description="Average value per order"
        />
      </div>

      <div className="grid gap-6 md:grid-cols-2">
        {chartData.length > 0 && (
          <Card>
            <CardHeader>
              <CardTitle>Revenue Overview</CardTitle>
            </CardHeader>
            <CardContent>
              <div className="h-[350px]">
                <ResponsiveContainer width="100%" height="100%">
                  <AreaChart data={chartData} margin={{ top: 5, right: 30, left: 20, bottom: 5 }}>
                    <defs>
                      <linearGradient id="colorRevenue" x1="0" y1="0" x2="0" y2="1">
                        <stop offset="5%" stopColor="#1d7d69" stopOpacity={0.8}/>
                        <stop offset="95%" stopColor="#1d7d69" stopOpacity={0}/>
                      </linearGradient>
                    </defs>
                    <CartesianGrid strokeDasharray="3 3" />
                    <XAxis dataKey="name" />
                    <YAxis />
                    <Tooltip 
                      contentStyle={{
                        backgroundColor: 'rgba(255, 255, 255, 0.8)',
                        backdropFilter: 'blur(5px)',
                        border: '1px solid #1d7d69'
                      }}
                      formatter={(value) => [`₹${value.toLocaleString()}`, 'Revenue']}
                    />
                    <Area type="monotone" dataKey="revenue" stroke="#1d7d69" fillOpacity={1} fill="url(#colorRevenue)" />
                  </AreaChart>
                </ResponsiveContainer>
              </div>
            </CardContent>
          </Card>
        )}

        {paymentMethodsData.length > 0 && (
          <Card>
            <CardHeader>
              <CardTitle className="flex items-center gap-2">
                <CreditCard className="h-5 w-5" />
                Payment Methods
              </CardTitle>
            </CardHeader>
            <CardContent>
              <div className="h-[350px]">
                <ResponsiveContainer width="100%" height="100%">
                  <PieChart>
                    <Pie
                      data={paymentMethodsData}
                      cx="50%"
                      cy="50%"
                      labelLine={false}
                      label={({ name, percent }) => `${name} ${(percent * 100).toFixed(0)}%`}
                      outerRadius={100}
                      fill="#8884d8"
                      dataKey="value"
                    >
                      {paymentMethodsData.map((entry, index) => (
                        <Cell key={`cell-${index}`} fill={COLORS[index % COLORS.length]} />
                      ))}
                    </Pie>
                    <Tooltip />
                    <Legend />
                  </PieChart>
                </ResponsiveContainer>
              </div>
            </CardContent>
          </Card>
        )}
      </div>

      {stats.recent_orders && stats.recent_orders.length > 0 && (
        <Card>
          <CardHeader>
            <CardTitle>Recent Orders</CardTitle>
          </CardHeader>
          <CardContent>
            <div className="space-y-4">
              {stats.recent_orders.slice(0, 5).map((order) => {
                const shippingAddr = order.shippingAddress || order.shipping_address || {};
                const city = typeof shippingAddr === 'object' ? shippingAddr.city : null;
                
                return (
                  <div key={order.id} className="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                    <div className="flex-1">
                      <p className="font-medium">Order #{order.id || order.orderId}</p>
                      <p className="text-sm text-gray-600">{order.user_name || order.customer_name}</p>
                      {city && (
                        <p className="text-sm text-gray-500 flex items-center gap-1 mt-1">
                          <MapPin className="w-3 h-3" />
                          {city}
                        </p>
                      )}
                    </div>
                    <div className="text-right">
                      <p className="font-medium">₹{parseFloat(order.total_amount || order.totalAmount || 0).toLocaleString()}</p>
                      <p className="text-sm text-gray-600 capitalize">{order.status || order.orderStatus}</p>
                    </div>
                  </div>
                );
              })}
            </div>
          </CardContent>
        </Card>
      )}
    </div>
  );
};

export default DashboardStats;
