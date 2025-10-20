# PSAU Admission System - Deployment Guide

## 🚀 Complete Step-by-Step Deployment Guide

This guide will walk you through deploying the PSAU Admission System to Replit and making it accessible on the web.

## 📋 Prerequisites

Before starting, ensure you have:
- A Replit account (free or paid)
- A GitHub account
- Access to Firebase Console
- Basic understanding of PHP, MySQL, and web deployment

## 🔧 Step 1: Prepare Your Project for Git

### 1.1 Initialize Git Repository
```bash
# Navigate to your project directory
cd "C:\xampp\htdocs\Development of AI-Assisted Admission System at PSAU"

# Initialize Git repository
git init

# Add all files to Git
git add .

# Create initial commit
git commit -m "Initial commit: PSAU Admission System"
```

### 1.2 Create GitHub Repository
1. Go to [GitHub.com](https://github.com)
2. Click "New repository"
3. Name it: `psau-admission-system`
4. Make it **Public** (required for free Replit)
5. Don't initialize with README (we already have files)
6. Click "Create repository"

### 1.3 Push to GitHub
```bash
# Add GitHub remote
git remote add origin https://github.com/YOUR_USERNAME/psau-admission-system.git

# Push to GitHub
git push -u origin main
```

## 🔥 Step 2: Deploy to Replit

### 2.1 Create New Replit
1. Go to [Replit.com](https://replit.com)
2. Click "Create Repl"
3. Choose "Import from GitHub"
4. Enter your repository URL: `https://github.com/YOUR_USERNAME/psau-admission-system`
5. Click "Import from GitHub"

### 2.2 Configure Replit Environment
1. Wait for the import to complete
2. The `.replit` file will automatically configure the environment
3. Click "Run" to start the setup process

### 2.3 Run Setup Script
In the Replit terminal, run:
```bash
chmod +x setup.sh
./setup.sh
```

This will:
- Install all dependencies (PHP, MySQL, Python, Node.js)
- Set up the database
- Configure the environment
- Install required packages

## 🗄️ Step 3: Database Setup

### 3.1 Verify Database Import
The setup script should have imported the database. To verify:
```bash
mysql -u root -e "USE psau_admission; SHOW TABLES;"
```

### 3.2 Create Admin User (if needed)
```sql
-- Connect to MySQL
mysql -u root

-- Use the database
USE psau_admission;

-- Check if admin exists
SELECT * FROM admins;

-- If no admin exists, create one
INSERT INTO admins (username, email, mobile_number, password, role) 
VALUES ('admin', 'admin@psau.edu.ph', '1234567890', '$2y$10$h81ZG0xk0f8MXSdJAswRuuu1tF8wdMKraq6kQH9s.Y8RXs07Ff3zu', 'admin');
```

## 🔧 Step 4: Configure Firebase

### 4.1 Update Firebase Configuration
1. Go to [Firebase Console](https://console.firebase.google.com)
2. Select your project: `psau-admission-system`
3. Go to Project Settings > General
4. Copy your Firebase config

### 4.2 Update Environment Variables in Replit
1. In Replit, go to the "Secrets" tab (lock icon)
2. Add these environment variables:
   - `FIREBASE_API_KEY`: Your Firebase API key
   - `FIREBASE_PROJECT_ID`: psau-admission-system
   - `FIREBASE_AUTH_DOMAIN`: psau-admission-system.firebaseapp.com
   - `FIREBASE_STORAGE_BUCKET`: psau-admission-system.appspot.com
   - `FIREBASE_MESSAGING_SENDER_ID`: 522448258958
   - `FIREBASE_APP_ID`: 1:522448258958:web:994b133a4f7b7f4c1b06df

## 🚀 Step 5: Start the Application

### 5.1 Start Services
In the Replit terminal:
```bash
# Start MySQL
service mysql start

# Start the PHP server
php -S 0.0.0.0:8000 -t .
```

### 5.2 Access Your Application
1. Click the "Open in new tab" button in Replit
2. Or visit: `https://YOUR_REPL_NAME.replit.app`
3. The application should now be running!

## 🔐 Step 6: Initial Setup

### 6.1 Test the Application
1. Visit the main page
2. Try registering a new user
3. Test the admin login

### 6.2 Admin Login
- **URL**: `https://YOUR_REPL_NAME.replit.app/admin/login.php`
- **Username**: `jerico` (or the admin you created)
- **Password**: Check the database for the hashed password

### 6.3 Configure Settings
1. Login to admin panel
2. Go to Settings/Configuration
3. Update:
   - Site URL
   - Email settings
   - Firebase configuration
   - reCAPTCHA keys

## 🌐 Step 7: Make it Publicly Accessible

### 7.1 Replit Web Service
Replit automatically provides a public URL:
- Format: `https://YOUR_REPL_NAME.replit.app`
- This URL is accessible from anywhere on the internet

### 7.2 Custom Domain (Optional)
If you want a custom domain:
1. Go to Replit Settings
2. Add your custom domain
3. Update DNS settings with your domain provider

## 🔧 Step 8: Production Optimizations

### 8.1 Security Hardening
1. Update all default passwords
2. Configure proper file permissions
3. Enable HTTPS (Replit provides this automatically)
4. Update Firebase security rules

### 8.2 Performance Optimization
1. Enable PHP OPcache
2. Optimize database queries
3. Configure proper caching
4. Optimize images and assets

### 8.3 Monitoring
1. Set up error logging
2. Monitor application performance
3. Set up uptime monitoring
4. Configure backup procedures

## 🐛 Troubleshooting

### Common Issues

#### Database Connection Error
```bash
# Check if MySQL is running
service mysql status

# Start MySQL if not running
service mysql start

# Check database exists
mysql -u root -e "SHOW DATABASES;"
```

#### PHP Errors
```bash
# Check PHP error logs
tail -f /var/log/php_errors.log

# Check PHP configuration
php -m
```

#### Firebase Connection Issues
1. Verify API keys in environment variables
2. Check Firebase project settings
3. Ensure proper CORS configuration

#### File Upload Issues
```bash
# Check upload directory permissions
ls -la uploads/

# Fix permissions if needed
chmod 755 uploads/
chmod 755 images/
```

### Getting Help
1. Check Replit logs in the console
2. Review PHP error logs
3. Check Firebase console for errors
4. Verify all environment variables are set

## 📱 Step 9: Mobile Optimization

The application is already mobile-responsive, but you can:
1. Test on various devices
2. Optimize images for mobile
3. Configure push notifications
4. Test touch interactions

## 🔄 Step 10: Maintenance

### Regular Tasks
1. **Daily**: Check error logs
2. **Weekly**: Review application performance
3. **Monthly**: Update dependencies
4. **Quarterly**: Security audit

### Backup Strategy
1. Database backups
2. File uploads backup
3. Configuration backup
4. Code repository backup

## 🎉 Congratulations!

Your PSAU Admission System is now deployed and accessible on the web! 

### Quick Access URLs:
- **Main Site**: `https://YOUR_REPL_NAME.replit.app`
- **Admin Panel**: `https://YOUR_REPL_NAME.replit.app/admin/login.php`
- **User Registration**: `https://YOUR_REPL_NAME.replit.app/public/register.php`

### Next Steps:
1. Test all functionality
2. Configure email notifications
3. Set up monitoring
4. Train users on the system
5. Plan for regular maintenance

## 📞 Support

If you encounter any issues:
1. Check this deployment guide
2. Review the troubleshooting section
3. Check Replit documentation
4. Contact the development team

---

**Note**: This deployment guide assumes you're using the free Replit plan. For production use, consider upgrading to a paid plan for better performance and reliability.
