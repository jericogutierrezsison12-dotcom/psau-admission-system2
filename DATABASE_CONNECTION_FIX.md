# Database Connection Timeout - Fix Guide

## Error: `SQLSTATE[HY000] [2002] Connection timed out`

This error means **Render's servers cannot reach your Google Cloud SQL instance** because the IP addresses are blocked.

## ✅ Solution Steps

### Step 1: Authorize Render IP Addresses in Google Cloud SQL

**This is the MOST IMPORTANT step!**

1. **Go to Google Cloud Console:**
   - URL: https://console.cloud.google.com/sql/instances/psau2025/connections
   - Or navigate: Google Cloud Console → SQL → Instances → `psau2025` → Connections tab

2. **Add Authorized Network:**
   - Click **"Add network"** button under "Authorized networks" section
   - Enter:
     - **Name**: `Render-All-IPs`
     - **Network**: `0.0.0.0/0` (CIDR notation)
   - Click **"Done"**
   - Click **"Save"** at the bottom

3. **Wait 1-2 minutes** for the change to propagate

### Step 2: Verify Google Cloud SQL Instance Status

1. Go to: https://console.cloud.google.com/sql/instances
2. Check that instance `psau2025` shows status: **"Running"** (green)
3. If stopped, click **"Start"**

### Step 3: Check Public IP Address

1. In Google Cloud Console → SQL → Instances → `psau2025`
2. Click on the instance name
3. Under **"Connect to this instance"** → **"Public IP"**
4. Verify the IP is: `34.170.34.174`
5. Make sure **"Public IP"** is enabled (not just Private IP)

### Step 4: Verify Database Credentials

**In Render Dashboard:**
1. Go to your service in Render
2. Click **"Environment"** tab
3. Verify these environment variables exist:
   - `DB_HOST` = `34.170.34.174`
   - `DB_NAME` = `psau_admission`
   - `DB_USER` = `root`
   - `DB_PASS` = `Psau_2025`
   - `DB_PORT` = `3306`

### Step 5: Test Connection

After authorizing IPs, wait 2-3 minutes, then:
1. Visit: `https://psau-admission-system2.onrender.com/`
2. The error should be gone
3. If still timing out, check Render logs for more details

## 🔍 Alternative: Find Render's Specific IP (More Secure)

If you want to be more specific (instead of `0.0.0.0/0`):

1. Check Render documentation for their IP ranges
2. Or check Render logs for connection attempts - they may show the source IP
3. Add specific IP ranges instead of `0.0.0.0/0`

**Note**: For free tier/test deployments, `0.0.0.0/0` is fine since you have a strong password.

## 🚨 Common Issues

### Issue 1: "Save" button is grayed out
- Make sure you're editing the instance (click the instance name first)

### Issue 2: Still timing out after adding IP
- Wait 2-3 minutes for changes to propagate
- Restart your Render service
- Double-check the IP: `0.0.0.0/0` (not `0.0.0.0`)

### Issue 3: Instance not running
- Go to instances list and click "Start" if it's stopped

### Issue 4: Wrong database name
- Verify database `psau_admission` exists in the instance
- Go to: Google Cloud Console → SQL → Databases → Check if `psau_admission` exists

## 📝 Quick Checklist

- [ ] Google Cloud SQL instance is **Running**
- [ ] Public IP is **enabled** (`34.170.34.174`)
- [ ] Authorized network `0.0.0.0/0` is **added and saved**
- [ ] Render environment variables are **set correctly**
- [ ] Waited **2-3 minutes** after adding network
- [ ] Database `psau_admission` **exists** in the instance

## 🎯 Expected Result

After completing these steps:
- Connection timeout error should disappear
- Homepage should load successfully
- Database queries should work

## 📞 Still Having Issues?

If still not working after 5 minutes:
1. Check Render logs (Dashboard → Logs tab)
2. Verify the exact error message
3. Check Google Cloud SQL logs for connection attempts
4. Make sure you're using the correct Google Cloud project

