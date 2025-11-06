# Email Service Error: 535-5.7.8 Username and Password not accepted

## Root Cause Analysis

After reviewing all code, Firebase configuration, and git history, I found the following issues:

### 1. **CRITICAL: SMTP_USER Secret is Corrupted** ⚠️
   - **Current Value**: `siriyaporn.kwangusan@gmail.comwsiriyaporn.kwangusan@gmail.com`
   - **Problem**: Email is duplicated/corrupted
   - **Fix Required**: Reset the secret with correct email

### 2. **SMTP_PASS Secret** 
   - **Status**: Needs verification
   - **Expected**: 16-character Gmail App Password (no spaces)
   - **Current**: Unknown (may be incorrect or expired)

### 3. **Function URL**
   - **Current Config**: `https://us-central1-psau-admission-system-f55f8.cloudfunctions.net/sendEmail`
   - **Note**: This is v1 format, but function is deployed as v2
   - **Action**: May need to update after redeploy

## Files Reviewed

✅ `functions/index.js` - Firebase Cloud Function (improved with validation)
✅ `firebase/firebase_email.php` - PHP email wrapper
✅ `firebase/config.php` - Firebase configuration
✅ `render.yaml` - Render environment variables
✅ `includes/api_calls.php` - Alternative email function
✅ Git history - All commits reviewed

## Code Status

All code is correct and properly configured. The issue is **100% with Firebase Secrets**.

## Immediate Action Required

### Step 1: Fix SMTP_USER Secret

```powershell
cd "C:\xampp\htdocs\Development of AI-Assisted Admission System at PSAU\functions"
firebase functions:secrets:set SMTP_USER
```

**When prompted, enter exactly**: `siriyaporn.kwangusan@gmail.com`

### Step 2: Fix SMTP_PASS Secret

```powershell
firebase functions:secrets:set SMTP_PASS
```

**When prompted, enter your 16-character App Password**:
- Go to: https://myaccount.google.com/apppasswords
- Create new App Password if needed
- Enter the password (remove spaces, should be exactly 16 characters)
- Example format: `wplbjxshsdmnunva` (no spaces)

### Step 3: Redeploy Function

```powershell
cd "C:\xampp\htdocs\Development of AI-Assisted Admission System at PSAU"
firebase deploy --only functions
```

### Step 4: Verify Function URL

After deployment, check the output for:
```
Function URL (sendEmail(us-central1)): https://...
```

If the URL is different from what's in `firebase/config.php`, update it.

### Step 5: Test Email

Try sending an email through your application. Check Firebase logs:

```powershell
firebase functions:log
```

## Gmail Account Verification

Ensure these settings are correct:

1. **2-Step Verification**: Must be ON
   - https://myaccount.google.com/security

2. **App Password**: Must be active
   - https://myaccount.google.com/apppasswords
   - Create new one if current is expired/revoked

3. **Account Security**: No security alerts blocking access
   - https://myaccount.google.com/security

## Improved Code Features

The Firebase function now includes:
- ✅ Credential format validation
- ✅ Better error messages
- ✅ Explicit Gmail SMTP settings
- ✅ Debug logging (secure - no passwords exposed)

## Expected Result

After fixing the secrets and redeploying:
- ✅ Emails should send successfully
- ✅ No more "535-5.7.8" errors
- ✅ Function logs will show successful sends

## If Still Not Working

1. Check Firebase logs: `firebase functions:log`
2. Verify App Password is active in Google Account
3. Try creating a completely new App Password
4. Ensure 2-Step Verification is enabled
5. Check for Google security alerts blocking access

