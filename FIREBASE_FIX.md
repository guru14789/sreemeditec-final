# Firebase Firestore Error Fix

## Problem
Getting "Invalid JWT Signature" error when connecting to Firebase Firestore.

## Cause
The Firebase service account private key is incomplete, corrupted, or incorrectly formatted.

## Solution: Get Fresh Firebase Credentials

### Option 1: Download New Service Account (Recommended)

1. **Go to Firebase Console:**
   - Visit: https://console.firebase.google.com/
   - Select project: **sreemeditec-97add**

2. **Navigate to Service Accounts:**
   - Click the gear icon (⚙️) → Project settings
   - Click "Service accounts" tab
   - Click "Generate new private key" button
   - Download the JSON file

3. **Replace the file:**
   ```bash
   # Save the downloaded file as:
   # php-backend/config/sreemeditec-final-firebase-adminsdk-fbsvc-6184119249.json
   ```

4. **Restart the backend:**
   - The backend will automatically pick up the new credentials

### Option 2: Paste Complete Service Account Here

If you have the complete service account JSON (not truncated), paste it and I'll update the file.

The service account JSON should look like this (with a complete private_key):

```json
{
  "type": "service_account",
  "project_id": "sreemeditec-97add",
  "private_key_id": "...",
  "private_key": "-----BEGIN PRIVATE KEY-----\nMIIE...VERY LONG KEY...CTAg==\n-----END PRIVATE KEY-----\n",
  "client_email": "firebase-adminsdk-fbsvc@sreemeditec-97add.iam.gserviceaccount.com",
  ...
}
```

**The private_key field should be very long (around 1600+ characters).**

## Quick Check

Current private key length: 1708 characters ✓ (This looks complete)

Let me verify the key format is correct...
