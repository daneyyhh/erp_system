@echo off
echo ========================================
echo   Project ERP: Cloud Deployment Tool
echo ========================================
echo.

:: Initialize Git
if not exist ".git" (
    echo [1/3] Initializing local repository...
    git init
    git add .
    git commit -m "Initialize Project ERP for Vercel Deployment"
) else (
    echo [1/3] Local repository already exists.
    git add .
    git commit -m "Update Project ERP Cloud Configuration"
)

echo.
echo [2/3] GitHub Authentication Required
echo Please create a NEW repository on github.com (e.g., 'project-erp')
echo.
:: Link to GitHub
git remote add origin https://github.com/daneyyhh/erp_system 2>nul
git remote set-url origin https://github.com/daneyyhh/erp_system
git branch -M main
git push -u origin main

echo.
echo [3/3] Final Step: Vercel Connection
echo 1. Go to Vercel.com
echo 2. Click 'Add New Project' -> Select this GitHub Repo
echo 3. Click 'Deploy'
echo.
echo Success! Your ERP is going global.
pause
