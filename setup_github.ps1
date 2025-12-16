# GitHub Setup Script for Student Attendance Monitoring System
# Run this PowerShell script to connect your project to GitHub

Write-Host "🚀 Setting up GitHub connection for Student Attendance Monitoring System" -ForegroundColor Cyan
Write-Host "=" * 70 -ForegroundColor Cyan

# Configuration
$ProjectDir = "C:\xampp\htdocs\Student_Attendance_Monitoring"
$GitHubUsername = Read-Host "Enter your GitHub username"
$RepoName = Read-Host "Enter repository name (e.g., student-attendance-system)"

# Check if Git is installed
try {
    $gitVersion = git --version
    Write-Host "✓ Git is installed: $gitVersion" -ForegroundColor Green
} catch {
    Write-Host "❌ Git is not installed. Please install Git first from https://git-scm.com/" -ForegroundColor Red
    exit 1
}

# Navigate to project directory
Set-Location $ProjectDir
Write-Host "📁 Working in: $ProjectDir" -ForegroundColor Yellow

# Initialize Git repository
Write-Host "🔧 Initializing Git repository..." -ForegroundColor Yellow
git init

# Create .gitignore file
Write-Host "📝 Creating .gitignore file..." -ForegroundColor Yellow
$gitignoreContent = @"
# Database files
*.db
*.sqlite
*.sqlite3

# Logs
logs/*.log
logs/telegram_*.txt
*.log

# PHP sessions
session/

# Temporary files
*.tmp
*.temp
cache/
temp/

# IDE files
.vscode/
.idea/
*.swp
*.swo

# OS files
.DS_Store
Thumbs.db

# Node modules (if any)
node_modules/

# Environment files
.env
.env.local
.env.*.local

# Backup files
*.bak
backups/

# XAMPP specific
/xampp/
/apache/
/mysql/data/
"@

Set-Content -Path ".gitignore" -Value $gitignoreContent

# Add all files
Write-Host "📦 Adding files to Git..." -ForegroundColor Yellow
git add .

# Initial commit
Write-Host "💾 Creating initial commit..." -ForegroundColor Yellow
git commit -m "Initial commit: Student Attendance Monitoring System

Features:
- RFID-based attendance tracking
- Face recognition verification
- Telegram bot integration for notifications
- Web-based dashboard
- Student management system
- Section and year level management"

# Set up remote repository
$RepoUrl = "https://github.com/$GitHubUsername/$RepoName.git"
Write-Host "🔗 Setting up remote repository..." -ForegroundColor Yellow
git branch -M main
git remote add origin $RepoUrl

# Push to GitHub
Write-Host "⬆️ Pushing to GitHub..." -ForegroundColor Yellow
try {
    git push -u origin main
    Write-Host "✅ Successfully pushed to GitHub!" -ForegroundColor Green
    Write-Host "🌐 Repository URL: https://github.com/$GitHubUsername/$RepoName" -ForegroundColor Cyan
} catch {
    Write-Host "❌ Failed to push to GitHub. Please check:" -ForegroundColor Red
    Write-Host "   1. Repository exists on GitHub" -ForegroundColor Yellow
    Write-Host "   2. You have push access to the repository" -ForegroundColor Yellow
    Write-Host "   3. Your GitHub credentials are configured" -ForegroundColor Yellow
    Write-Host ""
    Write-Host "Manual push command:" -ForegroundColor Cyan
    Write-Host "   git push -u origin main" -ForegroundColor White
}

Write-Host ""
Write-Host "📋 Next steps:" -ForegroundColor Cyan
Write-Host "1. Go to https://github.com/$GitHubUsername/$RepoName" -ForegroundColor White
Write-Host "2. Enable GitHub Pages if you want to host documentation" -ForegroundColor White
Write-Host "3. Set up GitHub Actions for automated testing (optional)" -ForegroundColor White
Write-Host "4. Add collaborators if needed" -ForegroundColor White

Write-Host ""
Write-Host "🎉 GitHub setup complete!" -ForegroundColor Green