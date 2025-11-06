# ⚠️ CRITICAL: Fix SMTP_USER Secret Now

## The Problem
Your Firebase `SMTP_USER` secret is **corrupted** with duplicated email:
- **Current (WRONG)**: `siriyaporn.kwangusan@gmail.comwsiriyaporn.kwangusan@gmail.com`
- **Should be**: `siriyaporn.kwangusan@gmail.com`

## Quick Fix (2 Steps)

### Step 1: Fix the Secret

Open PowerShell and run:

```powershell
cd "C:\xampp\htdocs\Development of AI-Assisted Admission System at PSAU\functions"
firebase functions:secrets:set SMTP_USER
```

**When prompted, copy and paste EXACTLY this (no spaces, no duplication):**
```
siriyaporn.kwangusan@gmail.com
```

Press Enter.

### Step 2: Redeploy

```powershell
cd ..
firebase deploy --only functions
```

## Verification

After fixing, verify it worked:

```powershell
cd functions
firebase functions:secrets:access SMTP_USER
```

You should see: `siriyaporn.kwangusan@gmail.com` (single, not duplicated)

## Why This Will Work

✅ All code files are correct (using single email)  
✅ Firebase function is correct  
✅ Only the secret is corrupted  
✅ After fixing, emails will work

## After Fixing

1. Test sending an email
2. Check logs: `firebase functions:log`
3. If still errors, verify SMTP_PASS: `firebase functions:secrets:set SMTP_PASS`

