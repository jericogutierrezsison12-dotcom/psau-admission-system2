# Fix SMTP_USER Secret - Step by Step

## Problem Identified
The `SMTP_USER` secret in Firebase is **CORRUPTED** (duplicated):
- **Current (WRONG)**: `siriyaporn.kwangusan@gmail.comwsiriyaporn.kwangusan@gmail.com`
- **Should be**: `siriyaporn.kwangusan@gmail.com`

## Root Cause
The email was accidentally duplicated when setting the secret, causing Gmail authentication to fail.

## Solution

### Step 1: Fix SMTP_USER Secret

Open PowerShell and run:

```powershell
cd "C:\xampp\htdocs\Development of AI-Assisted Admission System at PSAU\functions"
firebase functions:secrets:set SMTP_USER
```

**When prompted, enter EXACTLY (no extra characters, no duplication):**
```
siriyaporn.kwangusan@gmail.com
```

**IMPORTANT**: 
- Type it ONCE only
- No spaces before or after
- No duplication
- Press Enter after typing

### Step 2: Verify the Fix

```powershell
firebase functions:secrets:access SMTP_USER
```

**Expected output**: `siriyaporn.kwangusan@gmail.com` (exactly 30 characters)

### Step 3: Verify SMTP_PASS (Already Correct)

```powershell
firebase functions:secrets:access SMTP_PASS
```

**Expected output**: `wplbjxshsdmnunva` (exactly 16 characters, no spaces)

### Step 4: Redeploy Functions

```powershell
cd ..
firebase deploy --only functions
```

### Step 5: Test Email Service

After deployment, test the email:

```powershell
php test_email_service.php
```

Or visit in browser: `http://localhost/test_email_service.php`

## Verification Checklist

- [ ] SMTP_USER is exactly `siriyaporn.kwangusan@gmail.com` (30 chars)
- [ ] SMTP_PASS is exactly `wplbjxshsdmnunva` (16 chars, no spaces)
- [ ] Functions redeployed successfully
- [ ] Test email sent successfully
- [ ] No "Invalid login" errors

## Current Status

✅ **SMTP_PASS**: Correct (`wplbjxshsdmnunva`)
❌ **SMTP_USER**: Corrupted (duplicated - needs fix)
✅ **Function Code**: Updated with better error detection
✅ **Function Deployed**: Latest version is live

## Next Steps

1. Run Step 1 above to fix SMTP_USER
2. Run Step 4 to redeploy
3. Run Step 5 to test

The function will now detect if SMTP_USER is corrupted and return a clear error message.

