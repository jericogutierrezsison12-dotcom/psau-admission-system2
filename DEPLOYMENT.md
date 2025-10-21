# PSAU Admission System - Deployment Guide

This guide will help you deploy the PSAU Admission System to Railway and make it available online.

## Prerequisites

1. **GitHub Account** - For hosting your code repository
2. **Railway Account** - For hosting your application
3. **Firebase Account** - For authentication and email services
4. **MySQL Database** - Railway provides MySQL service

## Step 1: Prepare Your Project for Git

### 1.1 Initialize Git Repository

```bash
# Navigate to your project directory
cd "C:\xampp\htdocs\Development of AI-Assisted Admission System at PSAU"

# Initialize git repository
git init

# Add all files to git
git add .

# Create initial commit
git commit -m "Initial commit: PSAU Admission System"
```

### 1.2 Create GitHub Repository

1. Go to [GitHub.com](https://github.com) and create a new repository
2. Name it `psau-admission-system` (or your preferred name)
3. Make it public or private as needed
4. Don't initialize with README, .gitignore, or license (we already have these)

### 1.3 Push to GitHub

```bash
# Add your GitHub repository as remote origin
git remote add origin https://github.com/YOUR_USERNAME/psau-admission-system.git

# Push your code to GitHub
git push -u origin main
```

## Step 2: Deploy to Railway

### 2.1 Connect Railway to GitHub

1. Go to [Railway.app](https://railway.app)
2. Sign up/Login with your GitHub account
3. Click "New Project"
4. Select "Deploy from GitHub repo"
5. Choose your `psau-admission-system` repository
6. Click "Deploy Now"

### 2.2 Configure Environment Variables

In your Railway project dashboard:

1. Go to "Variables" tab
2. Add the following environment variables:

```
DB_HOST=localhost
DB_NAME=psau_admission
DB_USER=root
DB_PASS=

FIREBASE_API_KEY=your_firebase_api_key_here
FIREBASE_AUTH_DOMAIN=your_project.firebaseapp.com
FIREBASE_PROJECT_ID=your_project_id
FIREBASE_STORAGE_BUCKET=your_project.appspot.com
FIREBASE_MESSAGING_SENDER_ID=your_sender_id
FIREBASE_APP_ID=your_app_id
FIREBASE_EMAIL_FUNCTION_URL=your_cloud_function_url

ENVIRONMENT=production
DEBUG=false
```

### 2.3 Add MySQL Database

1. In Railway dashboard, click "New"
2. Select "Database" → "MySQL"
3. Railway will automatically create a MySQL database
4. Copy the `RAILWAY_MYSQL_URL` from the database service
5. Add it to your environment variables

### 2.4 Import Database Schema

1. Go to your MySQL database service in Railway
2. Click "Query" tab
3. Copy the contents of `database/psau_admission.sql`
4. Paste and execute the SQL to create all tables

## Step 3: Configure Firebase

### 3.1 Update Firebase Configuration

1. Go to [Firebase Console](https://console.firebase.google.com)
2. Select your project or create a new one
3. Go to Project Settings → General
4. Copy your Firebase configuration
5. Update the environment variables in Railway with your actual Firebase config

### 3.2 Deploy Firebase Functions

```bash
# Navigate to functions directory
cd functions

# Install dependencies
npm install

# Deploy functions
firebase deploy --only functions
```

## Step 4: Configure File Uploads

### 4.1 Create Upload Directories

Railway doesn't persist file uploads by default. You have two options:

**Option A: Use Railway Volumes (Recommended)**
1. In Railway dashboard, add a volume
2. Mount it to `/uploads` in your app
3. Update your code to use the volume path

**Option B: Use Cloud Storage**
1. Set up AWS S3, Google Cloud Storage, or similar
2. Update your upload handling code to use cloud storage

## Step 5: Test Your Deployment

1. Go to your Railway app URL (provided after deployment)
2. Test the main functionality:
   - User registration
   - Login
   - File uploads
   - Admin dashboard
   - Email notifications

## Step 6: Custom Domain (Optional)

1. In Railway dashboard, go to "Settings"
2. Click "Custom Domain"
3. Add your domain name
4. Follow the DNS configuration instructions

## Troubleshooting

### Common Issues

1. **Database Connection Error**
   - Check that `RAILWAY_MYSQL_URL` is set correctly
   - Verify database schema is imported

2. **Firebase Authentication Not Working**
   - Check Firebase configuration in environment variables
   - Verify Firebase project settings

3. **File Upload Issues**
   - Check upload directory permissions
   - Consider using cloud storage for production

4. **Email Not Sending**
   - Verify Firebase Cloud Functions are deployed
   - Check Firebase function logs

### Logs and Debugging

1. In Railway dashboard, go to "Deployments"
2. Click on your latest deployment
3. View logs to debug issues

## Security Considerations

1. **Environment Variables**: Never commit sensitive data to Git
2. **Firebase Security Rules**: Configure proper security rules
3. **Database Security**: Use strong passwords and limit access
4. **File Uploads**: Validate file types and sizes
5. **HTTPS**: Railway provides HTTPS by default

## Maintenance

1. **Regular Backups**: Set up automated database backups
2. **Updates**: Keep dependencies updated
3. **Monitoring**: Use Railway's monitoring features
4. **Logs**: Regularly check application logs

## Support

- Railway Documentation: https://docs.railway.app
- Firebase Documentation: https://firebase.google.com/docs
- GitHub Documentation: https://docs.github.com

---

**Note**: This deployment guide assumes you have basic knowledge of Git, GitHub, and web development. If you encounter issues, refer to the respective documentation or seek help from the community.
