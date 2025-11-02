# Git Commit and Deploy Instructions

## Step 1: Review Changes

All files have been updated:
- ✅ Domain changed to `psau-admission-system2.onrender.com`
- ✅ Database connection configured for Google Cloud SQL
- ✅ render.yaml updated with Google Cloud SQL credentials
- ✅ Database connection optimized for Render

## Step 2: Initialize Git (if not done)

```bash
git init
```

## Step 3: Add All Files

```bash
git add .
```

## Step 4: Commit Changes

```bash
git commit -m "Update to Google Cloud SQL and new Render domain

- Updated domain to psau-admission-system2.onrender.com
- Configured database connection for Google Cloud SQL (34.170.34.174)
- Updated render.yaml with Google Cloud SQL credentials
- Added Render-specific SSL configuration for database
- Updated all domain references in codebase"
```

## Step 5: Add Remote Repository (if not already added)

```bash
git remote add origin <your-github-repo-url>
# Or if already exists:
git remote set-url origin <your-github-repo-url>
```

## Step 6: Push to Repository

```bash
git push -u origin main
# Or if using master branch:
git push -u origin master
```

## Step 7: Deploy on Render

1. Go to Render Dashboard (https://dashboard.render.com)
2. If service doesn't exist:
   - Click "New" → "Web Service"
   - Connect your Git repository
   - Render will auto-detect `render.yaml`
3. If service already exists:
   - Go to your service
   - Click "Manual Deploy" → "Deploy latest commit"
   - Or wait for automatic deploy if auto-deploy is enabled

## Step 8: Configure Google Cloud SQL (IMPORTANT!)

Before deployment works, you MUST:

1. **Authorize Render IPs in Google Cloud SQL**:
   - Go to Google Cloud Console
   - Navigate to SQL Instances → `psau2025`
   - Click "Connections" tab
   - Under "Authorized networks", click "Add network"
   - Add: `0.0.0.0/0` (allows all IPs) OR get Render's IP ranges
   - Save

2. **Verify Database Credentials**:
   - Database: `psau_admission` must exist
   - Username: `root`
   - Password: `Psau_2025`
   - Port: `3306`

## Step 9: Verify Deployment

After deployment:
1. Check Render logs for any errors
2. Visit: `https://psau-admission-system2.onrender.com`
3. Test database connection by trying to login/register
4. Check Google Cloud SQL logs if connection fails

## Important Notes

- The database password is in `render.yaml` - keep your repository private or use Render environment variables
- Google Cloud SQL must allow connections from Render's IP ranges
- All environment variables are configured in `render.yaml`
- Database connection automatically detects Render environment and adjusts SSL settings

