# 🚀 Teammate Setup Guide

## Quick Setup (5 minutes)

### Step 1: Clone the Repository
```bash
git clone https://github.com/SuperShyyy-git/Student_Attendance_Monitoring.git
cd Student_Attendance_Monitoring
```

### Step 2: Copy to XAMPP
Copy the project folder to `C:\xampp\htdocs\attendance\`

Or use this command in PowerShell:
```powershell
Copy-Item -Path ".\*" -Destination "C:\xampp\htdocs\attendance\" -Recurse -Force
```

### Step 3: Create Database Config
1. Create the folder: `config/`
2. Copy `config/db_connect.sample.php` to `config/db_connect.php`
3. Edit if needed (default settings work for standard XAMPP)

### Step 4: Create Database
1. Open XAMPP Control Panel
2. Start **Apache** and **MySQL**
3. Open phpMyAdmin: http://localhost/phpmyadmin
4. Create database: `attendance_system`

### Step 5: Run Migration (IMPORTANT!)
In phpMyAdmin:
1. Select `attendance_system` database
2. Click **SQL** tab
3. Open `database/migration_v2.sql` file
4. Copy all contents and paste into SQL box
5. Click **Go**

**Or via command line:**
```bash
mysql -u root attendance_system < database/migration_v2.sql
```

### Step 6: Create Admin User
Run this SQL in phpMyAdmin:
```sql
INSERT INTO users (username, password, role) VALUES 
('admin', '$2y$10$ij2FcLQM.WCtuCrTW.QKq.3Qo478QttAwCVhwTjFLsQYv6N18jNDG', 'admin');
```
Login: **admin** / **admin123**

### Step 7: Access the System
Open browser: http://localhost/attendance/HTML/login.php

---

## ⚠️ Common Issues

### "Undefined array key 'grade_level'" or "Undefined array key 'address'"
**Cause:** Database not migrated
**Fix:** Run `database/migration_v2.sql` in phpMyAdmin

### "Database connection failed"
**Cause:** Missing config file
**Fix:** Copy `config/db_connect.sample.php` to `config/db_connect.php`

### "Table doesn't exist"
**Cause:** Database or tables not created
**Fix:** Create database and run migration SQL

---

## 📁 Project Structure
```
attendance/
├── config/
│   └── db_connect.php      ← Create this from sample
├── database/
│   └── migration_v2.sql    ← Run this to set up tables
├── HTML/                   ← PHP pages
├── CSS/                    ← Stylesheets
├── JS/                     ← JavaScript
└── README.md
```

## 👥 Team Roles
- **Person 1**: Backend/Database ✅ (Complete)
- **Person 2**: Frontend UI
- **Person 3**: AI/Attendance
- **Person 4**: Notifications/Hosting
