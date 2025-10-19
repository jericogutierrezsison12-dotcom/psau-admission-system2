# 🚀 PSAU Admission System - Deployment Guide

## 📋 Quick Deployment Options

Your PSAU Admission System is now ready for deployment! Choose from the following options:

### 🔥 Option 1: Firebase Hosting (Recommended)

**Steps:**
1. Open Chrome and go to: `https://console.firebase.google.com`
2. Sign in with your Google account
3. Select your project: `psau-admission-system`
4. Click **"Hosting"** in the left sidebar
5. Click **"Get started"**
6. Choose **"Connect GitHub"**
7. Authorize Firebase to access your GitHub
8. Select repository: `psau-admission-system`
9. Configure:
   - **Branch**: `main`
   - **Build command**: (leave empty)
   - **Publish directory**: `public`
10. Click **"Save"** → **"Deploy"**

**Your site will be live at**: `https://psau-admission-system.web.app`

### 📄 Option 2: GitHub Pages (Free)

**Steps:**
1. Go to: `https://github.com/jericogutierrezsison12-dotcom/psau-admission-system`
2. Click **"Settings"** tab
3. Scroll to **"Pages"** section
4. Source: **"Deploy from a branch"**
5. Branch: **"main"** → **"/ (root)"**
6. Click **"Save"**

**Your site will be live at**: `https://jericogutierrezsison12-dotcom.github.io/psau-admission-system`

### 🌐 Option 3: Netlify

**Steps:**
1. Go to: `https://netlify.com`
2. Sign up with GitHub account
3. Click **"New site from Git"**
4. Choose **"GitHub"**
5. Select repository: `psau-admission-system`
6. Configure:
   - **Build command**: (leave empty)
   - **Publish directory**: `public`
7. Click **"Deploy site"**

### ⚡ Option 4: Vercel

**Steps:**
1. Go to: `https://vercel.com`
2. Sign up with GitHub
3. Click **"New Project"**
4. Import repository: `psau-admission-system`
5. Configure:
   - **Framework Preset**: Other
   - **Root Directory**: `./public`
6. Click **"Deploy"**

## 🔧 For PHP Applications (Traditional Hosting)

Since your system uses PHP, you'll need a PHP-compatible hosting service:

### 🌟 Recommended PHP Hosts:

1. **Hostinger** (`https://hostinger.com`)
   - PHP 8.1+ support
   - MySQL database
   - cPanel included
   - Starting at $1.99/month

2. **000WebHost** (`https://000webhost.com`)
   - Free PHP hosting
   - MySQL database
   - No ads on paid plans

3. **InfinityFree** (`https://infinityfree.net`)
   - Completely free
   - PHP 8.1+ support
   - MySQL database

### 📤 Upload Process:

1. **Export Database:**
   ```sql
   -- Export from phpMyAdmin or MySQL command line
   mysqldump -u root -p psau_admission > psau_admission.sql
   ```

2. **Upload Files:**
   - Use File Manager or FTP
   - Upload all project files to `public_html` folder

3. **Import Database:**
   - Create new database in hosting control panel
   - Import `psau_admission.sql`

4. **Update Configuration:**
   - Edit `includes/db_connect.php` with new database credentials
   - Update `firebase/config.php` if needed

## 🔐 Environment Configuration

### Database Configuration (`includes/db_connect.php`)
```php
<?php
// Production database settings
$host = 'your-production-host';
$dbname = 'psau_admission';
$username = 'your-production-username';
$password = 'your-production-password';
?>
```

### Firebase Configuration (`firebase/config.php`)
```php
<?php
$firebase_config = [
    'apiKey' => 'AIzaSyB7HqxV971vmWiJiXnWdaFnMaFx1C1t6s8',
    'authDomain' => 'psau-admission-system.firebaseapp.com',
    'projectId' => 'psau-admission-system',
    'storageBucket' => 'psau-admission-system.appspot.com',
    'messagingSenderId' => '522448258958',
    'appId' => '1:522448258958:web:994b133a4f7b7f4c1b06df',
    'email_function_url' => 'https://sendemail-alsstt22ha-uc.a.run.app'
];
?>
```

## 🚀 Automated Deployment

Run the deployment script:
```bash
# Windows
deploy.bat

# Or manually
git add .
git commit -m "Deploy to production"
git push origin main
```

## 📱 Mobile App Deployment

Your system is also ready for mobile deployment:

1. **Progressive Web App (PWA):**
   - Add `manifest.json`
   - Implement service worker
   - Enable offline functionality

2. **React Native:**
   - Convert to React Native app
   - Use Firebase SDK for mobile

3. **Flutter:**
   - Create Flutter app
   - Integrate Firebase plugins

## 🔍 Testing Your Deployment

After deployment, test these features:

- [ ] User registration
- [ ] User login
- [ ] PDF upload and validation
- [ ] Admin dashboard access
- [ ] Email notifications
- [ ] Firebase real-time updates
- [ ] Mobile responsiveness

## 📞 Support

If you encounter any issues:

1. Check Firebase Console logs
2. Review hosting provider error logs
3. Verify database connections
4. Test API endpoints

## 🎉 Success!

Your PSAU Admission System is now live and ready to serve students!

**Live URLs:**
- Firebase: `https://psau-admission-system.web.app`
- GitHub Pages: `https://jericogutierrezsison12-dotcom.github.io/psau-admission-system`

---

**Developed by:** PSAU Development Team  
**Version:** 1.0.0  
**Last Updated:** January 2024
