@echo off
echo ========================================
echo PSAU Admission System Deployment Script
echo ========================================
echo.

echo [1/5] Checking Git status...
git status
echo.

echo [2/5] Adding deployment files...
git add deploy-config.json
git add public/index.html
git add deploy.bat
echo.

echo [3/5] Committing deployment files...
git commit -m "Add deployment configuration and web hosting files"
echo.

echo [4/5] Pushing to GitHub...
git push origin main
echo.

echo [5/5] Deployment files ready!
echo.
echo ========================================
echo DEPLOYMENT OPTIONS:
echo ========================================
echo.
echo 1. FIREBASE HOSTING:
echo    - Go to: https://console.firebase.google.com
echo    - Select project: psau-admission-system
echo    - Click Hosting > Get Started
echo    - Connect GitHub repository
echo.
echo 2. GITHUB PAGES:
echo    - Go to: https://github.com/jericogutierrezsison12-dotcom/psau-admission-system
echo    - Settings > Pages
echo    - Source: Deploy from branch > main
echo.
echo 3. NETLIFY:
echo    - Go to: https://netlify.com
echo    - New site from Git
echo    - Connect GitHub repository
echo.
echo 4. VERCEL:
echo    - Go to: https://vercel.com
echo    - New Project
echo    - Import from GitHub
echo.
echo ========================================
echo Your site will be available at:
echo - Firebase: https://psau-admission-system.web.app
echo - GitHub: https://jericogutierrezsison12-dotcom.github.io/psau-admission-system
echo ========================================
echo.
pause
