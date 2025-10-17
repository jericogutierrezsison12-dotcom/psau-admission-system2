@echo off
echo 🚀 PSAU Admission System - GitHub Upload Script
echo.

echo 📁 Current directory: %CD%
echo.

echo ⚠️  Make sure you have Git installed and GitHub account ready!
echo.

set /p github_username="Enter your GitHub username: "
set /p repo_name="Enter repository name (default: psau-admission-system): "

if "%repo_name%"=="" set repo_name=psau-admission-system

echo.
echo 🔧 Setting up Git repository...
git init
git add .
git commit -m "Initial commit - PSAU AI-Assisted Admission System"

echo.
echo 🌿 Setting up main branch...
git branch -M main

echo.
echo 🔗 Adding remote origin...
git remote add origin https://github.com/%github_username%/%repo_name%.git

echo.
echo 📤 Pushing to GitHub...
git push -u origin main

echo.
echo ✅ Done! Your code is now on GitHub.
echo 🌐 Repository URL: https://github.com/%github_username%/%repo_name%
echo.
echo 📋 Next steps:
echo 1. Go to https://dashboard.render.com/
echo 2. Create new Web Service
echo 3. Connect your GitHub repository
echo 4. Configure deployment settings
echo.
pause
