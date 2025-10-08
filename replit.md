# Overview

This is a comprehensive e-commerce backend system for Sree Meditec, a medical equipment company. The project consists of a PHP 8+ backend with Firebase/Firestore as the primary database and a React frontend client. The backend is designed to be production-ready, modular, secure, and scalable, providing a complete e-commerce solution with features like user authentication, product management, order processing, Razorpay payment integration, DTDC courier integration, automated shipment creation, and admin dashboard capabilities.

## Recent Updates (October 2025)

### Razorpay Payment Integration
- Complete Razorpay payment gateway integration with test and production mode support
- Payment order creation and verification with signature validation
- Webhook handler for automatic payment status updates
- Secure payment flow: Create Order → Process Payment → Verify → Auto-create Shipment

### DTDC Courier API Integration  
- Automated shipment creation after successful payment
- Real-time shipment tracking using AWB numbers
- Label generation and storage in database
- Integration with DTDC Shipsy API for courier management

### Direct-To-Database (DTD) Pipeline
- Seamless flow from payment to shipment creation
- Automatic shipment creation upon payment verification
- Order status updates reflecting payment and shipment states
- Shipment details stored with order information for tracking

# User Preferences

Preferred communication style: Simple, everyday language.

# System Architecture

## Backend Architecture (PHP 8+)
- **Framework**: Plain PHP with Composer dependency management
- **Structure**: MVC pattern with PSR-4 autoloading
- **Entry Point**: Single entry point through `public/` directory
- **Dependencies**: Core libraries include MongoDB driver, JWT authentication, PHPMailer, and environment configuration management

## Database Design
- **Primary Database**: Firebase/Firestore for flexible document storage and real-time capabilities
- **Collections**: users, products, orders, cart, payments, shipments
- **Connection**: Centralized Firebase connection through configuration layer
- **Data Modeling**: Document-based approach suitable for product catalogs, user profiles, payments, and shipment tracking

## Authentication & Security
- **Dual Authentication**: Session-based + JWT token authentication
- **Password Security**: Bcrypt/Argon2 hashing for user passwords
- **Role-Based Access**: User and Admin role differentiation
- **Input Validation**: Server-side validation and sanitization

## API Structure
- **RESTful Design**: Standard HTTP methods for CRUD operations
- **Response Format**: JSON-based API responses
- **Error Handling**: Structured error responses with appropriate HTTP status codes
- **Rate Limiting**: Built-in protection against abuse

## Frontend Architecture (React)
- **Framework**: React 18 with modern hooks
- **Build Tool**: Vite for fast development and optimized builds
- **Styling**: Tailwind CSS with custom design system
- **State Management**: React hooks for local state management
- **Routing**: React Router for client-side navigation

## File Upload & Storage
- **Image Handling**: Local file storage in `/uploads/` directory
- **Profile Pictures**: User avatar upload and management
- **Product Images**: Multiple image support for products
- **File Validation**: Type and size restrictions for security

## E-commerce Features
- **Product Management**: Full CRUD operations for products and categories
- **Shopping Cart**: Persistent cart system linked to user accounts
- **Order Processing**: Complete order lifecycle management
- **Inventory Tracking**: Stock management and availability checking

# External Dependencies

## Payment Processing
- **Razorpay Integration**: Complete payment gateway integration for Indian market
- **API Endpoints**: 
  - POST `/api/payment/create-order` - Create Razorpay order
  - POST `/api/payment/verify` - Verify payment signature and auto-create shipment
  - POST `/api/payment/webhook` - Handle Razorpay webhooks
  - GET `/api/payment/order/{orderId}` - Get payment details by order
- **Webhook Handling**: Automatic order status updates via Razorpay webhooks with HMAC signature verification
- **Payment Security**: Server-side payment verification, signature validation, and secure credential storage

## Shipping & Logistics
- **DTDC Integration**: Courier service integration for automated order fulfillment
- **API Endpoints**:
  - POST `/api/shipment/create` - Create shipment with DTDC
  - GET `/api/shipment/track/{awbNumber}` - Track shipment status
  - GET `/api/shipment/order/{orderId}` - Get shipment by order ID
  - GET `/api/shipments` - Get all user shipments
- **Tracking System**: Real-time order tracking with AWB numbers and status updates
- **Label Management**: Automatic label generation and storage
- **Auto-shipment**: Shipments automatically created after successful payment verification

## Email Services
- **PHPMailer**: SMTP-based email sending
- **Notification System**: Order confirmations, password reset, and user communications
- **Template System**: Email template management for consistent branding

## Development Tools
- **Composer**: PHP dependency management
- **Environment Configuration**: `.env` file for secure configuration management
- **Database Abstraction**: MongoDB PHP driver for database operations
- **Session Management**: PHP sessions with secure configuration

## Third-Party Libraries
- **JWT Library**: Firebase JWT for token-based authentication
- **UUID Generation**: Ramsey UUID for unique identifier generation
- **Testing Framework**: PHPUnit for backend testing
- **Code Quality**: Various development dependencies for code analysis and formatting