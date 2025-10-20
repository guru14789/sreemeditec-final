import React, { createContext, useContext, useState, useEffect } from 'react';
import { toast } from '@/components/ui/use-toast';
import { api } from '@/lib/api';
import { jwtDecode } from 'jwt-decode';

const AuthContext = createContext();

export const useAuth = () => {
  const context = useContext(AuthContext);
  if (!context) {
    throw new Error('useAuth must be used within an AuthProvider');
  }
  return context;
};

export const AuthProvider = ({ children }) => {
  const [user, setUser] = useState(null);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    loadUser();
  }, []);

  const loadUser = async () => {
    const token = api.getToken();
    if (token) {
      try {
        // Fetch fresh user data from API to get updated role
        const response = await api.getProfile();
        if (response.success && response.user) {
          setUser(response.user);
        } else {
          // Fallback to JWT decode if API fails
          const decoded = jwtDecode(token);
          setUser(decoded.data || decoded.user || decoded);
        }
      } catch (error) {
        console.error('Error loading user:', error);
        // If token is invalid, clear it
        if (error.message?.includes('token') || error.message?.includes('401')) {
          api.removeToken();
        }
      }
    }
    setLoading(false);
  };

  const login = async (email, password) => {
    try {
      const response = await api.login(email, password);
      if (response.success && response.idToken) {
        api.setToken(response.idToken);
        // Store refresh token if available
        if (response.refreshToken) {
          localStorage.setItem('refreshToken', response.refreshToken);
        }
        // Use the user data from response which includes role
        setUser(response.user);
        toast({
          title: "Login successful",
          description: "Welcome back!",
        });
        return { success: true };
      }
      throw new Error(response.message || 'Login failed');
    } catch (error) {
      return { success: false, error: error.message };
    }
  };

  const register = async (userData) => {
    try {
      const response = await api.register(userData);
      if (response.success && response.idToken) {
        api.setToken(response.idToken);
        // Store refresh token if available
        if (response.refreshToken) {
          localStorage.setItem('refreshToken', response.refreshToken);
        }
        // Fetch fresh user data from API to get complete profile with role
        const profileResponse = await api.getProfile();
        if (profileResponse.success && profileResponse.user) {
          setUser(profileResponse.user);
        } else {
          // Fallback: decode JWT if profile fetch fails
          const decoded = jwtDecode(response.idToken);
          setUser(decoded.data || decoded.user || decoded);
        }
        toast({
          title: "Registration successful",
          description: "Welcome to Sreemeditec!",
        });
        return { success: true };
      }
      throw new Error(response.message || 'Registration failed');
    } catch (error) {
      return { success: false, error: error.message };
    }
  };

  const logout = () => {
    api.logout();
    setUser(null);
    toast({
      title: "Logged out",
      description: "You have been successfully logged out.",
    });
  };

  const updateProfile = async (profileData) => {
    try {
      const response = await api.updateProfile(profileData);
      if (response.success) {
        // Update user in context with new data
        setUser(prev => ({ ...prev, ...profileData }));
        toast({
          title: "Profile updated",
          description: "Your profile has been updated successfully.",
        });
        return { success: true };
      } else {
        toast({
          title: "Update failed",
          description: response.errors?.join(', ') || "Profile update failed.",
          variant: "destructive",
        });
        return { success: false, error: response.errors?.join(', ') };
      }
    } catch (error) {
      toast({
        title: "Update failed",
        description: error.message,
        variant: "destructive",
      });
      return { success: false, error: error.message };
    }
  };

  const changePassword = async (currentPassword, newPassword) => {
    try {
      const response = await api.changePassword(currentPassword, newPassword);
      if (response.success) {
        toast({
          title: "Password changed",
          description: "Your password has been changed successfully.",
        });
        return { success: true };
      } else {
        toast({
          title: "Password change failed",
          description: response.errors?.join(', ') || "Password change failed.",
          variant: "destructive",
        });
        return { success: false, error: response.errors?.join(', ') };
      }
    } catch (error) {
      toast({
        title: "Password change failed",
        description: error.message,
        variant: "destructive",
      });
      return { success: false, error: error.message };
    }
  };

  const value = {
    user,
    login,
    register,
    logout,
    updateProfile,
    changePassword,
    loading,
    isAdmin: user ? user.role === 'admin' : false,
  };

  return (
    <AuthContext.Provider value={value}>
      {children}
    </AuthContext.Provider>
  );
};
