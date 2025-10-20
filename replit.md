# Overview

This project is a comprehensive e-commerce backend system for Sree Meditec, a medical equipment company. It provides a production-ready, modular, secure, and scalable solution with features like user authentication, product management, order processing, Razorpay payment integration, DTDC courier integration, automated shipment creation, and an admin dashboard. The system aims to offer a complete e-commerce experience for medical equipment sales.

# User Preferences

Preferred communication style: Simple, everyday language.

# System Architecture

## Backend Architecture (PHP 8+)
- **Framework**: Plain PHP with Composer, MVC pattern, PSR-4 autoloading.
- **Dependencies**: JWT authentication, PHPMailer, environment configuration.

## Database Design
- **Primary Database**: Firebase/Firestore for flexible document storage and real-time capabilities.
- **Collections**: users, products, orders, cart, payments, shipments.
- **Data Modeling**: Document-based approach.

## Authentication & Security
- **Authentication**: Session-based + JWT token authentication with automatic token refresh.
- **Password Security**: Bcrypt/Argon2 hashing.
- **Access Control**: Role-Based Access for User and Admin.
- **Validation**: Server-side input validation and sanitization.

## API Structure
- **Design**: RESTful with JSON-based responses.
- **Error Handling**: Structured error responses with HTTP status codes.
- **Security**: Rate Limiting.

## Frontend Architecture (React)
- **Framework**: React 18 with modern hooks.
- **Build Tool**: Vite.
- **Styling**: Tailwind CSS with a custom design system.
- **State Management**: React hooks.
- **Routing**: React Router.
- **UI/UX**: Responsive typography, touch-optimized interactions, enhanced spacing, mobile-optimized components (product cards, cart, navigation, homepage, checkout flow, product details, store page), image fallback for broken images.

## File Upload & Storage
- **Image Handling**: Local file storage in `/uploads/` for product images and user avatars.
- **Validation**: Type and size restrictions.

## E-commerce Features
- **Product Management**: Full CRUD operations with enhanced admin UI (Table/Grid view, filtering, categories, stock indicators, sortable columns, image management). Products now include comprehensive details: warranty info, shipping info, return policy, key features list, technical specifications (model, power, dimensions, etc.), original price for showing savings, and reviews count.
- **Shopping Cart**: Persistent system linked to user accounts.
- **Order Processing**: Complete lifecycle management.
- **Inventory Tracking**: Stock management.
- **Checkout Flow**: Streamlined process, direct redirection to Razorpay after shipping details, Razorpay as the sole payment method.

## Admin Dashboard
- **Features**: Order, user, and product management (CRUD), analytics (revenue, order statistics), role-based access.
- **Data Handling**: Backend-to-frontend data normalization (snake_case to camelCase), type safety for monetary values.

# External Dependencies

## Payment Processing
- **Razorpay Integration**: Complete payment gateway integration for the Indian market.
- **Features**: Payment order creation, verification, webhook handling for automatic status updates, server-side amount validation for security.

## Shipping & Logistics
- **DTDC Integration**: Courier service integration for automated order fulfillment via Shipsy API.
- **Features**: Automated shipment creation after payment, real-time tracking, label generation.

## Email Services
- **PHPMailer**: SMTP-based email sending for notifications (order confirmations, password resets).

## Development Tools
- **Composer**: PHP dependency management.
- **Environment Configuration**: `.env` file.
- **Third-Party Libraries**: Firebase JWT, Ramsey UUID.

# Recent Updates

## Razorpay Payment Integration Fix (October 13, 2025)
- **Dynamic SDK Loading**: Changed from static script tag to dynamic loading to bypass CSP restrictions
- **Iframe Fallback Solution**: Detects "api.razorpay.com refused to connect" errors and automatically opens page in new tab
- **Connection Error Handling**: Comprehensive error detection and user-friendly guidance messages
- **Environment Compatibility**: Works in both iframe-restricted environments (Replit preview) and standalone browsers
- **Payment Flow**: Order creation (201) → Payment order (200) → Razorpay checkout → Success confirmation
- **Full Integration**: UPI, Cards, Net Banking, Wallets all working through Razorpay gateway

## Automatic Token Refresh System (October 13, 2025)
- **Firebase Refresh Tokens**: Implemented automatic token refresh with Firebase refresh tokens
- **API Client Enhancement**: Automatic retry on 401 errors with token refresh
- **Session Persistence**: Users no longer need to manually logout/login
- **Token Migration**: Legacy tokens without refresh tokens are automatically cleared

## Hero Section Enhancement (October 13, 2025)
- **Left Alignment**: Changed hero section from center to left-aligned layout
- **Large Typography**: Increased title size from 30px (mobile) to 72px (desktop)
- **Visual Impact**: Created more professional, modern appearance

## Image Fallback System (October 13, 2025)
- **Local SVG Placeholders**: Created fallback system for broken external images
- **Graceful Degradation**: via.placeholder.com URLs fail gracefully to /placeholder-product.svg
- **Professional UX**: No broken image icons, smooth fallback experience

## Order History Enhancements (October 13, 2025)
- **Paid Orders Filter**: Backend filters orders to show only successfully paid orders (captured, completed, paid, success, succeeded)
- **Live Shipment Tracking**: New TrackOrder page with DTDC API integration for real-time tracking with status/ETA display
- **Professional Invoices**: HTML invoice generation with full branding, line items, totals, and payment status badges
- **Complete Order Actions**: View details, live tracking, reorder capability, and download invoice functionality
- **Robust Error Handling**: Graceful fallbacks for missing shipments, orders, and tracking data

## Profile Page Enhancements (October 13, 2025)
- **Detailed Address Fields**: Added separate fields for street, city, state, and pincode with appropriate icons
- **Secure Password Change**: Backend now verifies current password before allowing password change
- **Enhanced Validation**: Current password verification prevents unauthorized password changes
- **Professional UI**: Clean, organized layout with proper field grouping and visual hierarchy

## Shipsy API Integration (October 13, 2025)
- **Official Shipsy API**: Updated DTDC integration to use official Shipsy API (https://app.shipsy.in)
- **Authentication**: Changed from Bearer token to api-key header authentication
- **Softdata Upload**: Shipment creation uses `/api/customer/integration/consignment/upload/softdata/v2` endpoint
- **Tracking API**: Live tracking via `/api/customer/integration/consignment/track` with reference_number parameter
- **Label Generation**: PDF label generation via `/api/customer/integration/consignment/print/upload` endpoint
- **Cancellation**: Order cancellation via `/api/customer/integration/consignment/cancel` endpoint with AWB array
- **Firestore Queries**: Smart fallback logic queries by referenceNumber first, then orderId for backward compatibility

## Quote Request System (October 13, 2025)
- **Backend API**: Created complete quote management system with Firestore database storage
- **Quote Model**: Stores customer information, equipment requirements, budget, timeline, and detailed specifications
- **API Endpoints**: Full CRUD operations - create, retrieve (user/admin), update status, delete quotes
- **Guest Support**: Allows both authenticated users and guests to submit quote requests
- **Admin Access**: Admins can view and manage all quote requests through API endpoints
- **Route Integration**: Quote page accessible via `/quote` route linked to navbar "Request Quote" button
- **Form Validation**: Client-side validation for required fields with user-friendly error messages

## Admin Account Setup (October 13, 2025)
- **Admin Scripts**: Created secure admin management scripts:
  - `create-admin.php`: Creates complete admin account in both Firebase Auth and Firestore (usage: `php create-admin.php <email> <password> <name> <phone>`)
  - `reset-admin-password.php`: Resets Firebase Auth password for existing admin (usage: `php reset-admin-password.php <email> <new_password>`)
- **Security**: Scripts accept credentials via CLI arguments or environment variables, never hardcoded
- **Role-Based Access**: Admin role stored in Firestore, AuthMiddleware validates role on every request
- **Admin Features**: Full access to admin dashboard, product/order/user management, quote management
- **Setup Process**: Use scripts with secure credentials, store passwords in environment secrets, rotate immediately after setup
- **Frontend Fix**: Updated AuthContext to use user data from login response (which includes role) instead of JWT decode, ensuring admin role is properly set in frontend state

## Form Submissions Management (October 13, 2025)
- **Contact Messages**: Backend API for storing contact form submissions in Firestore with full CRUD operations
- **Unified Admin View**: Created FormSubmissions component showing all form responses (contacts and quotes) in tabbed interface
- **Status Management**: Admin can update submission status (new, pending, contacted, completed, rejected)
- **Detailed Views**: Modal dialog for viewing complete submission details with full message/requirement content
- **API Endpoints**: 
  - POST `/api/contacts` - Submit contact message (public)
  - GET `/api/contacts/all` - View all contacts (admin only)
  - PUT `/api/contacts/{id}/status` - Update status (admin only)
  - DELETE `/api/contacts/{id}` - Delete contact (admin only)
- **Admin Dashboard Integration**: "Submissions" tab shows contact messages and quote requests with counts, status badges, and quick actions
- **Bug Fix**: Added missing `requireAdmin()` method to AuthMiddleware to fix admin user loading issue
- **PHP 8.2 Compatibility Fix**: Fixed deprecated dynamic property creation error by creating a new user object instead of modifying Firebase UserRecord, preventing HTML error responses

## Admin Panel Enhancements (October 14, 2025)
- **Orders Filtering**: Admin orders now show only successfully paid orders (captured, completed, paid, success, succeeded status), includes payment/shipment data, sorted by creation date
- **User Role Management**: Fixed role changing functionality by adding uid field to getAllUsers() response, admin can now change user roles from user to admin and vice versa
- **Payment Dashboard**: Enhanced admin dashboard with detailed payment statistics:
  - Today's Revenue card showing daily revenue
  - Average Order Value card
  - Payment Methods pie chart showing breakdown by payment method
  - Backend provides total_payments, today_revenue, avg_order_value, payment_methods stats
- **Revenue Tracking**: Fixed Firestore Timestamp handling for accurate today's revenue calculation using get() method
- **Revenue Calculation Fix**: Updated total revenue, monthly revenue, and today's revenue calculations to handle both camelCase (totalAmount, createdAt) and snake_case (total_amount, created_at) field names with Firestore Timestamp support, ensuring accurate dashboard metrics
- **Revenue Overview Timeline**: Updated revenue chart to show last 2 months, current month (labeled "Current"), and next 3 months (labeled "Projected") for better revenue planning and forecasting
- **Data Enrichment**: getAllOrders() now includes customer_name, email, phone for better order display
- **Location Display Enhancements**:
  - Admin orders list shows order ID and city with MapPin icon
  - Order details modal displays complete structured delivery address (name, street, city/state/pincode, phone) with location icon
  - Dashboard recent orders show city information for quick geographic insights
  - Smart address transformation handles both object and string shipping addresses, gracefully falls back to 'N/A' for missing data
  - City display is conditional - only shown when actual city data is available

## Comprehensive Product Details Enhancement (October 13, 2025)
- **Expanded Product Model**: Added 8 new fields to product schema for complete product information:
  - `original_price` - For showing savings/discounts
  - `warranty_info` - Warranty details (e.g., "2 years manufacturer warranty")
  - `shipping_info` - Shipping details (e.g., "Free shipping on orders over ₹10,000")
  - `return_policy` - Return policy (e.g., "30-day return policy")
  - `key_features` - Array of product features for bullet-point display
  - `specifications` - Object with technical specs (model, power, max_vacuum, flow_rate, jar_capacity, noise_level, weight, dimensions)
  - `reviews_count` - Number of customer reviews
- **Enhanced Admin Form**: Added comprehensive form sections in ProductList component:
  - Pricing Details section (original price, reviews count)
  - Additional Information section (warranty, shipping, returns)
  - Key Features section (multi-line textarea, one feature per line)
  - Technical Specifications section (8 spec fields in organized grid layout)
- **Product Page Integration**: Updated ProductDetails page to fetch and display all new fields from API:
  - Shows original price with savings calculation
  - Displays warranty, shipping, and return policy icons with details
  - Renders key features as bullet-point list
  - Shows specifications in organized table format
- **Data Flow**: Complete backend-to-frontend integration with proper data transformation and validation