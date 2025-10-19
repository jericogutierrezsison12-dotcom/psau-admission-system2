@echo off
echo ========================================
echo PSAU ADMISSION SYSTEM - RENDER DEPLOYMENT
echo ========================================
echo.

echo [1/6] Opening Render.com for account creation...
start https://render.com
echo.

echo [2/6] Opening PlanetScale for database setup...
start https://planetscale.com
echo.

echo [3/6] Opening Railway as database alternative...
start https://railway.app
echo.

echo [4/6] Opening GitHub repository...
start https://github.com/jericogutierrezsison12-dotcom/psau-admission-system
echo.

echo [5/6] Opening Firebase Console...
start https://console.firebase.google.com/u/0/project/psau-admission-system
echo.

echo [6/6] Deployment preparation complete!
echo.
echo ========================================
echo DEPLOYMENT INSTRUCTIONS:
echo ========================================
echo.
echo 1. RENDER SETUP:
echo    - Create account at render.com
echo    - Connect GitHub repository
echo    - Configure service settings
echo.
echo 2. DATABASE SETUP:
echo    - Choose PlanetScale (free) or Railway (free)
echo    - Create MySQL database
echo    - Import psau_admission.sql
echo.
echo 3. ENVIRONMENT VARIABLES:
echo    - Add database credentials to Render
echo    - Add Firebase configuration
echo.
echo 4. DEPLOY:
echo    - Click "Create Web Service"
echo    - Wait for deployment
echo    - Test your system
echo.
echo ========================================
echo YOUR SYSTEM WILL BE LIVE AT:
echo ========================================
echo Render: https://psau-admission-system.onrender.com
echo GitHub: https://github.com/jericogutierrezsison12-dotcom/psau-admission-system
echo Firebase: https://console.firebase.google.com/u/0/project/psau-admission-system
echo ========================================
echo.
echo Total deployment time: ~15 minutes
echo Total cost: $0/month (Free tier)
echo.
pause
