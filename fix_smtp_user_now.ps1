# Fix SMTP_USER Secret - Remove Duplication
# This script fixes the corrupted SMTP_USER secret

Write-Host "========================================" -ForegroundColor Cyan
Write-Host "Fix SMTP_USER Secret" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""

$functionsDir = Join-Path $PSScriptRoot "functions"
if (-not (Test-Path $functionsDir)) {
    Write-Host "ERROR: functions directory not found!" -ForegroundColor Red
    exit 1
}

Set-Location $functionsDir

Write-Host "Current SMTP_USER value:" -ForegroundColor Yellow
$currentValue = firebase functions:secrets:access SMTP_USER 2>&1 | Where-Object { $_ -notmatch "Add-Content" -and $_ -notmatch "CategoryInfo" } | Select-Object -First 1
Write-Host $currentValue -ForegroundColor $(if ($currentValue -eq "siriyaporn.kwangusan@gmail.com") { "Green" } else { "Red" })
Write-Host ""

if ($currentValue -ne "siriyaporn.kwangusan@gmail.com") {
    Write-Host "❌ SMTP_USER is CORRUPTED (duplicated or incorrect)" -ForegroundColor Red
    Write-Host ""
    Write-Host "Fixing now..." -ForegroundColor Cyan
    Write-Host ""
    Write-Host "When prompted, enter EXACTLY:" -ForegroundColor Yellow
    Write-Host "  siriyaporn.kwangusan@gmail.com" -ForegroundColor Green
    Write-Host ""
    Write-Host "Press Enter to continue..." -ForegroundColor Cyan
    Read-Host
    
    firebase functions:secrets:set SMTP_USER
    
    Write-Host ""
    Write-Host "Verifying fix..." -ForegroundColor Cyan
    $newValue = firebase functions:secrets:access SMTP_USER 2>&1 | Where-Object { $_ -notmatch "Add-Content" -and $_ -notmatch "CategoryInfo" } | Select-Object -First 1
    
    if ($newValue -eq "siriyaporn.kwangusan@gmail.com") {
        Write-Host "✅ SMTP_USER fixed successfully!" -ForegroundColor Green
        Write-Host ""
        Write-Host "Now redeploying functions..." -ForegroundColor Cyan
        Set-Location ..
        firebase deploy --only functions
    } else {
        Write-Host "❌ Fix failed. Value is still incorrect:" -ForegroundColor Red
        Write-Host $newValue -ForegroundColor Red
        Write-Host ""
        Write-Host "Please manually run:" -ForegroundColor Yellow
        Write-Host "  firebase functions:secrets:set SMTP_USER" -ForegroundColor Cyan
        Write-Host "  Then enter: siriyaporn.kwangusan@gmail.com" -ForegroundColor Green
    }
} else {
    Write-Host "✅ SMTP_USER is already correct!" -ForegroundColor Green
}

Set-Location $PSScriptRoot

