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

  const loadUser = () => {
    const token = api.getToken();
    if (token) {
      try {
        const decoded = jwtDecode(token);
        setUser(decoded.data || decoded.user || decoded);
      } catch (error) {
        console.error('Invalid token:', error);
        api.removeToken();
      }
    }
    setLoading(false);
  };

  const login = async (email, password) => {
    try {
      await api.login(email, password);
      loadUser();
      toast({
        title: "Login successful",
        description: "Welcome back!",
      });
      return { success: true };
    } catch (error) {
      return { success: false, error: error.message };
    }
  };

  const register = async (userData) => {
    try {
      await api.register(userData);
      loadUser();
      toast({
        title: "Registration successful",
        description: "Welcome to Sreemeditec!",
      });
      return { success: true };
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
