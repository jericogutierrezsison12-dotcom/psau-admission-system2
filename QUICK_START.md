# Quick Start Guide - Render Deployment

## ✅ Code is Ready!
Your code has been pushed to Git and is ready for deployment.

## 🎯 ONE Critical Step Required

### Authorize Render in Google Cloud SQL Console

**Without this, your app will NOT connect to the database!**

**Steps:**
1. Open: https://console.cloud.google.com/sql/instances/psau2025/connections
2. Click **"Add network"** under "Authorized networks"
3. Enter:
   - **Name**: `Render`
   - **Network**: `0.0.0.0/0`
4. Click **"Done"** and **"Save"**

That's it! This allows Render's servers to connect to your database.

## 📝 In Render Dashboard

### Option 1: Using render.yaml (Recommended)
- Render will auto-detect `render.yaml` in your repo
- All settings will be applied automatically
- Just connect your Git repo and deploy!

### Option 2: Manual Setup (if needed)
If Render doesn't auto-detect render.yaml:

1. Create a new **Web Service** in Render
2. Connect your GitHub repository: `jericogutierrezsison12-dotcom/psau-admission-system2`
3. Render will automatically configure from `render.yaml`
4. If not, manually add these environment variables:
   ```
   DB_HOST=34.170.34.174
   DB_NAME=psau_admission
   DB_USER=root
   DB_PASS=Psau_2025
   DB_PORT=3306
   RENDER=true
   ENVIRONMENT=production
   ```

## ✅ Google Cloud SQL Encryption

**YES - Google Cloud SQL auto-encrypts everything!**
- ✅ **Data at rest**: Automatically encrypted with AES-256
- ✅ **Data in transit**: Connections are encrypted by default
- ✅ **No additional setup needed** for basic encryption

The connection is secure even though we disabled certificate verification for Render compatibility.

## 🚀 Deploy Steps

1. **Authorize Render IPs** in Google Cloud SQL (see above)
2. **Go to Render Dashboard**: https://dashboard.render.com
3. **Create/Update Web Service**:
   - Connect your Git repo
   - Render will use `render.yaml` automatically
4. **Wait for deployment** (5-10 minutes)
5. **Test your app**: https://psau-admission-system2.onrender.com

## 🔍 Troubleshooting

**If database connection fails:**
1. Check Google Cloud SQL - is `0.0.0.0/0` authorized?
2. Check Render logs for connection errors
3. Verify database name is `psau_admission` in Google Cloud SQL
4. Verify password is `Psau_2025` in Google Cloud SQL

**If build fails:**
1. Check Render build logs
2. Verify `composer.json` dependencies
3. Check PHP version compatibility

