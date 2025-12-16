# Auto Chat ID Telegram Fetcher Service Starter
# Run this PowerShell script to start the background Telegram API fetcher
# Place it in your Startup folder to auto-start after logon

# Configuration
$ServiceDir = Split-Path -Parent $MyInvocation.MyCommand.Path
$PHPExe = "C:\xampp\php\php.exe"
$ServiceScript = Join-Path $ServiceDir "auto_chatid_service.php"
$LogFile = Join-Path $ServiceDir "logs\auto_chatid_service.log"

# Verify paths exist
if (-not (Test-Path $PHPExe)) {
    Write-Error "PHP executable not found at: $PHPExe"
    exit 1
}

if (-not (Test-Path $ServiceScript)) {
    Write-Error "Service script not found at: $ServiceScript"
    exit 1
}

# Ensure logs directory exists
$LogDir = Split-Path -Parent $LogFile
if (-not (Test-Path $LogDir)) {
    New-Item -ItemType Directory -Path $LogDir -Force | Out-Null
}

Write-Host "========================================" -ForegroundColor Cyan
Write-Host "Auto Chat ID Telegram Fetcher Service" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""
Write-Host "PHP Path: $PHPExe" -ForegroundColor Green
Write-Host "Service Script: $ServiceScript" -ForegroundColor Green
Write-Host "Log File: $LogFile" -ForegroundColor Green
Write-Host ""
Write-Host "Starting Telegram API fetcher..." -ForegroundColor Yellow
Write-Host ""

try {
    # Start the service in background without displaying window
    $ProcessInfo = New-Object System.Diagnostics.ProcessStartInfo
    $ProcessInfo.FileName = $PHPExe
    $ProcessInfo.Arguments = "-d display_errors=0 `"$ServiceScript`""
    $ProcessInfo.UseShellExecute = $false
    $ProcessInfo.CreateNoWindow = $true
    $ProcessInfo.RedirectStandardOutput = $true
    $ProcessInfo.RedirectStandardError = $true
    
    $Process = [System.Diagnostics.Process]::Start($ProcessInfo)
    
    Write-Host "✓ Service started successfully" -ForegroundColor Green
    Write-Host "  Process ID: $($Process.Id)" -ForegroundColor Green
    Write-Host ""
    Write-Host "Monitor logs with:" -ForegroundColor Yellow
    Write-Host "  Get-Content '$LogFile' -Tail 20 -Wait" -ForegroundColor Cyan
    Write-Host ""
}
catch {
    Write-Error "Failed to start service: $_"
    exit 1
}

# Keep this script running (optional - remove to let it exit)
# while ($true) { Start-Sleep -Seconds 60 }
