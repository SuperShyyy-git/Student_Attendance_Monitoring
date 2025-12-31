<?php
/**
 * Email Configuration for Gmail SMTP
 * 
 * IMPORTANT: You need to generate an App Password for your Gmail account:
 * 1. Go to https://myaccount.google.com/apppasswords
 * 2. Sign in to your Google account
 * 3. Select "Mail" as the app and "Windows Computer" as the device
 * 4. Click "Generate" and copy the 16-character password
 * 5. Replace 'YOUR_APP_PASSWORD_HERE' below with that password
 */

// Gmail SMTP Configuration
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_SECURE', 'tls');

// Your Gmail credentials
define('SMTP_USERNAME', 'alexissantosnationalhighschool@gmail.com');
define('SMTP_PASSWORD', 'qqzensglgzqkjoie'); // Gmail App Password

// Sender information
define('SMTP_FROM_EMAIL', 'alexissantosnationalhighschool@gmail.com');
define('SMTP_FROM_NAME', 'Alexis Santos National High School - Attendance System');

// Email settings
define('EMAIL_CHARSET', 'UTF-8');
?>