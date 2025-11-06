# Fix Email Service Issues

## Critical Issues Found:

1. **SMTP_USER Secret is Corrupted**: The secret contains duplicated email: `siriyaporn.kwangusan@gmail.comwsiriyaporn.kwangusan@gmail.com`
2. **Function URL**: May need to be updated to Cloud Run format (v2 functions)
3. **SMTP_PASS**: Needs verification

## Steps to Fix:

### Step 1: Fix SMTP_USER Secret

Run these commands in PowerShell:

```powershell
cd "C:\xampp\htdocs\Development of AI-Assisted Admission System at PSAU\functions"
firebase functions:secrets:set SMTP_USER
```

When prompted, enter: `siriyaporn.kwangusan@gmail.com` (ONCE, no duplication)

### Step 2: Verify/Update SMTP_PASS Secret

```powershell
firebase functions:secrets:set SMTP_PASS
```

When prompted, enter your Gmail App Password (16 characters, no spaces):
- If you have the password: `wplbjxshsdmnunva` (remove spaces)
- If not, create a new one at: https://myaccount.google.com/apppasswords

### Step 3: Get Current Function URL

After fixing secrets, redeploy to get the current URL:

```powershell
cd "C:\xampp\htdocs\Development of AI-Assisted Admission System at PSAU"
firebase deploy --only functions
```

Look for the line: `Function URL (sendEmail(us-central1)): https://...`

Copy that URL and update it in:
- `firebase/config.php` (line 28)
- `render.yaml` (line 36)
- `includes/api_calls.php` (line 770)

### Step 4: Verify Gmail Settings

1. Go to: https://myaccount.google.com/security
2. Ensure **2-Step Verification** is ON
3. Go to: https://myaccount.google.com/apppasswords
4. Create a new App Password if needed:
   - App: Mail
   - Device: Other (Custom name) → "PSAU Admission System"
   - Copy the 16-character password (shown only once)

### Step 5: Test the Function

After fixing secrets and redeploying, test with:

```powershell
# Test the function directly
curl -X POST https://YOUR_FUNCTION_URL_HERE `
  -H "Content-Type: application/json" `
  -d '{\"to\":\"siriyaporn.kwangusan@gmail.com\",\"subject\":\"Test\",\"html\":\"<p>Test email</p>\"}'
```

## Current Configuration:

- **Email**: siriyaporn.kwangusan@gmail.com
- **App Password**: (needs to be set correctly)
- **Function URL**: (needs to be updated after redeploy)

## After Fixing:

1. Redeploy the function: `firebase deploy --only functions`
2. Update the function URL in all config files
3. Test sending an email
4. Check Firebase logs: `firebase functions:log`

