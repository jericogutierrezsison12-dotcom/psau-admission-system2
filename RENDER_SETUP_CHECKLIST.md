# Render Deployment Checklist

## ✅ Already Done (via render.yaml)
- Database connection credentials configured
- All environment variables set
- Google Cloud SQL connection configured
- Domain updated to psau-admission-system2.onrender.com

## ⚠️ REQUIRED: Authorize Render IPs in Google Cloud SQL

**This is the ONLY critical step you must do manually:**

1. Go to Google Cloud Console: https://console.cloud.google.com
2. Navigate to: **SQL** → **Instances** → **psau2025**
3. Click on **"Connections"** tab
4. Under **"Authorized networks"**, click **"Add network"**
5. Add one of these:
   - **Network**: `0.0.0.0/0` (CIDR notation) - Allows all IPs (simplest)
   - **Name**: `Render-All`
   - Click **"Add"** and **"Save"**

**OR** (more secure but requires updating):
- Find Render's IP ranges (check Render documentation)
- Add specific IP ranges

### Why This Is Needed
Google Cloud SQL blocks all connections by default for security. You must explicitly allow Render's servers to connect.

## ✅ Optional: Verify Environment Variables in Render

If `render.yaml` doesn't automatically apply, you may need to manually set environment variables in Render Dashboard:

1. Go to Render Dashboard → Your Service → **Environment** tab
2. Verify these variables are set (they should auto-populate from render.yaml):
   - `DB_HOST=34.170.34.174`
   - `DB_NAME=psau_admission`
   - `DB_USER=root`
   - `DB_PASS=Psau_2025`
   - `DB_PORT=3306`
   - `RENDER=true`
   - `ENVIRONMENT=production`

## 🚀 After Deployment

1. Check Render build logs - should complete successfully
2. Check Render runtime logs - watch for database connection
3. Visit: `https://psau-admission-system2.onrender.com`
4. Test login/registration to verify database works

## 🔒 Security Notes

- Google Cloud SQL encrypts all data automatically (AES-256)
- Connections are encrypted in transit
- Certificate verification is disabled in code for Render compatibility
- Using `0.0.0.0/0` allows all IPs - consider restricting later for production

