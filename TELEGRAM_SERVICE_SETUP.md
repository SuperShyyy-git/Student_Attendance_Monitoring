# Telegram Auto-Fetch Service Setup Guide

## Problem
The `auto_chatid_service.php` background process stopped running, so Telegram messages are no longer being automatically fetched and linked to students.

## Root Cause
- The background service process is not running continuously
- When run manually under VPN, it works fine
- The service needs to be started in a context that has the VPN route available

## Solutions

### Option 1: Run Service Manually (Quick Test)
```powershell
# Open PowerShell in your VPN-enabled session and run:
cd "C:\xampp\htdocs\Student_Attendance_Monitoring"
& "C:\xampp\php\php.exe" -d display_errors=0 auto_chatid_service.php
```

### Option 2: Use Windows Scheduled Task (Recommended)
1. Open **Task Scheduler** (Windows+R → `taskschd.msc`)
2. Click **Create Task** (right panel)
3. Configure:
   - **Name**: `TelegramAutoFetcher`
   - **Run with highest privileges**: ✓ Checked
   - **Trigger**: At logon (so it starts after you login/VPN connects)
   - **Action**: 
     - Program: `C:\xampp\php\php.exe`
     - Arguments: `-d display_errors=0 "C:\xampp\htdocs\Student_Attendance_Monitoring\auto_chatid_service.php"`
     - Start in: `C:\xampp\htdocs\Student_Attendance_Monitoring\`
4. Click OK

**Benefits**: Runs automatically after Windows startup, under your user context (has VPN route)

### Option 3: Run Batch File on Startup
1. Save the batch file to Windows Startup folder:
   ```
   C:\Users\[YourUsername]\AppData\Roaming\Microsoft\Windows\Start Menu\Programs\Startup\StartTelegramService.bat
   ```
2. Copy content from `start_auto_chatid_service.bat` to this file
3. Service will auto-start after logon

### Option 4: Use PowerShell Script with Task Scheduler
1. Right-click `Start-TelegramService.ps1`
2. **Run with PowerShell**
3. Or schedule it via Task Scheduler with:
   - **Program**: `powershell.exe`
   - **Arguments**: `-NoProfile -ExecutionPolicy Bypass -File "C:\xampp\htdocs\Student_Attendance_Monitoring\Start-TelegramService.ps1"`

## Verify Service is Running

Check the log file:
```powershell
Get-Content "C:\xampp\htdocs\Student_Attendance_Monitoring\logs\auto_chatid_service.log" -Tail 50 -Wait
```

Look for entries like:
- `[AUTO_CHATID] Fetched X updates`
- `[AUTO_CHATID_REGISTERED]` (successful registrations)

## Troubleshooting

**Issue**: "Could not resolve host: api.telegram.org"
- **Fix**: Make sure VPN is connected before starting the service
- The service inherits the network context of the user session

**Issue**: Service starts but immediately exits
- **Check**: Is the database connection working? Look at logs
- **Verify**: PHP path is correct: `C:\xampp\php\php.exe`

**Issue**: Nothing is being logged
- **Check**: Does the `logs/` folder exist and is it writable?
- **Create folder if needed**: `mkdir logs`

## Monitor in Real-Time
```powershell
# PowerShell command to tail logs (Ctrl+C to stop)
Get-Content "logs\auto_chatid_service.log" -Tail 20 -Wait
```

## Test the Service Manually

```powershell
# In VPN-enabled PowerShell session:
cd C:\xampp\htdocs\Student_Attendance_Monitoring
& 'C:\xampp\php\php.exe' -d display_errors=0 auto_chatid_service.php

# Wait 5-10 seconds, then send a test message to your Telegram bot
# Check logs for success entries
```

---

**Recommended**: Use **Option 2 (Task Scheduler)** for reliability and automatic startup.
