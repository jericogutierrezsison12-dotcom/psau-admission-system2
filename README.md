# PSAU Admission System

A fully AI-powered, secure, and automated Admission System for Pampanga State Agricultural University (PSAU).

## Project Overview

The PSAU Admission System provides an end-to-end solution for managing the university's admission process, from application submission to enrollment. The system is built using PHP for both frontend and backend functionality, MySQL for database storage, and Firebase for authentication, real-time updates, and notification services.

## Features

- **User Registration and Authentication**: Secure registration with OTP verification via Firebase Authentication
- **Application Submission**: PDF upload and automated validation using AI
- **Progress Tracking**: Real-time status updates through Firebase Realtime Database
- **Admin Dashboard**: For reviewing applications, scheduling exams, and managing the admission process
- **Automated Notifications**: Email and SMS notifications for important steps in the admission process
- **Course Selection and Assignment**: Course preference selection and admin assignment
- **Enrollment Scheduling**: Final step to complete the admission process

## Technical Stack

- **Backend**: PHP 7.4+
- **Frontend**: HTML5, CSS3, JavaScript (combined within PHP files)
- **Database**: MySQL (via XAMPP)
- **Authentication**: Firebase Authentication with SMS OTP
- **Security**: Firebase reCAPTCHA v3
- **Real-time Updates**: Firebase Realtime Database
- **Email Notifications**: Firebase Cloud Functions with Gmail
- **SMS Notifications**: Firebase Authentication SMS
- **PDF Processing**: Python with OCR (Tesseract)

## Installation and Setup

### Prerequisites

1. XAMPP (with PHP 7.4+ and MySQL)
2. Firebase account with Blaze plan (for Cloud Functions)
3. Python 3.7+ with pip
4. Node.js and npm (for Firebase Functions)

### Database Setup

1. Start XAMPP and ensure MySQL service is running
2. Import the database schema from `/database/psau_admission.sql`
3. Default credentials are:
   - Host: localhost
   - Username: root
   - Password: (blank)
   - Database: psau_admission

### Firebase Setup

1. Create a Firebase project
2. Enable Authentication with Email/Password and Phone
3. Set up Realtime Database
4. Set up Cloud Functions with Firebase Secrets:
   ```
   firebase functions:secrets:set SMTP_USER
   firebase functions:secrets:set SMTP_PASS
   ```
   Enter `siriyaporn.kwangusan@gmail.com` for SMTP_USER and your Gmail App Password for SMTP_PASS.
5. Deploy functions from the `/functions` directory

### Python Setup

Install required Python packages:
```
pip install pytesseract pdf2image
```

You also need to install Tesseract OCR engine on your system.

### System Setup

1. Clone or download the project to your XAMPP htdocs directory
2. Configure the database connection in `/includes/db_connect.php` if needed
3. Configure the Firebase project details in your client-side scripts
4. Ensure the `/uploads` directory is writable by the web server

## System Architecture

- **Public Interface**: User-facing pages in `/public` directory
- **Admin Interface**: Administration pages in `/admin` directory
- **Backend Functions**: Reusable PHP scripts in `/includes` directory
- **Firebase Functions**: Cloud functions in `/functions` directory
- **Python Scripts**: PDF processing in `/python` directory

## Security Features

- Password hashing using PHP's native `password_hash()`
- Firebase reCAPTCHA v3 for login protection
- OTP verification for user registration
- Secure storage of Gmail credentials in Firebase environment variables
- Role-based access control

## Credits

Developed for Pampanga State Agricultural University (PSAU) Admission System.

## License

Proprietary - All rights reserved.

# PSAU Admission System - Firebase Email Integration

This guide explains how to set up and use the Firebase email integration for the PSAU Admission System.

## Overview

The system now uses Firebase Cloud Functions for all email notifications, including:
- Application verification emails
- Application rejection/resubmission emails
- Test emails

## Setup Instructions

### 1. Create a Firebase Project

1. Go to [Firebase Console](https://console.firebase.google.com/)
2. Click "Add project" and follow the setup steps
3. Name your project (e.g., "psau-admission-system")
4. Follow the prompts to complete setup

### 2. Set Up Firebase Cloud Functions

1. Install the Firebase CLI:
```
npm install -g firebase-tools
```

2. Login to Firebase:
```
firebase login
```

3. Initialize your project:
```
mkdir functions
cd functions
firebase init functions
```

4. Create the Cloud Functions code in your `functions` directory:
   - `index.js`: Contains the Cloud Functions for email sending
   - `package.json`: Contains dependencies

5. Install dependencies:
```
cd functions
npm install
```

6. Deploy functions:
```
firebase deploy --only functions
```

7. After deployment, Firebase will show the URL for your Cloud Function. Copy this URL.

### 3. Update Firebase Configuration

1. Open `firebase_email.php` in your project
2. Update the Firebase configuration:
```php
$firebase_config = [
    'api_key' => 'YOUR_FIREBASE_API_KEY', // From Firebase console
    'project_id' => 'YOUR_PROJECT_ID',
    'email_function_url' => 'YOUR_CLOUD_FUNCTION_URL'
];
```

## Testing the Integration

1. Use the Firebase Console to test your cloud functions
2. Monitor the cloud function logs in Firebase Console to confirm proper email delivery

## Troubleshooting

### Common Issues

1. **Email not sending**:
   - Check your Firebase Cloud Function logs in the Firebase Console
   - Verify your API key is correct
   - Check that the Cloud Function URL is correct

2. **Function deployment errors**:
   - Make sure you've installed all dependencies
   - Check that your Firebase project is properly set up
   - Verify your `package.json` has the right dependencies

3. **Integration errors**:
   - Check PHP error logs for detailed error messages
   - Make sure the Firebase email functions are being included properly
   - Verify that cURL is enabled in your PHP configuration

## File Structure

- `firebase_email.php`: Main integration file with email functions
- `functions/`: Contains Cloud Function code for deployment
  - `index.js`: Cloud Functions for email sending
  - `package.json`: Node.js dependencies

## Using the Integration

To send an email from your PHP code:

```php
// Include the Firebase email functions
require_once 'firebase_email.php';

// Send a verification email
$user = [
    'email' => 'user@example.com',
    'first_name' => 'John',
    'last_name' => 'Doe'
];
$result = send_verification_email($user);

// Send a resubmission email
$reason = "Missing documents";
$result = send_resubmission_email($user, $reason);

// Send a custom email
$to = 'user@example.com';
$subject = 'Custom Email';
$message = '<p>This is a custom email message.</p>';
$result = firebase_send_email($to, $subject, $message);
``` 