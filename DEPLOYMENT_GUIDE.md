# Complete Deployment Guide: PSAU AI-Assisted Admission System

## 🚀 Step-by-Step Deployment Process

### Prerequisites
- Firebase account (free)
- Render account (free) 
- Cloud database (PlanetScale/Railway/Supabase)
- Domain name (optional, for custom domain)

---

## Part 1: Firebase Deployment (Frontend)

### Step 1: Create Firebase Project
1. Go to [Firebase Console](https://console.firebase.google.com/)
2. Click "Create a project"
3. Enter project name: `psau-admission-system`
4. Enable Google Analytics (optional)
5. Click "Create project"

### Step 2: Enable Firebase Services
1. **Hosting**: Go to Hosting → Click "Get started"
2. **Functions**: Go to Functions → Click "Get started"
3. **Authentication**: Go to Authentication → Enable Email/Password
4. **Firestore**: Go to Firestore Database → Create database → Start in test mode

### Step 3: Install Firebase CLI
Open Command Prompt/PowerShell and run:
```bash
npm install -g firebase-tools
firebase login
```
Follow the browser login process.

### Step 4: Initialize Firebase in Your Project
```bash
cd "C:\xampp\htdocs\Development of AI-Assisted Admission System at PSAU"
firebase init
```

**Select these options:**
- ✅ Hosting: Configure files for Firebase Hosting
- ✅ Functions: Configure a Cloud Functions directory
- Choose your Firebase project
- Public directory: `public`
- Single-page app: No
- Automatic builds: Yes
- Use existing functions: Yes

### Step 5: Deploy Firebase Functions
```bash
cd functions
npm install
cd ..
firebase deploy --only functions
```

### Step 6: Deploy Firebase Hosting
```bash
firebase deploy --only hosting
```

**Your Firebase URL will be:** `https://psau-admission-system.web.app`

---

## Part 2: Render Deployment (Backend)

### Step 1: Create Render Account
1. Go to [Render Dashboard](https://dashboard.render.com/)
2. Sign up with GitHub
3. Connect your GitHub account

### Step 2: Create Web Service
1. Click "New +" → "Web Service"
2. Connect your GitHub repository
3. Configure settings:

**Basic Settings:**
- **Name**: `psau-backend-api`
- **Environment**: Python 3
- **Region**: Oregon (US West)
- **Branch**: main

**Build & Deploy:**
- **Build Command**: `pip install -r python/image/requirements.txt`
- **Start Command**: `gunicorn python.image.app_production:app --bind 0.0.0.0:$PORT --workers 2 --timeout 120`

### Step 3: Add Environment Variables
In Render dashboard → Environment tab, add:

```bash
# Database Configuration
DB_HOST=your-database-host
DB_USER=your-database-user
DB_PASSWORD=your-database-password
DB_NAME=psau_admission
DB_PORT=3306

# Flask Configuration
FLASK_ENV=production
FLASK_DEBUG=false
SECRET_KEY=your-secret-key-here

# Firebase Configuration
FIREBASE_PROJECT_ID=psau-admission-system
FIREBASE_PRIVATE_KEY="-----BEGIN PRIVATE KEY-----\nyour-private-key\n-----END PRIVATE KEY-----\n"
FIREBASE_CLIENT_EMAIL=firebase-adminsdk-xxxxx@psau-admission-system.iam.gserviceaccount.com

# OCR Configuration
OCR_MODEL_PATH=/app/models
UPLOAD_FOLDER=/app/uploads
MAX_CONTENT_LENGTH=16777216
```

### Step 4: Deploy
Click "Create Web Service" and wait for deployment.

**Your Render URL will be:** `https://psau-backend-api.onrender.com`

---

## Part 3: Database Setup

### Option A: PlanetScale (MySQL) - Recommended
1. Go to [PlanetScale](https://planetscale.com/)
2. Create new database: `psau_admission`
3. Get connection credentials
4. Update Render environment variables with PlanetScale credentials

### Option B: Railway (PostgreSQL)
1. Go to [Railway](https://railway.app/)
2. Create new PostgreSQL database
3. Get connection string
4. Update environment variables for PostgreSQL

### Option C: Supabase (PostgreSQL)
1. Go to [Supabase](https://supabase.com/)
2. Create new project
3. Get database credentials
4. Update environment variables

---

## Part 4: Domain Setup (Optional)

### Step 1: Buy Domain
1. Go to domain registrar (GoDaddy, Namecheap, etc.)
2. Buy domain: `psau-admission.com` (or your preferred name)

### Step 2: Configure Domain in Firebase
1. Go to Firebase Console → Hosting
2. Click "Add custom domain"
3. Enter your domain name
4. Follow DNS configuration instructions

### Step 3: Update DNS Records
Add these DNS records in your domain registrar:
```
Type: A
Name: @
Value: 151.101.1.195

Type: A  
Name: @
Value: 151.101.65.195

Type: CNAME
Name: www
Value: psau-admission-system.web.app
```

---

## Part 5: Update Frontend Configuration

### Step 1: Update API URLs
In your PHP files, update the API base URL:

```php
// In public/ai/html/chatbot.html
window.CHATBOT_API_BASE = 'https://psau-backend-api.onrender.com';
```

### Step 2: Update Firebase Configuration
Create `public/js/firebase-config.js`:

```javascript
const firebaseConfig = {
  apiKey: "your-api-key",
  authDomain: "psau-admission-system.firebaseapp.com",
  projectId: "psau-admission-system",
  storageBucket: "psau-admission-system.appspot.com",
  messagingSenderId: "your-sender-id",
  appId: "your-app-id"
};
```

### Step 3: Redeploy Firebase
```bash
firebase deploy --only hosting
```

---

## Part 6: Testing Your Deployment

### Test Frontend
Visit: `https://psau-admission-system.web.app`

### Test Backend API
```bash
curl https://psau-backend-api.onrender.com/health
```

### Test Database Connection
```bash
curl https://psau-backend-api.onrender.com/db_check
```

### Test Firebase Functions
```bash
curl https://us-central1-psau-admission-system.cloudfunctions.net/sendEmail
```

---

## Part 7: SEO and Google Search

### Step 1: Submit to Google Search Console
1. Go to [Google Search Console](https://search.google.com/search-console/)
2. Add your property: `https://psau-admission-system.web.app`
3. Verify ownership
4. Submit sitemap

### Step 2: Create Sitemap
Create `public/sitemap.xml`:

```xml
<?xml version="1.0" encoding="UTF-8"?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
  <url>
    <loc>https://psau-admission-system.web.app/</loc>
    <lastmod>2024-01-01</lastmod>
    <changefreq>weekly</changefreq>
    <priority>1.0</priority>
  </url>
  <url>
    <loc>https://psau-admission-system.web.app/public/</loc>
    <lastmod>2024-01-01</lastmod>
    <changefreq>weekly</changefreq>
    <priority>0.8</priority>
  </url>
  <url>
    <loc>https://psau-admission-system.web.app/admin/</loc>
    <lastmod>2024-01-01</lastmod>
    <changefreq>monthly</changefreq>
    <priority>0.6</priority>
  </url>
</urlset>
```

### Step 3: Add Meta Tags
Update your HTML files with proper meta tags:

```html
<head>
  <title>PSAU AI-Assisted Admission System</title>
  <meta name="description" content="Pampanga State Agricultural University AI-Assisted Admission System">
  <meta name="keywords" content="PSAU, admission, university, AI, chatbot, Philippines">
  <meta name="author" content="PSAU">
  
  <!-- Open Graph -->
  <meta property="og:title" content="PSAU AI-Assisted Admission System">
  <meta property="og:description" content="Apply to PSAU with AI assistance">
  <meta property="og:image" content="https://psau-admission-system.web.app/logo/PSAU_logo.png">
  <meta property="og:url" content="https://psau-admission-system.web.app">
  
  <!-- Twitter -->
  <meta name="twitter:card" content="summary_large_image">
  <meta name="twitter:title" content="PSAU AI-Assisted Admission System">
  <meta name="twitter:description" content="Apply to PSAU with AI assistance">
</head>
```

### Step 4: Submit to Search Engines
1. **Google**: Submit to Google Search Console
2. **Bing**: Submit to Bing Webmaster Tools
3. **Yahoo**: Submit to Yahoo Site Explorer

---

## Part 8: Monitoring and Maintenance

### Step 1: Set Up Monitoring
1. **Firebase Analytics**: Monitor user engagement
2. **Render Monitoring**: Monitor backend performance
3. **Google Analytics**: Track website traffic

### Step 2: Set Up Alerts
1. **Uptime Monitoring**: Use UptimeRobot or similar
2. **Error Monitoring**: Set up error alerts
3. **Performance Monitoring**: Monitor response times

### Step 3: Regular Maintenance
- **Weekly**: Check logs and performance
- **Monthly**: Update dependencies
- **Quarterly**: Security audit

---

## 🎉 Your System is Live!

### URLs:
- **Frontend**: `https://psau-admission-system.web.app`
- **Backend API**: `https://psau-backend-api.onrender.com`
- **Admin Panel**: `https://psau-admission-system.web.app/admin`

### Default Admin Credentials:
- **Username**: `admin`
- **Password**: `admin123`
- **Email**: `admin@psau.edu.ph`

⚠️ **Important**: Change default admin password immediately!

### Next Steps:
1. Change default passwords
2. Configure email settings
3. Add your institution's branding
4. Set up monitoring
5. Configure backups
6. Submit to search engines

---

## Troubleshooting

### Common Issues:

1. **Firebase deployment fails**
   - Check Firebase CLI version
   - Verify project permissions
   - Check internet connection

2. **Render deployment fails**
   - Check build logs
   - Verify environment variables
   - Check Python dependencies

3. **Database connection fails**
   - Verify database credentials
   - Check network connectivity
   - Ensure database is running

4. **Domain not working**
   - Check DNS propagation
   - Verify DNS records
   - Wait for DNS propagation (up to 48 hours)

### Support:
- Firebase Support: [Firebase Help](https://firebase.google.com/support)
- Render Support: [Render Support](https://render.com/docs)
- Documentation: Check project README files

---

**Deployment completed successfully! 🚀**
