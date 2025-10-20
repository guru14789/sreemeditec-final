# Overview

This project is a comprehensive e-commerce backend system for Sree Meditec, a medical equipment company. It provides a production-ready, modular, secure, and scalable solution with features like user authentication, product management, order processing, Razorpay payment integration, DTDC courier integration, automated shipment creation, and an admin dashboard. The system aims to offer a complete e-commerce experience for medical equipment sales, including detailed product information, streamlined checkout, and robust administrative tools.

# User Preferences

Preferred communication style: Simple, everyday language.

# System Architecture

## Backend Architecture (PHP 8+)
- **Framework**: Plain PHP with Composer, MVC pattern, PSR-4 autoloading.
- **Authentication**: Session-based + JWT token authentication with automatic token refresh, Bcrypt/Argon2 hashing for passwords, Role-Based Access for User and Admin.
- **API**: RESTful with JSON-based responses, structured error handling, rate limiting.
- **File Uploads**: Local file storage in `/uploads/` for images, with type and size validation.

## Database Design
- **Primary Database**: Firebase/Firestore for flexible document storage and real-time capabilities.
- **Collections**: `users`, `products`, `orders`, `cart`, `payments`, `shipments`, `quotes`, `contacts`.
- **Data Modeling**: Document-based approach.

## Frontend Architecture (React)
- **Framework**: React 18 with modern hooks, Vite build tool.
- **Styling**: Tailwind CSS with a custom design system, responsive typography, touch-optimized interactions, mobile-optimized components.
- **State Management**: React hooks.
- **Routing**: React Router.
- **UI/UX**: Image fallback for broken images.

## E-commerce Features
- **Product Management**: Full CRUD operations with enhanced admin UI (Table/Grid view, filtering, categories, stock indicators, sortable columns, image management). Products include comprehensive details: warranty info, shipping info, return policy, key features list, technical specifications, original price, and reviews count.
- **Shopping Cart**: Persistent system linked to user accounts.
- **Order Processing**: Complete lifecycle management, filtered to show only successfully paid orders for admin.
- **Inventory Tracking**: Stock management.
- **Checkout Flow**: Streamlined process, direct redirection to Razorpay.
- **Quote Request System**: Allows both authenticated users and guests to submit quote requests; admins can manage quotes.
- **Contact Form Management**: Backend API for storing contact form submissions; admins can manage and view submissions.

## Admin Dashboard
- **Features**: Order, user, product, quote, and contact message management (CRUD), analytics (revenue, order statistics, payment methods), role-based access.
- **Data Handling**: Backend-to-frontend data normalization (snake_case to camelCase), type safety for monetary values, detailed payment statistics (today's revenue, AOV, payment methods pie chart), revenue overview timeline.
- **Order Display**: Enhanced with customer name, email, phone, and detailed location information.

# External Dependencies

## Payment Processing
- **Razorpay Integration**: Complete payment gateway integration for the Indian market, including payment order creation, verification, webhook handling, and server-side amount validation. Dynamic SDK loading and iframe fallback solution for compatibility.

## Shipping & Logistics
- **DTDC Integration**: Courier service integration via Shipsy API for automated order fulfillment. Features include automated shipment creation after payment, real-time tracking, label generation, and cancellation.

## Email Services
- **PHPMailer**: SMTP-based email sending for notifications (order confirmations, password resets).

## Development Tools
- **Composer**: PHP dependency management.
- **Environment Configuration**: `.env` file.
- **Third-Party Libraries**: Firebase JWT, Ramsey UUID.