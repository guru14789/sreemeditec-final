const API_BASE_URL = '/api';

class ApiClient {
  constructor() {
    this.baseURL = API_BASE_URL;
    this.migrateTokens();
  }

  migrateTokens() {
    // Migration: If user has old token without refresh token, clear it
    const token = this.getToken();
    const refreshToken = this.getRefreshToken();
    
    if (token && !refreshToken) {
      console.log('🔄 Migrating to refresh token system - clearing old token');
      this.removeToken();
    }
  }

  getToken() {
    return localStorage.getItem('token');
  }

  setToken(token) {
    localStorage.setItem('token', token);
  }

  removeToken() {
    localStorage.removeItem('token');
    localStorage.removeItem('refreshToken');
  }

  getRefreshToken() {
    return localStorage.getItem('refreshToken');
  }

  setRefreshToken(refreshToken) {
    if (refreshToken) {
      localStorage.setItem('refreshToken', refreshToken);
    }
  }

  async refreshAccessToken() {
    const refreshToken = this.getRefreshToken();
    if (!refreshToken) {
      throw new Error('No refresh token available');
    }

    const response = await fetch(`${this.baseURL}/auth/refresh`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({ refreshToken }),
    });

    const data = await response.json();

    if (!response.ok || !data.success) {
      throw new Error('Token refresh failed');
    }

    this.setToken(data.idToken);
    this.setRefreshToken(data.refreshToken);

    return data.idToken;
  }

  async request(endpoint, options = {}, retryCount = 0) {
    const url = `${this.baseURL}${endpoint}`;
    const token = this.getToken();

    const config = {
      headers: {
        'Content-Type': 'application/json',
        ...(token && { 'Authorization': `Bearer ${token}` }),
        ...options.headers,
      },
      ...options,
    };

    try {
      console.log(`Making request to: ${url}`);
      const response = await fetch(url, config);
      
      const contentType = response.headers.get('content-type') || '';
      
      let data;
      let isJson = contentType.includes('application/json');
      
      if (isJson) {
        data = await response.json();
      } else {
        const textResponse = await response.text();
        
        console.error('⚠️ Backend returned non-JSON response:');
        console.error('Status:', response.status, response.statusText);
        console.error('Content-Type:', contentType);
        console.error('Response preview:', textResponse.substring(0, 500));
        
        const errorMatch = textResponse.match(/<b>(.*?)<\/b>/);
        const backendError = errorMatch ? errorMatch[1] : 'Backend returned an error page';
        
        data = {
          success: false,
          errors: [
            `Backend error: ${backendError}`,
            'This indicates a server configuration issue. Check the backend logs.'
          ],
          _rawHtml: textResponse,
        };
      }

      if (!response.ok) {
        // Handle 401 Unauthorized with token refresh
        if (response.status === 401 && retryCount === 0 && this.getRefreshToken()) {
          console.log('🔄 Token expired, attempting refresh...');
          try {
            await this.refreshAccessToken();
            console.log('✅ Token refreshed successfully, retrying request...');
            // Retry the original request with the new token
            return await this.request(endpoint, options, retryCount + 1);
          } catch (refreshError) {
            console.error('❌ Token refresh failed:', refreshError);
            this.removeToken();
            window.location.href = '/login';
            throw new Error('Session expired. Please log in again.');
          }
        }

        const errorMessage = data.errors?.[0] || data.error || data.msg || 
                           `Request failed: ${response.status} ${response.statusText}`;
        
        console.error(`❌ Request failed: ${response.status} ${response.statusText}`, data);
        throw new Error(errorMessage);
      }

      return data;
    } catch (error) {
      console.error('💥 API request error:', error);
      
      if (error.message?.includes('JSON')) {
        throw new Error('Server returned an invalid response. Please check your backend configuration.');
      }
      
      throw error;
    }
  }

  async login(email, password) {
    const response = await this.request('/auth/login', {
      method: 'POST',
      body: JSON.stringify({ email, password }),
    });

    if (response.idToken) {
      this.setToken(response.idToken);
      this.setRefreshToken(response.refreshToken);
    }

    return response;
  }

  async register(userData) {
    const response = await this.request('/auth/register', {
      method: 'POST',
      body: JSON.stringify(userData),
    });

    if (response.idToken) {
      this.setToken(response.idToken);
      this.setRefreshToken(response.refreshToken);
    }

    return response;
  }

  logout() {
    this.removeToken();
  }

  async getProfile() {
    const response = await this.request('/user/profile');
    return response;
  }

  async updateProfile(userData) {
    const response = await this.request('/user/profile', {
      method: 'PUT',
      body: JSON.stringify(userData),
    });
    return response;
  }

  async changePassword(currentPassword, newPassword) {
    const response = await this.request('/user/change-password', {
      method: 'PUT',
      body: JSON.stringify({ currentPassword, newPassword }),
    });
    return response;
  }

  async getOrders() {
    const response = await this.request('/orders');
    return response;
  }

  async getOrderById(orderId) {
    const response = await this.request(`/orders/${orderId}`);
    return response;
  }

  async getAllOrders() {
    const response = await this.request('/orders/all');
    return response;
  }

  async createOrder(orderData) {
    const response = await this.request('/orders', {
      method: 'POST',
      body: JSON.stringify(orderData),
    });
    return response;
  }

  async updateOrderStatus(orderId, status) {
    const response = await this.request(`/orders/${orderId}`, {
      method: 'PUT',
      body: JSON.stringify({ status }),
    });
    return response;
  }

  async getProducts(filters = {}) {
    const params = new URLSearchParams(filters);
    const response = await this.request(`/products?${params}`);
    return response;
  }

  async getProduct(productId) {
    const response = await this.request(`/products/${productId}`);
    return response;
  }

  async createProduct(productData) {
    const response = await this.request('/products', {
      method: 'POST',
      body: JSON.stringify(productData),
    });
    return response;
  }

  async updateProduct(productId, productData) {
    const response = await this.request(`/products/${productId}`, {
      method: 'PUT',
      body: JSON.stringify(productData),
    });
    return response;
  }

  async deleteProduct(productId) {
    const response = await this.request(`/products/${productId}`, {
      method: 'DELETE',
    });
    return response;
  }

  async getUsers() {
    const response = await this.request('/users');
    return response;
  }

  async createPaymentOrder(orderData) {
    const response = await this.request('/payment/create-order', {
      method: 'POST',
      body: JSON.stringify(orderData),
    });
    return response;
  }

  async verifyPayment(paymentData) {
    const response = await this.request('/payment/verify', {
      method: 'POST',
      body: JSON.stringify(paymentData),
    });
    return response;
  }

  async createShipment(shipmentData) {
    const response = await this.request('/shipment/create', {
      method: 'POST',
      body: JSON.stringify(shipmentData),
    });
    return response;
  }

  async trackShipment(awbNumber) {
    const response = await this.request(`/shipment/track/${awbNumber}`);
    return response;
  }

  async getShipmentByOrder(orderId) {
    const response = await this.request(`/shipment/order/${orderId}`);
    return response;
  }

  async getUserShipments() {
    const response = await this.request('/shipments');
    return response;
  }

  async createQuote(quoteData) {
    const response = await this.request('/quotes', {
      method: 'POST',
      body: JSON.stringify(quoteData),
    });
    return response;
  }

  async getUserQuotes() {
    const response = await this.request('/quotes/user');
    return response;
  }

  async getAllQuotes() {
    const response = await this.request('/quotes/all');
    return response;
  }

  async getQuoteById(quoteId) {
    const response = await this.request(`/quotes/${quoteId}`);
    return response;
  }

  async updateQuoteStatus(quoteId, status) {
    const response = await this.request(`/quotes/${quoteId}/status`, {
      method: 'PUT',
      body: JSON.stringify({ status }),
    });
    return response;
  }

  async deleteQuote(quoteId) {
    const response = await this.request(`/quotes/${quoteId}`, {
      method: 'DELETE',
    });
    return response;
  }

  async createContact(contactData) {
    const response = await this.request('/contacts', {
      method: 'POST',
      body: JSON.stringify(contactData),
    });
    return response;
  }

  async getAllContacts() {
    const response = await this.request('/contacts/all');
    return response;
  }

  async getContactById(contactId) {
    const response = await this.request(`/contacts/${contactId}`);
    return response;
  }

  async updateContactStatus(contactId, status) {
    const response = await this.request(`/contacts/${contactId}/status`, {
      method: 'PUT',
      body: JSON.stringify({ status }),
    });
    return response;
  }

  async deleteContact(contactId) {
    const response = await this.request(`/contacts/${contactId}`, {
      method: 'DELETE',
    });
    return response;
  }
}

export const api = new ApiClient();
export default api;
