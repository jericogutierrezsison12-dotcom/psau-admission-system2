# Deployment Guide - PSAU Admission System to Render

## Prerequisites
1. Google Cloud SQL instance set up with database `psau_admission`
2. Database password: `Psau_2025`
3. Google Cloud SQL IP: `34.170.34.174`
4. Render account

## Step 1: Google Cloud SQL Configuration

### Authorize Render IP Ranges
Render uses dynamic IPs, so you need to allow all IPs or use these ranges:

1. Go to Google Cloud Console → SQL Instances → `psau2025`
2. Click on "Connections"
3. Under "Authorized networks", add:
   - `0.0.0.0/0` (Allows all IPs - for development)
   - Or add specific Render IP ranges when known

**Recommended**: Use `0.0.0.0/0` with strong password for production, or restrict to known Render IPs.

### Verify Database Credentials
- Host: `34.170.34.174`
- Port: `3306`
- Database: `psau_admission`
- Username: `root`
- Password: `Psau_2025`

## Step 2: Render Deployment Configuration

### Environment Variables in Render Dashboard

After deploying, set these environment variables in Render Dashboard:

1. Go to your service → Environment tab
2. Add/Update these variables:

```
DB_HOST=34.170.34.174
DB_NAME=psau_admission
DB_USER=root
DB_PASS=Psau_2025
DB_PORT=3306
RENDER=true
ENVIRONMENT=production
```

### Or use render.yaml (already configured)

The `render.yaml` file is already configured with:
- Google Cloud SQL connection details
- Firebase configuration
- All required environment variables

## Step 3: Deploy to Render

### Option A: Using Git (Recommended)

1. **Initialize Git repository** (if not already done):
```bash
git init
git add .
git commit -m "Initial commit - PSAU Admission System"
```

2. **Add Render as remote**:
```bash
# In Render Dashboard, create a new Web Service
# Connect to your GitHub/GitLab repository
# Render will auto-deploy when you push
```

3. **Push to repository**:
```bash
git remote add origin <your-repository-url>
git push -u origin main
```

### Option B: Using Render Dashboard

1. Go to Render Dashboard
2. Click "New" → "Web Service"
3. Connect your Git repository
4. Render will detect `render.yaml` automatically
5. Click "Create Web Service"

## Step 4: Verify Deployment

1. **Check Build Logs**: Ensure build completes successfully
2. **Check Runtime Logs**: Verify no database connection errors
3. **Test Application**: Visit `https://psau-admission-system2.onrender.com`
4. **Test Database Connection**: Try logging in or registering

## Step 5: Troubleshooting

### Database Connection Errors

If you see "Connection refused" or timeout errors:

1. **Verify IP Authorization**:
   - Go to Google Cloud SQL Console
   - Check "Authorized networks" includes Render IPs or `0.0.0.0/0`

2. **Check Environment Variables**:
   - Verify all DB_* variables are set correctly in Render
   - Check they match Google Cloud SQL credentials

3. **Check Firewall Rules**:
   - Ensure Google Cloud SQL allows connections on port 3306
   - Check security group rules

### Common Issues

**Issue**: "Access denied for user"
- **Solution**: Verify password is `Psau_2025` and username is `root`

**Issue**: "Unknown database 'psau_admission'"
- **Solution**: Verify database name exists in Google Cloud SQL

**Issue**: "Connection timeout"
- **Solution**: Add Render IP ranges to Google Cloud SQL authorized networks

**Issue**: Build fails
- **Solution**: Check `composer.json` dependencies and PHP version

## Step 6: Post-Deployment

1. **Import Database Schema** (if not already done):
   - Use phpMyAdmin or MySQL client
   - Connect to Google Cloud SQL instance
   - Import your SQL dump file

2. **Set up Admin Account**:
   - Register an admin account through the registration page
   - Or insert directly into database

3. **Test All Features**:
   - User registration
   - Admin login
   - File uploads
   - Email notifications
   - Database operations

## Important Notes

- **Google Cloud SQL**: Make sure the database is created and schema is imported
- **IP Authorization**: Render IPs need to be authorized in Google Cloud SQL
- **SSL**: Connection uses SSL but certificate verification is disabled for Render compatibility
- **Environment Variables**: All database credentials are in `render.yaml` and should be set in Render Dashboard

## Support

For issues:
1. Check Render logs
2. Check Google Cloud SQL logs
3. Verify environment variables
4. Test database connection from Render service

