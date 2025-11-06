# Test Firebase Secrets and Email Service
# This script verifies the Firebase secrets and tests the email function

Write-Host "========================================" -ForegroundColor Cyan
Write-Host "Firebase Secrets & Email Service Test" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""

$functionsDir = Join-Path $PSScriptRoot "functions"
if (-not (Test-Path $functionsDir)) {
    Write-Host "ERROR: functions directory not found!" -ForegroundColor Red
    exit 1
}

Set-Location $functionsDir

# Test 1: Check SMTP_USER
Write-Host "=== Test 1: SMTP_USER Secret ===" -ForegroundColor Yellow
$smtpUser = firebase functions:secrets:access SMTP_USER 2>&1 | Where-Object { $_ -notmatch "Add-Content" } | Select-Object -First 1

if ($smtpUser) {
    $userLength = $smtpUser.Length
    $isDuplicated = $smtpUser -like "*siriyaporn.kwangusan@gmail.com*siriyaporn.kwangusan@gmail.com*"
    $isCorrect = $smtpUser -eq "siriyaporn.kwangusan@gmail.com"
    
    Write-Host "Value (first 50 chars): " -NoNewline
    if ($userLength -gt 50) {
        Write-Host $smtpUser.Substring(0, 50) + "..." -ForegroundColor $(if ($isCorrect) { "Green" } else { "Red" })
    } else {
        Write-Host $smtpUser -ForegroundColor $(if ($isCorrect) { "Green" } else { "Red" })
    }
    Write-Host "Length: $userLength characters" -ForegroundColor $(if ($userLength -eq 30) { "Green" } else { "Red" })
    
    if ($isDuplicated) {
        Write-Host "❌ ERROR: Email is DUPLICATED (corrupted)" -ForegroundColor Red
        Write-Host "   Expected: siriyaporn.kwangusan@gmail.com" -ForegroundColor Yellow
        Write-Host "   Current:  $smtpUser" -ForegroundColor Red
        Write-Host ""
        Write-Host "FIX REQUIRED: Run 'firebase functions:secrets:set SMTP_USER'" -ForegroundColor Cyan
        Write-Host "   Then enter: siriyaporn.kwangusan@gmail.com" -ForegroundColor Green
    } elseif ($isCorrect) {
        Write-Host "✅ SMTP_USER is correct" -ForegroundColor Green
    } else {
        Write-Host "⚠️  SMTP_USER format may be incorrect" -ForegroundColor Yellow
    }
} else {
    Write-Host "❌ SMTP_USER secret not found" -ForegroundColor Red
}

Write-Host ""

# Test 2: Check SMTP_PASS
Write-Host "=== Test 2: SMTP_PASS Secret ===" -ForegroundColor Yellow
$smtpPass = firebase functions:secrets:access SMTP_PASS 2>&1 | Where-Object { $_ -notmatch "Add-Content" } | Select-Object -First 1

if ($smtpPass) {
    $passLength = $smtpPass.Length
    $hasSpaces = $smtpPass -match '\s'
    
    Write-Host "Length: $passLength characters" -ForegroundColor $(if ($passLength -eq 16) { "Green" } else { "Red" })
    Write-Host "Has spaces: $(if ($hasSpaces) { 'YES (should be NO)' } else { 'NO (correct)' })" -ForegroundColor $(if ($hasSpaces) { "Red" } else { "Green" })
    
    if ($passLength -eq 16 -and -not $hasSpaces) {
        Write-Host "✅ SMTP_PASS format is correct (16 characters, no spaces)" -ForegroundColor Green
    } else {
        Write-Host "❌ SMTP_PASS format is incorrect" -ForegroundColor Red
        Write-Host "   Expected: 16 characters, no spaces" -ForegroundColor Yellow
        Write-Host "   Current:  $passLength characters" -ForegroundColor Red
        if ($hasSpaces) {
            Write-Host "   WARNING: Password contains spaces (remove them!)" -ForegroundColor Red
        }
        Write-Host ""
        Write-Host "FIX REQUIRED: Run 'firebase functions:secrets:set SMTP_PASS'" -ForegroundColor Cyan
        Write-Host "   Then enter your 16-character App Password (no spaces)" -ForegroundColor Green
    }
} else {
    Write-Host "❌ SMTP_PASS secret not found" -ForegroundColor Red
}

Write-Host ""

# Test 3: Test Firebase Function
Write-Host "=== Test 3: Firebase Function Test ===" -ForegroundColor Yellow
Write-Host "Testing email function..." -ForegroundColor Cyan

$testPayload = @{
    to = "siriyaporn.kwangusan@gmail.com"
    subject = "Firebase Function Test"
    html = "<p>This is a test email from Firebase Function.</p>"
} | ConvertTo-Json

try {
    $functionUrl = "https://us-central1-psau-admission-system-f55f8.cloudfunctions.net/sendEmail"
    $response = Invoke-RestMethod -Uri $functionUrl -Method Post -Body $testPayload -ContentType "application/json" -ErrorAction Stop
    
    Write-Host "✅ Function responded successfully" -ForegroundColor Green
    Write-Host "Response: $($response | ConvertTo-Json)" -ForegroundColor Green
} catch {
    Write-Host "❌ Function test failed" -ForegroundColor Red
    Write-Host "Error: $($_.Exception.Message)" -ForegroundColor Red
    
    if ($_.Exception.Response) {
        $statusCode = $_.Exception.Response.StatusCode.value__
        Write-Host "HTTP Status: $statusCode" -ForegroundColor Yellow
        
        try {
            $reader = New-Object System.IO.StreamReader($_.Exception.Response.GetResponseStream())
            $responseBody = $reader.ReadToEnd()
            Write-Host "Response: $responseBody" -ForegroundColor Yellow
        } catch {
            Write-Host "Could not read response body" -ForegroundColor Yellow
        }
    }
}

Write-Host ""
Write-Host "=== Summary ===" -ForegroundColor Cyan
Write-Host "1. Check the SMTP_USER value above" -ForegroundColor Yellow
Write-Host "2. Check the SMTP_PASS length (should be 16)" -ForegroundColor Yellow
Write-Host "3. If secrets are incorrect, fix them and redeploy:" -ForegroundColor Yellow
Write-Host "   firebase functions:secrets:set SMTP_USER" -ForegroundColor Cyan
Write-Host "   firebase functions:secrets:set SMTP_PASS" -ForegroundColor Cyan
Write-Host "   cd .." -ForegroundColor Cyan
Write-Host "   firebase deploy --only functions" -ForegroundColor Cyan
Write-Host ""
Write-Host "4. Check Firebase logs:" -ForegroundColor Yellow
Write-Host "   firebase functions:log" -ForegroundColor Cyan

Set-Location $PSScriptRoot


