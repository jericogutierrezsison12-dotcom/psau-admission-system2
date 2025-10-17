# PSAU AI-Assisted Admission System - Render Deployment Guide

## 🚀 Quick Setup for Render

### Step 1: Create GitHub Repository
1. Go to [GitHub](https://github.com) and create a new repository
2. Name it: `psau-admission-system`
3. Make it **Public** (required for free Render deployment)
4. Initialize with README

### Step 2: Upload Your Code to GitHub
```bash
# In your project directory
git init
git add .
git commit -m "Initial commit - PSAU Admission System"
git branch -M main
git remote add origin https://github.com/YOUR_USERNAME/psau-admission-system.git
git push -u origin main
```

### Step 3: Deploy to Render
1. Go to [Render Dashboard](https://dashboard.render.com/)
2. Sign up/Login with GitHub
3. Click **"New +"** → **"Web Service"**
4. Connect your GitHub repository: `psau-admission-system`
5. Configure settings:

**Basic Settings:**
- **Name**: `psau-backend-api`
- **Environment**: `Python 3`
- **Region**: `Oregon (US West)`
- **Branch**: `main`

**Build & Deploy:**
- **Build Command**: `pip install -r python/image/requirements.txt`
- **Start Command**: `gunicorn python.image.app_production:app --bind 0.0.0.0:$PORT --workers 2 --timeout 120`

### Step 4: Environment Variables
Add these environment variables in Render:

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

# Firebase Configuration (if needed)
FIREBASE_PROJECT_ID=psau-admission-system
FIREBASE_PRIVATE_KEY="-----BEGIN PRIVATE KEY-----\nyour-private-key\n-----END PRIVATE KEY-----\n"
FIREBASE_CLIENT_EMAIL=firebase-adminsdk-xxxxx@psau-admission-system.iam.gserviceaccount.com

# OCR Configuration
OCR_MODEL_PATH=/app/models
UPLOAD_FOLDER=/app/uploads
MAX_CONTENT_LENGTH=16777216
```

### Step 5: Deploy
1. Click **"Create Web Service"**
2. Wait for deployment (5-10 minutes)
3. Your API will be available at: `https://psau-backend-api.onrender.com`

## 🔗 Connect Frontend to Backend

Update your Firebase Functions to proxy requests to Render:

```javascript
// In functions/index.js
const RENDER_API_URL = 'https://psau-backend-api.onrender.com';

exports.apiProxy = functions.https.onRequest(async (req, res) => {
  // Enable CORS
  res.set('Access-Control-Allow-Origin', '*');
  res.set('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS');
  res.set('Access-Control-Allow-Headers', 'Content-Type, Authorization');
  
  if (req.method === 'OPTIONS') {
    res.status(204).send('');
    return;
  }
  
  try {
    const response = await axios({
      method: req.method,
      url: `${RENDER_API_URL}${req.path}`,
      data: req.body,
      headers: req.headers
    });
    
    res.status(response.status).send(response.data);
  } catch (error) {
    console.error('Proxy error:', error);
    res.status(500).send({ error: 'Backend service unavailable' });
  }
});
```

## 🎯 Expected Results

After deployment:
- **Frontend**: `https://psau-admission-system.web.app`
- **Backend API**: `https://psau-backend-api.onrender.com`
- **Health Check**: `https://psau-backend-api.onrender.com/health`

## 📋 API Endpoints

- `GET /` - Service info
- `GET /health` - Health check
- `POST /api/chatbot` - AI Chatbot
- `POST /api/recommend` - Course recommendations
- `POST /api/ocr/classify` - Document classification
- `POST /api/ocr/extract` - Text extraction

## 🚨 Troubleshooting

**Common Issues:**
1. **Build fails**: Check requirements.txt syntax
2. **Import errors**: Ensure all dependencies are listed
3. **Database connection**: Verify environment variables
4. **CORS errors**: Update allowed origins in app_production.py

**Logs**: Check Render dashboard → Logs tab for detailed error messages
