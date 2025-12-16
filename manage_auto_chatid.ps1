# Auto Chat ID Fetcher Service - PowerShell Launcher
# This script runs the background service and keeps it running

param(
    [string]$Action = "start"
)

$serviceScript = Join-Path $PSScriptRoot "auto_chatid_service.php"
$logFile = Join-Path $PSScriptRoot "logs\auto_chatid_service.log"
$pidFile = Join-Path $PSScriptRoot "logs\auto_chatid_service.pid"

function Start-Service {
    # Check if already running
    if (Test-Path $pidFile) {
        $oldPid = Get-Content $pidFile
        if (Get-Process -Id $oldPid -ErrorAction SilentlyContinue) {
            Write-Host "✅ Service already running (PID: $oldPid)"
            return
        }
    }
    
    # Ensure logs directory exists
    $logsDir = Split-Path $logFile
    if (!(Test-Path $logsDir)) {
        New-Item -ItemType Directory -Path $logsDir | Out-Null
    }
    
    # Start the service in background
    Write-Host "🚀 Starting Auto Chat ID Fetcher Service..."
    Write-Host "   - Fetching Chat IDs every 3 seconds"
    Write-Host "   - Log file: $logFile"
    Write-Host ""
    
    $process = Start-Process php -ArgumentList "-d display_errors=0 `"$serviceScript`"" `
        -WindowStyle Hidden `
        -PassThru `
        -ErrorAction Stop
    
    # Save PID
    $process.Id | Out-File $pidFile -Force
    
    Write-Host "✅ Service started successfully (PID: $($process.Id))"
    Write-Host ""
    Write-Host "Service is running in the background."
    Write-Host "To check status: Get-Content '$logFile' -Tail 10"
    Write-Host "To stop service: powershell -File '$PSCommandPath' -Action stop"
}

function Stop-Service {
    if (!(Test-Path $pidFile)) {
        Write-Host "❌ Service is not running"
        return
    }
    
    $pid = Get-Content $pidFile
    
    $process = Get-Process -Id $pid -ErrorAction SilentlyContinue
    if ($process) {
        Write-Host "⏹️  Stopping service (PID: $pid)..."
        $process | Stop-Process -Force
        Start-Sleep -Seconds 1
        Remove-Item $pidFile -Force -ErrorAction SilentlyContinue
        Write-Host "✅ Service stopped"
    } else {
        Write-Host "❌ Process not found"
        Remove-Item $pidFile -Force -ErrorAction SilentlyContinue
    }
}

function Get-Status {
    if (!(Test-Path $pidFile)) {
        Write-Host "❌ Service is not running"
        return
    }
    
    $pid = Get-Content $pidFile
    $process = Get-Process -Id $pid -ErrorAction SilentlyContinue
    
    if ($process) {
        Write-Host "✅ Service is running (PID: $pid)"
        Write-Host "   Memory: $([Math]::Round($process.WorkingSet64/1MB, 2)) MB"
        Write-Host ""
        Write-Host "Recent logs:"
        if (Test-Path $logFile) {
            Get-Content $logFile -Tail 5
        }
    } else {
        Write-Host "❌ Service is not running"
        Remove-Item $pidFile -Force -ErrorAction SilentlyContinue
    }
}

# Execute action
switch ($Action.ToLower()) {
    "start" { Start-Service }
    "stop" { Stop-Service }
    "status" { Get-Status }
    "restart" { 
        Stop-Service
        Start-Sleep -Seconds 1
        Start-Service
    }
    default {
        Write-Host "Auto Chat ID Fetcher Service Control"
        Write-Host ""
        Write-Host "Usage: powershell -File manage_auto_chatid.ps1 -Action <action>"
        Write-Host ""
        Write-Host "Actions:"
        Write-Host "  start   - Start the service"
        Write-Host "  stop    - Stop the service"
        Write-Host "  status  - Check service status"
        Write-Host "  restart - Restart the service"
        Write-Host ""
        Write-Host "Examples:"
        Write-Host "  powershell -File manage_auto_chatid.ps1 -Action start"
        Write-Host "  powershell -File manage_auto_chatid.ps1 -Action status"
        Write-Host "  powershell -File manage_auto_chatid.ps1 -Action stop"
    }
}
