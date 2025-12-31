<?php
/**
 * Email Notification Module
 * Sends attendance alerts via Gmail SMTP
 */

require_once __DIR__ . '/../config/email_config.php';

class EmailNotifier
{
    private $smtpHost;
    private $smtpPort;
    private $smtpUsername;
    private $smtpPassword;
    private $fromEmail;
    private $fromName;

    public function __construct()
    {
        $this->smtpHost = SMTP_HOST;
        $this->smtpPort = SMTP_PORT;
        $this->smtpUsername = SMTP_USERNAME;
        $this->smtpPassword = SMTP_PASSWORD;
        $this->fromEmail = SMTP_FROM_EMAIL;
        $this->fromName = SMTP_FROM_NAME;
    }

    /**
     * Send email via SMTP
     * @param string $to_email - Recipient email address
     * @param string $subject - Email subject
     * @param string $message - HTML message body
     * @return array - Response with success status
     */
    public function send($to_email, $subject, $message)
    {
        if (!$to_email || !$message) {
            return [
                'success' => false,
                'error' => 'Missing email or message'
            ];
        }

        error_log("[EMAIL_SEND] To: $to_email | Subject: $subject");

        // Build email headers
        $headers = [
            'MIME-Version: 1.0',
            'Content-type: text/html; charset=UTF-8',
            'From: ' . $this->fromName . ' <' . $this->fromEmail . '>',
            'Reply-To: ' . $this->fromEmail,
            'X-Mailer: PHP/' . phpversion()
        ];

        // Use native SMTP connection
        return $this->sendWithNativeSMTP($to_email, $subject, $message, $headers);
    }

    /**
     * Send using native PHP mail with SMTP
     */
    private function sendWithNativeSMTP($to_email, $subject, $message, $headers)
    {
        // Try using PHP's mail() function with proper headers
        // Note: This requires proper PHP mail configuration

        $headerString = implode("\r\n", $headers);

        // For Gmail SMTP, we need to use fsockopen for direct SMTP
        try {
            $result = $this->smtpMail($to_email, $subject, $message);
            return $result;
        } catch (Exception $e) {
            error_log("[EMAIL_ERROR] " . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Send email using direct SMTP connection
     */
    private function smtpMail($to_email, $subject, $htmlBody)
    {
        $smtp = @fsockopen('tls://' . $this->smtpHost, $this->smtpPort, $errno, $errstr, 30);

        if (!$smtp) {
            // Try without TLS wrapper (will use STARTTLS)
            $smtp = @fsockopen($this->smtpHost, $this->smtpPort, $errno, $errstr, 30);

            if (!$smtp) {
                error_log("[SMTP_ERROR] Connection failed: $errstr ($errno)");
                return [
                    'success' => false,
                    'error' => "SMTP connection failed: $errstr"
                ];
            }
        }

        // Set stream timeout
        stream_set_timeout($smtp, 30);

        // Read greeting
        $this->getResponse($smtp);

        // Send EHLO
        fwrite($smtp, "EHLO localhost\r\n");
        $this->getResponse($smtp);

        // Start TLS if not already using TLS
        fwrite($smtp, "STARTTLS\r\n");
        $response = $this->getResponse($smtp);

        if (strpos($response, '220') === 0) {
            // Enable TLS encryption
            stream_socket_enable_crypto($smtp, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);

            // Send EHLO again after TLS
            fwrite($smtp, "EHLO localhost\r\n");
            $this->getResponse($smtp);
        }

        // Authenticate
        fwrite($smtp, "AUTH LOGIN\r\n");
        $this->getResponse($smtp);

        fwrite($smtp, base64_encode($this->smtpUsername) . "\r\n");
        $this->getResponse($smtp);

        fwrite($smtp, base64_encode($this->smtpPassword) . "\r\n");
        $authResponse = $this->getResponse($smtp);

        if (strpos($authResponse, '235') !== 0) {
            fclose($smtp);
            error_log("[SMTP_AUTH_ERROR] Authentication failed: $authResponse");
            return [
                'success' => false,
                'error' => 'SMTP authentication failed. Please check your App Password.'
            ];
        }

        // Set sender
        fwrite($smtp, "MAIL FROM:<{$this->fromEmail}>\r\n");
        $this->getResponse($smtp);

        // Set recipient
        fwrite($smtp, "RCPT TO:<$to_email>\r\n");
        $rcptResponse = $this->getResponse($smtp);

        if (strpos($rcptResponse, '250') !== 0) {
            fclose($smtp);
            return [
                'success' => false,
                'error' => "Recipient rejected: $rcptResponse"
            ];
        }

        // Send DATA command
        fwrite($smtp, "DATA\r\n");
        $this->getResponse($smtp);

        // Build email content
        $email = "From: {$this->fromName} <{$this->fromEmail}>\r\n";
        $email .= "To: $to_email\r\n";
        $email .= "Subject: $subject\r\n";
        $email .= "MIME-Version: 1.0\r\n";
        $email .= "Content-Type: text/html; charset=UTF-8\r\n";
        $email .= "\r\n";
        $email .= $htmlBody;
        $email .= "\r\n.\r\n";

        fwrite($smtp, $email);
        $dataResponse = $this->getResponse($smtp);

        // Quit
        fwrite($smtp, "QUIT\r\n");
        fclose($smtp);

        if (strpos($dataResponse, '250') === 0) {
            error_log("[EMAIL_SUCCESS] Email sent to: $to_email");
            return [
                'success' => true,
                'message' => 'Email sent successfully'
            ];
        } else {
            return [
                'success' => false,
                'error' => "Send failed: $dataResponse"
            ];
        }
    }

    /**
     * Get SMTP response
     */
    private function getResponse($smtp)
    {
        $response = '';
        while ($line = fgets($smtp, 515)) {
            $response .= $line;
            if (substr($line, 3, 1) == ' ')
                break;
        }
        return $response;
    }

    /**
     * Format attendance notification email (HTML)
     */
    public static function formatAttendanceMessage($guardianName, $studentName, $status, $date, $time, $gradeLevel, $section)
    {
        $statusColor = ($status === 'TIME IN') ? '#28a745' : '#dc3545';
        $statusIcon = ($status === 'TIME IN') ? '✅' : '🚪';

        return "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <style>
                body { font-family: Arial, sans-serif; background-color: #f4f4f4; margin: 0; padding: 20px; }
                .container { max-width: 600px; margin: 0 auto; background: #fff; border-radius: 10px; overflow: hidden; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
                .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px; text-align: center; }
                .header h1 { margin: 0; font-size: 24px; }
                .header p { margin: 10px 0 0 0; opacity: 0.9; }
                .content { padding: 30px; }
                .greeting { font-size: 18px; color: #333; margin-bottom: 20px; }
                .status-badge { display: inline-block; background: {$statusColor}; color: white; padding: 10px 20px; border-radius: 25px; font-weight: bold; font-size: 16px; }
                .details { background: #f8f9fa; border-radius: 8px; padding: 20px; margin: 20px 0; }
                .details-row { display: flex; justify-content: space-between; padding: 10px 0; border-bottom: 1px solid #eee; }
                .details-row:last-child { border-bottom: none; }
                .details-label { color: #666; font-weight: 500; }
                .details-value { color: #333; font-weight: 600; }
                .footer { background: #f8f9fa; padding: 20px; text-align: center; color: #666; font-size: 12px; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>📚 Student Attendance Notification</h1>
                    <p>Alexis Santos National High School</p>
                </div>
                <div class='content'>
                    <p class='greeting'>Good day, <strong>{$guardianName}</strong>!</p>
                    
                    <p>This is to inform you that your child has recorded attendance:</p>
                    
                    <p style='text-align: center; margin: 25px 0;'>
                        <span class='status-badge'>{$statusIcon} {$status}</span>
                    </p>
                    
                    <div class='details'>
                        <div class='details-row'>
                            <span class='details-label'>Student Name</span>
                            <span class='details-value'>{$studentName}</span>
                        </div>
                        <div class='details-row'>
                            <span class='details-label'>Date</span>
                            <span class='details-value'>{$date}</span>
                        </div>
                        <div class='details-row'>
                            <span class='details-label'>Time</span>
                            <span class='details-value'>{$time}</span>
                        </div>
                        <div class='details-row'>
                            <span class='details-label'>Grade Level</span>
                            <span class='details-value'>{$gradeLevel}</span>
                        </div>
                        <div class='details-row'>
                            <span class='details-label'>Section</span>
                            <span class='details-value'>{$section}</span>
                        </div>
                    </div>
                    
                    <p style='color: #666; font-size: 14px;'>If you have any questions, please contact the school administration.</p>
                </div>
                <div class='footer'>
                    <p>This is an automated message from the Attendance Monitoring System.</p>
                    <p>Please do not reply to this email.</p>
                </div>
            </div>
        </body>
        </html>
        ";
    }
}
?>