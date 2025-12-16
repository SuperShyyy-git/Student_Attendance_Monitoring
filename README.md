# Student Attendance Monitoring System

A comprehensive RFID and face recognition-based attendance monitoring system built with PHP, MySQL, and modern web technologies.

## 🚀 Features

### Core Functionality
- **RFID Attendance Tracking**: Automated attendance recording using RFID cards
- **Face Recognition Verification**: AI-powered face verification for enhanced security
- **Real-time Dashboard**: Web-based interface for monitoring and management
- **Telegram Bot Integration**: Automated notifications via Telegram
- **Student Management**: Complete CRUD operations for student records
- **Section & Year Level Management**: Organize students by academic groups

### Technical Features
- **Multi-modal Authentication**: RFID + Face recognition combination
- **Real-time Notifications**: Instant alerts for attendance events
- **Responsive Design**: Mobile-friendly web interface
- **Background Processing**: Automated Telegram message fetching
- **Comprehensive Logging**: Detailed system logs for troubleshooting

## 🛠️ Technology Stack

- **Backend**: PHP 8.x with MySQL
- **Frontend**: HTML5, CSS3, JavaScript (Vanilla)
- **Face Recognition**: Python with face_recognition library
- **Database**: MySQL 8.x
- **Web Server**: Apache (XAMPP)
- **External APIs**: Telegram Bot API
- **Icons**: Lucide Icons

## 📋 Prerequisites

- **XAMPP** (Apache, MySQL, PHP)
- **Python 3.8+** with pip
- **Git** (for version control)
- **Windows 10/11** (developed on Windows)

### Required Python Packages
```
face_recognition==1.3.0
opencv-python==4.8.0.76
numpy==1.24.3
Pillow==10.0.1
```

## 🚀 Installation

### 1. Clone the Repository
```bash
git clone https://github.com/yourusername/student-attendance-system.git
cd student-attendance-system
```

### 2. Database Setup
1. Start XAMPP Control Panel
2. Start Apache and MySQL services
3. Open phpMyAdmin (http://localhost/phpmyadmin)
4. Create database: `attendance_system`
5. Import the SQL schema (if provided)

### 3. Configuration
1. Update database credentials in `config/db_connect.php`
2. Configure Telegram bot token in relevant files
3. Set up Python environment for face recognition

### 4. Install Dependencies
```bash
# Install Python dependencies
pip install -r requirements.txt

# Install PHP dependencies (if using Composer)
composer install
```

### 5. Start the Application
1. Place project in `C:\xampp\htdocs\`
2. Access via: `http://localhost/Student_Attendance_Monitoring/`
3. Start the Telegram service: `start_auto_chatid_service.bat`

## 📁 Project Structure

```
Student_Attendance_Monitoring/
├── api/                    # API endpoints
├── config/                 # Database configuration
├── CSS/                    # Stylesheets
├── HTML/                   # PHP pages (main application)
├── JS/                     # JavaScript files
├── logs/                   # System logs
├── python/                 # Face recognition scripts
├── resources/              # Static assets
├── student_photos/         # Student photo storage
├── uploads/                # File uploads
├── backups/                # Database backups
└── docs/                   # Documentation
```

## 🔧 Configuration

### Database Configuration
Edit `config/db_connect.php`:
```php
$host = "localhost";
$user = "root";
$pass = "";
$dbname = "attendance_system";
```

### Telegram Bot Setup
1. Create bot via [@BotFather](https://t.me/botfather)
2. Get bot token
3. Update token in service files
4. Set up webhook or polling mechanism

### Face Recognition Setup
1. Install Python dependencies
2. Train face recognition model with student photos
3. Configure camera settings

## 📖 Usage

### For Administrators
1. **Login**: Access dashboard with admin credentials
2. **Student Management**: Add/edit/delete student records
3. **Section Management**: Organize students by year level and section
4. **Monitor Attendance**: View real-time attendance data
5. **Generate Reports**: Export attendance reports

### For Students
1. **RFID Check-in**: Tap RFID card at attendance terminal
2. **Face Verification**: Camera captures face for verification
3. **Receive Notifications**: Get Telegram notifications for attendance

## 🔄 API Endpoints

### Attendance Processing
- `POST /rfid_attendance_process.php` - Process RFID attendance
- `POST /telegram_get_chatid.php` - Handle Telegram messages

### Data Management
- `GET /student_table.php` - Student records
- `GET /sec_yr_level.php` - Section management
- `GET /student-attendance.php` - Attendance records

## 🤖 Telegram Integration

### Features
- **Auto Chat ID Linking**: Automatically link student phone numbers to Telegram chat IDs
- **Real-time Notifications**: Send attendance confirmations via Telegram
- **Background Service**: Continuous polling for new messages

### Setup
1. Create Telegram bot
2. Configure bot token
3. Start background service
4. Students send messages to link their accounts

## 📊 Database Schema

### Core Tables
- `students` - Student information and credentials
- `student_attendance` - Attendance records
- `sections` - Year level and section data
- `telegram_inbox` - Incoming Telegram messages

## 🔒 Security Features

- **Session Management**: Secure PHP sessions
- **Input Validation**: Sanitized user inputs
- **SQL Injection Protection**: Prepared statements
- **File Upload Security**: Restricted file types and sizes
- **Access Control**: Role-based permissions

## 📝 Development

### Code Style
- **PHP**: PSR-12 standards
- **JavaScript**: Standard ES6+ practices
- **CSS**: BEM methodology
- **Python**: PEP 8 standards

### Testing
```bash
# Run PHP tests
vendor/bin/phpunit

# Run Python face recognition tests
python -m pytest python/tests/
```

### Deployment
1. Set up production server (Apache/Nginx + MySQL)
2. Configure environment variables
3. Set up SSL certificates
4. Configure automated backups
5. Set up monitoring and logging

## 🐛 Troubleshooting

### Common Issues

**Database Connection Failed**
- Check XAMPP services are running
- Verify database credentials
- Ensure database exists

**Face Recognition Not Working**
- Check Python installation
- Verify camera permissions
- Test face_recognition library

**Telegram Service Not Starting**
- Check PHP path in batch file
- Verify bot token is correct
- Check network connectivity

**RFID Reader Not Detected**
- Check USB connections
- Install RFID reader drivers
- Verify COM port settings

## 📄 License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## 👥 Contributing

1. Fork the repository
2. Create feature branch (`git checkout -b feature/AmazingFeature`)
3. Commit changes (`git commit -m 'Add some AmazingFeature'`)
4. Push to branch (`git push origin feature/AmazingFeature`)
5. Open Pull Request

## 📞 Support

For support and questions:
- Create an issue on GitHub
- Check the documentation in `/docs/`
- Review system logs in `/logs/`

## 🙏 Acknowledgments

- Face recognition powered by [face_recognition](https://github.com/ageitgey/face_recognition)
- Icons provided by [Lucide](https://lucide.dev/)
- Telegram Bot API integration

---

**Developed with ❤️ for educational institutions**