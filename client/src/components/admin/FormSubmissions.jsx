import React, { useState, useEffect } from 'react';
import { motion } from 'framer-motion';
import {
  MessageSquare, FileText, Mail, Phone, Building2, Calendar, Eye, Trash2, CheckCircle, Clock, XCircle
} from 'lucide-react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogHeader,
  DialogTitle,
} from '@/components/ui/dialog';
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select';
import { toast } from '@/components/ui/use-toast';
import { api } from '@/lib/api';

const FormSubmissions = () => {
  const [contacts, setContacts] = useState([]);
  const [quotes, setQuotes] = useState([]);
  const [loading, setLoading] = useState(true);
  const [selectedItem, setSelectedItem] = useState(null);
  const [dialogOpen, setDialogOpen] = useState(false);

  useEffect(() => {
    loadData();
  }, []);

  const loadData = async () => {
    setLoading(true);
    try {
      const [contactsRes, quotesRes] = await Promise.all([
        api.getAllContacts(),
        api.getAllQuotes()
      ]);

      if (contactsRes.success) {
        setContacts(contactsRes.contacts || []);
      }
      if (quotesRes.success) {
        setQuotes(quotesRes.quotes || []);
      }
    } catch (error) {
      toast({
        title: "Error loading data",
        description: error.message,
        variant: "destructive",
      });
    } finally {
      setLoading(false);
    }
  };

  const getStatusBadge = (status) => {
    const statusConfig = {
      new: { color: 'bg-blue-100 text-blue-800', icon: Clock },
      pending: { color: 'bg-yellow-100 text-yellow-800', icon: Clock },
      completed: { color: 'bg-green-100 text-green-800', icon: CheckCircle },
      contacted: { color: 'bg-green-100 text-green-800', icon: CheckCircle },
      rejected: { color: 'bg-red-100 text-red-800', icon: XCircle },
    };

    const config = statusConfig[status?.toLowerCase()] || statusConfig.new;
    const Icon = config.icon;

    return (
      <Badge className={`${config.color} border-0`}>
        <Icon className="w-3 h-3 mr-1" />
        {status}
      </Badge>
    );
  };

  const handleStatusChange = async (id, newStatus, type) => {
    try {
      const response = type === 'contact'
        ? await api.updateContactStatus(id, newStatus)
        : await api.updateQuoteStatus(id, newStatus);

      if (response.success) {
        toast({
          title: "Status updated",
          description: `${type} status updated successfully`,
        });
        loadData();
        setDialogOpen(false);
      } else {
        throw new Error(response.errors?.join(', ') || 'Update failed');
      }
    } catch (error) {
      toast({
        title: "Update failed",
        description: error.message,
        variant: "destructive",
      });
    }
  };

  const handleDelete = async (id, type) => {
    if (!confirm(`Are you sure you want to delete this ${type}?`)) return;

    try {
      const response = type === 'contact'
        ? await api.deleteContact(id)
        : await api.deleteQuote(id);

      if (response.success) {
        toast({
          title: "Deleted",
          description: `${type} deleted successfully`,
        });
        loadData();
      } else {
        throw new Error(response.errors?.join(', ') || 'Delete failed');
      }
    } catch (error) {
      toast({
        title: "Delete failed",
        description: error.message,
        variant: "destructive",
      });
    }
  };

  const viewDetails = (item, type) => {
    setSelectedItem({ ...item, type });
    setDialogOpen(true);
  };

  const formatDate = (dateObj) => {
    if (!dateObj) return 'N/A';
    const date = dateObj.date ? new Date(dateObj.date) : new Date(dateObj);
    return date.toLocaleDateString('en-IN', {
      year: 'numeric',
      month: 'short',
      day: 'numeric',
      hour: '2-digit',
      minute: '2-digit'
    });
  };

  if (loading) {
    return (
      <div className="flex items-center justify-center h-64">
        <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-[#1d7d69]"></div>
      </div>
    );
  }

  return (
    <div className="space-y-6">
      <div>
        <h2 className="text-3xl font-bold text-gray-900">Form Submissions</h2>
        <p className="text-gray-500 mt-2">View and manage all form submissions from your website</p>
      </div>

      <Tabs defaultValue="contacts" className="space-y-6">
        <TabsList>
          <TabsTrigger value="contacts" className="flex items-center gap-2">
            <MessageSquare className="w-4 h-4" />
            Contact Messages ({contacts.length})
          </TabsTrigger>
          <TabsTrigger value="quotes" className="flex items-center gap-2">
            <FileText className="w-4 h-4" />
            Quote Requests ({quotes.length})
          </TabsTrigger>
        </TabsList>

        <TabsContent value="contacts" className="space-y-4">
          {contacts.length === 0 ? (
            <Card>
              <CardContent className="p-12 text-center">
                <MessageSquare className="w-16 h-16 mx-auto text-gray-400 mb-4" />
                <p className="text-gray-500">No contact messages yet</p>
              </CardContent>
            </Card>
          ) : (
            <div className="grid gap-4">
              {contacts.map((contact) => (
                <motion.div
                  key={contact.id}
                  initial={{ opacity: 0, y: 20 }}
                  animate={{ opacity: 1, y: 0 }}
                >
                  <Card className="hover:shadow-md transition-shadow">
                    <CardContent className="p-6">
                      <div className="flex items-start justify-between">
                        <div className="flex-1 space-y-3">
                          <div className="flex items-center justify-between">
                            <h3 className="text-lg font-semibold text-gray-900">{contact.name}</h3>
                            {getStatusBadge(contact.status)}
                          </div>
                          
                          <div className="grid md:grid-cols-2 gap-2 text-sm">
                            <div className="flex items-center gap-2 text-gray-600">
                              <Mail className="w-4 h-4" />
                              {contact.email}
                            </div>
                            {contact.phone && (
                              <div className="flex items-center gap-2 text-gray-600">
                                <Phone className="w-4 h-4" />
                                {contact.phone}
                              </div>
                            )}
                            {contact.company && (
                              <div className="flex items-center gap-2 text-gray-600">
                                <Building2 className="w-4 h-4" />
                                {contact.company}
                              </div>
                            )}
                            <div className="flex items-center gap-2 text-gray-600">
                              <Calendar className="w-4 h-4" />
                              {formatDate(contact.createdAt)}
                            </div>
                          </div>

                          {contact.service && (
                            <div className="text-sm text-gray-600">
                              <span className="font-medium">Service:</span> {contact.service}
                            </div>
                          )}

                          <p className="text-gray-700 line-clamp-2">{contact.message}</p>
                        </div>

                        <div className="flex gap-2 ml-4">
                          <Button
                            variant="outline"
                            size="sm"
                            onClick={() => viewDetails(contact, 'contact')}
                          >
                            <Eye className="w-4 h-4" />
                          </Button>
                          <Button
                            variant="outline"
                            size="sm"
                            onClick={() => handleDelete(contact.id, 'contact')}
                          >
                            <Trash2 className="w-4 h-4 text-red-500" />
                          </Button>
                        </div>
                      </div>
                    </CardContent>
                  </Card>
                </motion.div>
              ))}
            </div>
          )}
        </TabsContent>

        <TabsContent value="quotes" className="space-y-4">
          {quotes.length === 0 ? (
            <Card>
              <CardContent className="p-12 text-center">
                <FileText className="w-16 h-16 mx-auto text-gray-400 mb-4" />
                <p className="text-gray-500">No quote requests yet</p>
              </CardContent>
            </Card>
          ) : (
            <div className="grid gap-4">
              {quotes.map((quote) => (
                <motion.div
                  key={quote.id}
                  initial={{ opacity: 0, y: 20 }}
                  animate={{ opacity: 1, y: 0 }}
                >
                  <Card className="hover:shadow-md transition-shadow">
                    <CardContent className="p-6">
                      <div className="flex items-start justify-between">
                        <div className="flex-1 space-y-3">
                          <div className="flex items-center justify-between">
                            <h3 className="text-lg font-semibold text-gray-900">{quote.name}</h3>
                            {getStatusBadge(quote.status)}
                          </div>
                          
                          <div className="grid md:grid-cols-2 gap-2 text-sm">
                            <div className="flex items-center gap-2 text-gray-600">
                              <Mail className="w-4 h-4" />
                              {quote.email}
                            </div>
                            {quote.phone && (
                              <div className="flex items-center gap-2 text-gray-600">
                                <Phone className="w-4 h-4" />
                                {quote.phone}
                              </div>
                            )}
                            {quote.company && (
                              <div className="flex items-center gap-2 text-gray-600">
                                <Building2 className="w-4 h-4" />
                                {quote.company}
                              </div>
                            )}
                            <div className="flex items-center gap-2 text-gray-600">
                              <Calendar className="w-4 h-4" />
                              {formatDate(quote.createdAt)}
                            </div>
                          </div>

                          <div className="grid md:grid-cols-3 gap-2 text-sm">
                            <div>
                              <span className="font-medium">Equipment:</span> {quote.equipmentType}
                            </div>
                            <div>
                              <span className="font-medium">Quantity:</span> {quote.quantity}
                            </div>
                            <div>
                              <span className="font-medium">Budget:</span> {quote.budget}
                            </div>
                          </div>

                          <p className="text-gray-700 line-clamp-2">{quote.requirements}</p>
                        </div>

                        <div className="flex gap-2 ml-4">
                          <Button
                            variant="outline"
                            size="sm"
                            onClick={() => viewDetails(quote, 'quote')}
                          >
                            <Eye className="w-4 h-4" />
                          </Button>
                          <Button
                            variant="outline"
                            size="sm"
                            onClick={() => handleDelete(quote.id, 'quote')}
                          >
                            <Trash2 className="w-4 h-4 text-red-500" />
                          </Button>
                        </div>
                      </div>
                    </CardContent>
                  </Card>
                </motion.div>
              ))}
            </div>
          )}
        </TabsContent>
      </Tabs>

      {/* Details Dialog */}
      <Dialog open={dialogOpen} onOpenChange={setDialogOpen}>
        <DialogContent className="max-w-2xl max-h-[90vh] overflow-y-auto">
          <DialogHeader>
            <DialogTitle>
              {selectedItem?.type === 'contact' ? 'Contact Message Details' : 'Quote Request Details'}
            </DialogTitle>
            <DialogDescription>
              Submitted on {selectedItem && formatDate(selectedItem.createdAt)}
            </DialogDescription>
          </DialogHeader>

          {selectedItem && (
            <div className="space-y-6">
              <div className="grid md:grid-cols-2 gap-4">
                <div>
                  <label className="text-sm font-medium text-gray-500">Name</label>
                  <p className="text-gray-900">{selectedItem.name}</p>
                </div>
                <div>
                  <label className="text-sm font-medium text-gray-500">Email</label>
                  <p className="text-gray-900">{selectedItem.email}</p>
                </div>
                {selectedItem.phone && (
                  <div>
                    <label className="text-sm font-medium text-gray-500">Phone</label>
                    <p className="text-gray-900">{selectedItem.phone}</p>
                  </div>
                )}
                {selectedItem.company && (
                  <div>
                    <label className="text-sm font-medium text-gray-500">Company</label>
                    <p className="text-gray-900">{selectedItem.company}</p>
                  </div>
                )}
              </div>

              {selectedItem.type === 'quote' && (
                <div className="grid md:grid-cols-3 gap-4">
                  <div>
                    <label className="text-sm font-medium text-gray-500">Equipment Type</label>
                    <p className="text-gray-900">{selectedItem.equipmentType}</p>
                  </div>
                  <div>
                    <label className="text-sm font-medium text-gray-500">Quantity</label>
                    <p className="text-gray-900">{selectedItem.quantity}</p>
                  </div>
                  <div>
                    <label className="text-sm font-medium text-gray-500">Budget</label>
                    <p className="text-gray-900">{selectedItem.budget}</p>
                  </div>
                  {selectedItem.timeline && (
                    <div className="md:col-span-3">
                      <label className="text-sm font-medium text-gray-500">Timeline</label>
                      <p className="text-gray-900">{selectedItem.timeline}</p>
                    </div>
                  )}
                </div>
              )}

              {selectedItem.service && (
                <div>
                  <label className="text-sm font-medium text-gray-500">Service</label>
                  <p className="text-gray-900">{selectedItem.service}</p>
                </div>
              )}

              <div>
                <label className="text-sm font-medium text-gray-500">
                  {selectedItem.type === 'quote' ? 'Requirements' : 'Message'}
                </label>
                <p className="text-gray-900 whitespace-pre-wrap">
                  {selectedItem.type === 'quote' ? selectedItem.requirements : selectedItem.message}
                </p>
              </div>

              {selectedItem.type === 'quote' && selectedItem.additionalInfo && (
                <div>
                  <label className="text-sm font-medium text-gray-500">Additional Information</label>
                  <p className="text-gray-900 whitespace-pre-wrap">{selectedItem.additionalInfo}</p>
                </div>
              )}

              <div className="flex items-center gap-4 pt-4 border-t">
                <label className="text-sm font-medium text-gray-500">Status</label>
                <Select
                  value={selectedItem.status}
                  onValueChange={(value) => handleStatusChange(selectedItem.id, value, selectedItem.type)}
                >
                  <SelectTrigger className="w-40">
                    <SelectValue />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem value="new">New</SelectItem>
                    <SelectItem value="pending">Pending</SelectItem>
                    <SelectItem value="contacted">Contacted</SelectItem>
                    <SelectItem value="completed">Completed</SelectItem>
                    <SelectItem value="rejected">Rejected</SelectItem>
                  </SelectContent>
                </Select>
              </div>
            </div>
          )}
        </DialogContent>
      </Dialog>
    </div>
  );
};

export default FormSubmissions;
