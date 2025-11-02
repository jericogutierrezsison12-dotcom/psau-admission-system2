# White Screen Issue - Solutions

## Problem
Getting a white screen when accessing `https://psau-admission-system2.onrender.com/public/`

## Root Causes & Solutions

### 1. ❌ Wrong URL Path
**Problem**: Since Render uses `public/` as the document root, you should NOT access `/public/`

**Solution**: Use the correct URL:
- ✅ **Correct**: `https://psau-admission-system2.onrender.com/`
- ✅ **Correct**: `https://psau-admission-system2.onrender.com/index.php`
- ❌ **Wrong**: `https://psau-admission-system2.onrender.com/public/` (this looks for `/public/public/`)

### 2. ⚠️ Database Connection Error
**Problem**: If Google Cloud SQL connection fails, it causes a white screen

**Solution**: 
1. **Check Render Logs**: Go to Render Dashboard → Your Service → Logs tab
2. **Authorize Render IPs**: 
   - Go to Google Cloud Console → SQL Instances → psau2025 → Connections
   - Add network: `0.0.0.0/0` under "Authorized networks"
3. **Verify Database Name**: Ensure database `psau_admission` exists in Google Cloud SQL

### 3. 🔍 Check PHP Errors
**Problem**: PHP fatal errors cause white screens

**Solution**: Check Render logs for:
- Fatal errors
- Parse errors
- Missing files
- Permission issues

### 4. ✅ Error Handling Improved
I've updated the code to:
- Show friendly error messages instead of white screen
- Log errors to Render logs
- Display database connection errors with helpful tips

## Quick Diagnostic Steps

1. **Try Correct URL**: `https://psau-admission-system2.onrender.com/`

2. **Check Render Logs**:
   - Go to Render Dashboard
   - Click your service
   - Go to "Logs" tab
   - Look for PHP errors or database connection errors

3. **Test Health Endpoint**: 
   - Try: `https://psau-admission-system2.onrender.com/health.php`
   - Should return JSON with status

4. **Verify Environment Variables**:
   - In Render Dashboard → Environment tab
   - Verify all DB_* variables are set

5. **Check Google Cloud SQL**:
   - Instance is running
   - Render IPs are authorized (`0.0.0.0/0`)
   - Database `psau_admission` exists
   - Password is `Psau_2025`

## After Fixing

After pushing the fixes, the app will show helpful error messages instead of white screens, making debugging easier.

