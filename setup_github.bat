@echo off
REM GitHub Setup Launcher for Student Attendance Monitoring System
REM This script helps you connect your project to GitHub

echo.
echo ========================================
echo GitHub Setup for Student Attendance System
echo ========================================
echo.
echo This script will help you:
echo 1. Initialize Git repository
echo 2. Create .gitignore file
echo 3. Add all files to Git
echo 4. Create initial commit
echo 5. Guide you through GitHub setup
echo.
echo Press any key to continue...
pause >nul

REM Check if Git is installed
git --version >nul 2>&1
if %errorlevel% neq 0 (
    echo.
    echo ERROR: Git is not installed!
    echo Please install Git first from: https://git-scm.com/
    echo Or run: winget install --id Git.Git -e --source winget
    echo.
    pause
    exit /b 1
)

echo.
echo ✓ Git is installed
echo.

REM Navigate to project directory
cd /d "%~dp0"

echo Working directory: %CD%
echo.

REM Initialize Git repository
echo Initializing Git repository...
git init
echo.

REM Create .gitignore
echo Creating .gitignore file...
(
echo # Database files
echo *.db
echo *.sqlite
echo *.sqlite3
echo.
echo # Logs
echo logs/*.log
echo logs/telegram_*.txt
echo *.log
echo.
echo # PHP sessions
echo session/
echo.
echo # Temporary files
echo *.tmp
echo *.temp
echo cache/
echo temp/
echo.
echo # IDE files
echo .vscode/
echo .idea/
echo *.swp
echo *.swo
echo.
echo # OS files
echo .DS_Store
echo Thumbs.db
echo.
echo # Node modules (if any)
echo node_modules/
echo.
echo # Environment files
echo .env
echo .env.local
echo .env.*.local
echo.
echo # Backup files
echo *.bak
echo backups/
echo.
echo # XAMPP specific
echo /xampp/
echo /apache/
echo /mysql/data/
) > .gitignore
echo.

REM Add all files
echo Adding files to Git...
git add .
echo.

REM Initial commit
echo Creating initial commit...
git commit -m "Initial commit: Student Attendance Monitoring System

Features:
- RFID-based attendance tracking
- Face recognition verification
- Web-based dashboard
- Telegram bot integration for notifications
- Student management system
- Section and year level management"
echo.

echo.
echo ========================================
echo GitHub Repository Setup Instructions
echo ========================================
echo.
echo Your code is now ready for GitHub!
echo.
echo NEXT STEPS:
echo.
echo 1. Go to https://github.com and sign in
echo 2. Click "New repository"
echo 3. Repository name: student-attendance-system
echo 4. Make it Public or Private
echo 5. DO NOT initialize with README (we already have one)
echo 6. Click "Create repository"
echo.
echo 7. Copy the repository URL from GitHub
echo 8. Run these commands (replace YOUR_USERNAME):
echo.
echo    git remote add origin https://github.com/YOUR_USERNAME/student-attendance-system.git
echo    git branch -M main
echo    git push -u origin main
echo.
echo ========================================
echo.
echo Press any key to open GitHub in your browser...
pause >nul

start https://github.com/new

echo.
echo Setup complete! Follow the instructions above to connect to GitHub.
echo.
pause