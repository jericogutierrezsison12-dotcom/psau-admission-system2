# Fix Firebase SMTP Secrets
# This script helps fix the corrupted SMTP_USER secret

Write-Host "========================================" -ForegroundColor Cyan
Write-Host "Firebase SMTP Secrets Fix" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""

# Check current directory
$currentDir = Get-Location
if (-not (Test-Path "functions\index.js")) {
    Write-Host "ERROR: Please run this script from the project root directory" -ForegroundColor Red
    Write-Host "Current directory: $currentDir" -ForegroundColor Yellow
    exit 1
}

Write-Host "Current SMTP_USER value:" -ForegroundColor Yellow
$currentUser = firebase functions:secrets:access SMTP_USER 2>&1 | Select-String -Pattern "siriyaporn" | ForEach-Object { $_.Line.Trim() }
Write-Host $currentUser -ForegroundColor Red
Write-Host ""

if ($currentUser -like "*siriyaporn.kwangusan@gmail.com*siriyaporn.kwangusan@gmail.com*") {
    Write-Host "✓ Confirmed: SMTP_USER is corrupted (duplicated)" -ForegroundColor Red
    Write-Host ""
    Write-Host "Fixing SMTP_USER..." -ForegroundColor Yellow
    Write-Host "When prompted, enter: siriyaporn.kwangusan@gmail.com" -ForegroundColor Cyan
    Write-Host ""
    
    # Set the correct email
    firebase functions:secrets:set SMTP_USER
    
    Write-Host ""
    Write-Host "Verifying SMTP_USER..." -ForegroundColor Yellow
    $newUser = firebase functions:secrets:access SMTP_USER 2>&1 | Select-String -Pattern "siriyaporn" | ForEach-Object { $_.Line.Trim() }
    
    if ($newUser -eq "siriyaporn.kwangusan@gmail.com") {
        Write-Host "✓ SMTP_USER fixed successfully!" -ForegroundColor Green
    } else {
        Write-Host "✗ SMTP_USER still incorrect. Please run manually:" -ForegroundColor Red
        Write-Host "  firebase functions:secrets:set SMTP_USER" -ForegroundColor Yellow
        Write-Host "  Then enter: siriyaporn.kwangusan@gmail.com" -ForegroundColor Yellow
    }
} else {
    Write-Host "SMTP_USER appears to be correct" -ForegroundColor Green
}

Write-Host ""
Write-Host "Checking SMTP_PASS..." -ForegroundColor Yellow
$passLength = (firebase functions:secrets:access SMTP_PASS 2>&1 | Measure-Object -Character).Characters

if ($passLength -eq 16) {
    Write-Host "✓ SMTP_PASS length is correct (16 characters)" -ForegroundColor Green
} else {
    Write-Host "⚠ SMTP_PASS length is $passLength (should be 16)" -ForegroundColor Yellow
    Write-Host "To fix, run: firebase functions:secrets:set SMTP_PASS" -ForegroundColor Cyan
    Write-Host "Then enter your 16-character Gmail App Password" -ForegroundColor Cyan
}

Write-Host ""
Write-Host "Next steps:" -ForegroundColor Cyan
Write-Host "1. If SMTP_USER was fixed, redeploy: firebase deploy --only functions" -ForegroundColor Yellow
Write-Host "2. Test sending an email" -ForegroundColor Yellow
Write-Host "3. Check logs: firebase functions:log" -ForegroundColor Yellow

