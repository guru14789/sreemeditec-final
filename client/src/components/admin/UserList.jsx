import React, { useState, useEffect } from 'react';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Badge } from '@/components/ui/badge';
import { Users, Shield, User as UserIcon } from 'lucide-react';
import { toast } from '@/components/ui/use-toast';
import { api } from '@/lib/api';

const UserList = () => {
  const [users, setUsers] = useState([]);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    fetchUsers();
  }, []);

  const fetchUsers = async () => {
    try {
      const response = await api.request('/admin/users');
      if (response.success) {
        setUsers(response.users || []);
      }
    } catch (error) {
      toast({
        title: "Error",
        description: "Failed to load users",
        variant: "destructive"
      });
    } finally {
      setLoading(false);
    }
  };

  const updateUserRole = async (uid, newRole) => {
    try {
      const response = await api.request('/admin/users', {
        method: 'PUT',
        body: JSON.stringify({ uid, role: newRole })
      });

      if (response.success) {
        toast({
          title: "Role updated",
          description: "User role updated successfully"
        });
        fetchUsers();
      }
    } catch (error) {
      toast({
        title: "Error",
        description: "Failed to update user role",
        variant: "destructive"
      });
    }
  };

  if (loading) {
    return (
      <Card>
        <CardContent className="py-10">
          <div className="flex items-center justify-center">
            <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-teal-600"></div>
          </div>
        </CardContent>
      </Card>
    );
  }

  return (
    <Card>
      <CardHeader>
        <div className="flex items-center justify-between">
          <div>
            <CardTitle>Users</CardTitle>
            <CardDescription>Manage registered customers and admins</CardDescription>
          </div>
          <Badge variant="outline">
            <Users className="h-4 w-4 mr-1" />
            {users.length} Total Users
          </Badge>
        </div>
      </CardHeader>
      <CardContent>
        {users.length === 0 ? (
          <div className="text-center py-10 border-2 border-dashed rounded-lg">
            <Users className="mx-auto h-12 w-12 text-gray-400" />
            <p className="mt-2 text-gray-500">No users yet</p>
          </div>
        ) : (
          <div className="space-y-4">
            {users.map((user) => (
              <div key={user.uid || user.email} className="flex items-center justify-between p-4 border rounded-lg hover:bg-gray-50">
                <div className="flex items-center space-x-4 flex-1">
                  <div className="w-10 h-10 rounded-full bg-gradient-to-br from-teal-500 to-teal-600 flex items-center justify-center text-white font-semibold">
                    {(user.username || user.email)?.charAt(0).toUpperCase()}
                  </div>
                  <div className="flex-1">
                    <div className="flex items-center space-x-2">
                      <h4 className="font-semibold">{user.username || user.name || 'No name'}</h4>
                      <Badge
                        variant={user.role === 'admin' ? 'default' : 'secondary'}
                        className={user.role === 'admin' ? 'bg-teal-600' : ''}
                      >
                        {user.role === 'admin' ? (
                          <>
                            <Shield className="h-3 w-3 mr-1" />
                            Admin
                          </>
                        ) : (
                          <>
                            <UserIcon className="h-3 w-3 mr-1" />
                            User
                          </>
                        )}
                      </Badge>
                    </div>
                    <p className="text-sm text-gray-600">{user.email}</p>
                    {user.phone && (
                      <p className="text-sm text-gray-500">{user.phone}</p>
                    )}
                  </div>
                </div>
                <div className="flex items-center space-x-3">
                  <Select
                    value={user.role || 'user'}
                    onValueChange={(value) => updateUserRole(user.uid, value)}
                  >
                    <SelectTrigger className="w-[130px]">
                      <SelectValue />
                    </SelectTrigger>
                    <SelectContent>
                      <SelectItem value="user">User</SelectItem>
                      <SelectItem value="admin">Admin</SelectItem>
                    </SelectContent>
                  </Select>
                </div>
              </div>
            ))}
          </div>
        )}
      </CardContent>
    </Card>
  );
};

export default UserList;
