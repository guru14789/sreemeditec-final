# Sreemeditec E-Commerce - Local Development Setup Guide

This guide will help you run the Sreemeditec e-commerce project 100% locally on your machine using VS Code.

## 📋 Prerequisites

Before you begin, make sure you have the following installed on your system:

1. **PHP 8.1 or higher**
   - Download from: https://www.php.net/downloads
   - Verify installation: `php --version`

2. **Composer** (PHP Package Manager)
   - Download from: https://getcomposer.org/download/
   - Verify installation: `composer --version`

3. **Node.js 18 or higher** (includes npm)
   - Download from: https://nodejs.org/
   - Verify installation: `node --version` and `npm --version`

4. **VS Code**
   - Download from: https://code.visualstudio.com/

5. **Git**
   - Download from: https://git-scm.com/downloads
   - Verify installation: `git --version`

## 🚀 Quick Start

### Step 1: Clone or Open the Project

```bash
# If cloning from a repository
git clone <your-repo-url>
cd <project-directory>

# Or simply open the project folder in VS Code
code .
```

### Step 2: Install Backend Dependencies

```bash
cd php-backend
php composer.phar install
# Or if composer is globally installed:
composer install
cd ..
```

### Step 3: Install Frontend Dependencies

```bash
cd client
npm install
cd ..
```

### Step 4: Environment Configuration

**IMPORTANT**: You need to create and configure environment files with your own credentials.

#### Backend Environment Setup:

1. **Copy the example environment file:**
   ```bash
   cp php-backend/.env.example php-backend/.env
   ```

2. **Edit `php-backend/.env` and fill in your credentials:**
   - `SESSION_SECRET`: Generate a secure random string (64+ characters)
   - `RAZORPAY_KEY_ID` & `RAZORPAY_KEY_SECRET`: Get from https://dashboard.razorpay.com/app/keys
   - `RAZORPAY_WEBHOOK_SECRET`: Set up webhook and get secret from Razorpay dashboard
   - `DTDC_API_KEY`, `DTDC_USERNAME`, `DTDC_PASSWORD`: Contact DTDC/Shipsy for API credentials
   - `FIREBASE_SERVICE_ACCOUNT`: Path to your Firebase service account JSON file

#### Frontend Environment Setup:

1. **Copy the example environment file:**
   ```bash
   cp client/.env.example client/.env
   ```

2. The default values should work for local development, but you can customize as needed.

#### Firebase Service Account Setup:

1. **Get your Firebase service account JSON:**
   - Go to [Firebase Console](https://console.firebase.google.com/)
   - Select your project
   - Go to Project Settings → Service Accounts
   - Click "Generate new private key"
   - Download the JSON file

2. **Save the file to your backend config directory:**
   ```bash
   # Rename and move your downloaded service account file:
   mv ~/Downloads/your-project-firebase-adminsdk-xxxxx.json \
      php-backend/config/firebase-service-account.json
   ```

3. **Update the path in your `.env` file:**
   ```
   FIREBASE_SERVICE_ACCOUNT=config/firebase-service-account.json
   ```

**Note**: Never commit your actual `.env` files or Firebase service account JSON to version control. These contain sensitive credentials and should be kept secure.

### Step 5: Run the Project

You have several options to run the project:

#### Option A: Using VS Code Tasks (Recommended)

1. Press `Ctrl+Shift+P` (Windows/Linux) or `Cmd+Shift+P` (Mac)
2. Type "Tasks: Run Task"
3. Select **"Start Full Stack"** to run both frontend and backend together

Or run them separately:
- Select **"Start PHP Backend"** for backend only
- Select **"Start Frontend"** for frontend only

#### Option B: Using Terminal Commands

**Terminal 1 - Start Backend:**
```bash
cd php-backend/public
php -S 0.0.0.0:8000 index.php
```

**Terminal 2 - Start Frontend:**
```bash
cd client
npm run dev
```

#### Option C: Using VS Code Debugger

1. Go to the **Run and Debug** view (Ctrl+Shift+D)
2. Select **"Full Stack: Frontend + Backend"** from the dropdown
3. Click the green play button

### Step 6: Access the Application

Once both servers are running:

- **Frontend (Website)**: http://localhost:5000
- **Backend (API)**: http://localhost:8000/api

The frontend automatically proxies API requests to the backend.

## 🛠️ VS Code Configuration

The project includes pre-configured VS Code settings:

### Tasks Available:
- **Start Full Stack**: Runs both frontend and backend
- **Start PHP Backend**: Runs only the PHP backend server
- **Start Frontend**: Runs only the React frontend
- **Install Frontend Dependencies**: Runs `npm install`
- **Install Backend Dependencies**: Runs `composer install`

### Debugging:
- **Launch PHP Backend**: Debug PHP code
- **Launch Frontend (Chrome)**: Debug React code in Chrome
- **Full Stack Debug**: Debug both simultaneously

## 📁 Project Structure

```
.
├── client/                          # React Frontend
│   ├── src/                         # Source code
│   ├── public/                      # Static assets
│   ├── package.json                 # Node dependencies
│   ├── vite.config.js              # Vite configuration
│   ├── .env.example                # Environment template
│   └── .env                        # Your config (create from .env.example, gitignored)
│
├── php-backend/                     # PHP Backend
│   ├── config/                      # Configuration files
│   │   ├── firebase.php            # Firebase setup
│   │   ├── firebase-service-account.json.example  # Firebase template
│   │   └── [your-firebase-credentials.json]  # Created from template (gitignored)
│   ├── public/                      # Public directory (entry point)
│   │   └── index.php               # Main API entry point
│   ├── routes/                      # API routes
│   ├── models/                      # Data models
│   ├── middleware/                  # Middleware functions
│   ├── vendor/                      # Composer dependencies
│   ├── composer.json                # PHP dependencies
│   ├── .env.example                # Environment template
│   └── .env                        # Your credentials (create from .env.example, gitignored)
│
└── .vscode/                         # VS Code configuration
    ├── launch.json                  # Debug configurations
    ├── settings.json                # Editor settings
    └── tasks.json                   # Task definitions
```

## 🔧 Configuration Details

### Backend Configuration (php-backend/.env)

The backend integrates with the following services (you must configure your own credentials):

1. **Firebase Firestore** - Database
   - Create service account in Firebase Console
   - Download JSON and save to config directory
   - Update FIREBASE_SERVICE_ACCOUNT path in .env

2. **Razorpay** - Payment Gateway
   - Get API keys from Razorpay dashboard
   - Configure in .env file
   - Supports webhook integration

3. **DTDC (via Shipsy)** - Courier Integration
   - API endpoint: https://app.shipsy.in
   - Obtain credentials from DTDC/Shipsy
   - Configure in .env file

### Frontend Configuration (client/.env)

- **API URL**: http://localhost:8000/api (default for local development)
- **Proxy Configuration**: Vite automatically proxies `/api` requests to backend

### Firebase Service Account

The Firebase service account JSON file must contain credentials for:
- Authentication
- Firestore database access
- Cloud services

**Important**: This file should never be committed to public repositories.

## 🧪 Testing

### Test Backend API:

```bash
# Test health check
curl http://localhost:8000/api/health

# Test with browser
# Open http://localhost:8000/api in your browser
```

### Test Frontend:

```bash
# Open http://localhost:5000 in your browser
# The site should load with the homepage
```

## 🐛 Troubleshooting

### Backend Issues

**Problem**: PHP server won't start
**Solution**: 
- Check if PHP is installed: `php --version`
- Check if port 8000 is available: `netstat -an | findstr 8000` (Windows) or `lsof -i :8000` (Mac/Linux)

**Problem**: Firebase authentication errors
**Solution**:
- Verify the service account JSON file exists in `php-backend/config/` directory
- Check that the `FIREBASE_SERVICE_ACCOUNT` variable in `.env` points to the correct file
- Ensure your Firebase service account JSON has valid credentials
- Check that the private key is properly formatted with newlines (\n)

**Problem**: "Class not found" errors
**Solution**: Run `composer install` in the `php-backend` directory

### Frontend Issues

**Problem**: Frontend won't start
**Solution**:
- Check Node.js version: `node --version` (should be 18+)
- Delete `node_modules` and `package-lock.json`, then run `npm install` again

**Problem**: "Cannot connect to backend" errors
**Solution**:
- Ensure the PHP backend is running on port 8000
- Check the proxy configuration in `vite.config.js`

**Problem**: Blank page or white screen
**Solution**:
- Open browser console (F12) to see error messages
- Check if all dependencies are installed: `npm install`

### Port Conflicts

If ports 5000 or 8000 are already in use:

**For Backend (port 8000)**:
1. Change the port in the PHP command: `php -S 0.0.0.0:8080 index.php`
2. Update the proxy target in `client/vite.config.js`

**For Frontend (port 5000)**:
1. Change the port in `client/vite.config.js`
2. Update the VS Code tasks in `.vscode/tasks.json`

## 🔐 Security Notes

⚠️ **Important Security Information**:

1. **Environment Variables**: The `.env` files contain sensitive credentials (API keys, secrets). Never commit these to public repositories.

2. **Firebase Service Account**: The service account JSON file provides full access to your Firebase project. Keep it secure.

3. **Production vs Development**: This setup is for **local development only**. For production:
   - Use environment-specific configuration
   - Enable HTTPS
   - Use production API endpoints
   - Implement proper secret management

4. **Git Ignore**: Make sure `.env` files and service account JSON files are in `.gitignore`

## 📦 Additional Commands

### Frontend Commands:

```bash
cd client

# Development server
npm run dev

# Production build
npm run build

# Preview production build
npm run preview
```

### Backend Commands:

```bash
cd php-backend

# Install dependencies
composer install

# Run tests
composer test

# Start development server
php -S 0.0.0.0:8000 -t public/
```

## 🎯 Development Workflow

1. **Start Development**: Run "Start Full Stack" task in VS Code
2. **Make Changes**: Edit files in `client/src` or `php-backend`
3. **Hot Reload**: Frontend changes reload automatically
4. **Backend Changes**: Restart PHP server to see changes
5. **Test**: Verify changes at http://localhost:5000

## 📚 Additional Resources

- **React Documentation**: https://react.dev/
- **Vite Documentation**: https://vitejs.dev/
- **PHP Documentation**: https://www.php.net/docs.php
- **Firebase Documentation**: https://firebase.google.com/docs
- **Razorpay Documentation**: https://razorpay.com/docs/

## 🤝 Support

If you encounter any issues not covered in this guide:

1. Check the browser console for frontend errors (F12)
2. Check the terminal output for backend errors
3. Review the environment variable configuration
4. Ensure all dependencies are properly installed

## ✅ Verification Checklist

Before starting development, verify:

- [ ] PHP 8.1+ installed
- [ ] Composer installed
- [ ] Node.js 18+ installed
- [ ] All backend dependencies installed (`composer install`)
- [ ] All frontend dependencies installed (`npm install`)
- [ ] Created `.env` files from `.env.example` in both `client` and `php-backend` directories
- [ ] Filled in all required credentials in `.env` files
- [ ] Firebase service account JSON file created and configured
- [ ] Both servers start without errors
- [ ] Frontend accessible at http://localhost:5000
- [ ] Backend accessible at http://localhost:8000

## 🎉 You're Ready!

Once all steps are complete, your development environment is fully set up and ready for local development. The project runs 100% locally without any external dependencies on Replit or other cloud services.

Happy coding! 🚀
