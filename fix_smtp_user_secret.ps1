# Quick Fix Script for SMTP_USER Secret
# This will guide you to fix the corrupted SMTP_USER secret

Write-Host "========================================" -ForegroundColor Cyan
Write-Host "Fixing SMTP_USER Secret" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""

# Navigate to functions directory
$functionsDir = Join-Path $PSScriptRoot "functions"
if (-not (Test-Path $functionsDir)) {
    Write-Host "ERROR: functions directory not found!" -ForegroundColor Red
    Write-Host "Please run this script from the project root directory" -ForegroundColor Yellow
    exit 1
}

Set-Location $functionsDir

Write-Host "Current SMTP_USER value (showing first 50 chars for security):" -ForegroundColor Yellow
$currentUser = firebase functions:secrets:access SMTP_USER 2>&1 | Where-Object { $_ -notmatch "Add-Content" } | Select-Object -First 1
if ($currentUser) {
    $displayUser = if ($currentUser.Length -gt 50) { $currentUser.Substring(0, 50) + "..." } else { $currentUser }
    Write-Host $displayUser -ForegroundColor Red
    Write-Host ""
    
    if ($currentUser -like "*siriyaporn.kwangusan@gmail.com*siriyaporn.kwangusan@gmail.com*") {
        Write-Host "✗ CONFIRMED: SMTP_USER is corrupted (duplicated)" -ForegroundColor Red
        Write-Host ""
        Write-Host "Fixing now..." -ForegroundColor Yellow
        Write-Host ""
        Write-Host "You will be prompted to enter the email." -ForegroundColor Cyan
        Write-Host "Enter EXACTLY: siriyaporn.kwangusan@gmail.com" -ForegroundColor Green
        Write-Host "(Copy and paste the line above)" -ForegroundColor Cyan
        Write-Host ""
        Write-Host "Press any key to continue..." -ForegroundColor Yellow
        $null = $Host.UI.RawUI.ReadKey("NoEcho,IncludeKeyDown")
        
        # Set the secret
        firebase functions:secrets:set SMTP_USER
        
        Write-Host ""
        Write-Host "Verifying fix..." -ForegroundColor Yellow
        Start-Sleep -Seconds 2
        
        $newUser = firebase functions:secrets:access SMTP_USER 2>&1 | Where-Object { $_ -notmatch "Add-Content" } | Select-Object -First 1
        
        if ($newUser -eq "siriyaporn.kwangusan@gmail.com") {
            Write-Host ""
            Write-Host "✓ SUCCESS! SMTP_USER is now correct!" -ForegroundColor Green
            Write-Host "  Value: $newUser" -ForegroundColor Green
            Write-Host ""
            Write-Host "Next step: Redeploy the function" -ForegroundColor Cyan
            Write-Host "  cd .." -ForegroundColor Yellow
            Write-Host "  firebase deploy --only functions" -ForegroundColor Yellow
        } else {
            Write-Host ""
            Write-Host "⚠ Verification: Current value is:" -ForegroundColor Yellow
            Write-Host "  $newUser" -ForegroundColor Yellow
            if ($newUser -ne "siriyaporn.kwangusan@gmail.com") {
                Write-Host ""
                Write-Host "✗ Still incorrect. Please try again manually:" -ForegroundColor Red
                Write-Host "  firebase functions:secrets:set SMTP_USER" -ForegroundColor Yellow
            }
        }
    } else {
        Write-Host "✓ SMTP_USER appears to be correct (no duplication detected)" -ForegroundColor Green
    }
} else {
    Write-Host "Could not read SMTP_USER secret" -ForegroundColor Yellow
    Write-Host "Setting it now..." -ForegroundColor Cyan
    Write-Host ""
    Write-Host "When prompted, enter: siriyaporn.kwangusan@gmail.com" -ForegroundColor Green
    firebase functions:secrets:set SMTP_USER
}

Set-Location $PSScriptRoot

