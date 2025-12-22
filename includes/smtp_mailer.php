<?php
/**
 * Simple SMTP Mailer for Gmail
 */
require_once 'config/database.php';

function getMailConfigFromDB() {
    try {
        $database = new Database();
        $conn = $database->getConnection();
        
        $stmt = $conn->prepare("SELECT smtp_host, smtp_port, smtp_username, smtp_password, from_email, from_name FROM mail_settings ORDER BY id DESC LIMIT 1");
        $stmt->execute();
        $config = $stmt->fetch();
        
        if (!$config) {
            // Return default configuration if none exists
            return [
                'smtp_host' => 'ssl://smtp.gmail.com',
                'smtp_port' => 465,
                'smtp_username' => '',
                'smtp_password' => '',
                'from_email' => '',
                'from_name' => 'Admin Team'
            ];
        }
        
        return $config;
    } catch (Exception $e) {
        // Return default configuration if there's an error
        return [
            'smtp_host' => 'ssl://smtp.gmail.com',
            'smtp_port' => 465,
            'smtp_username' => '',
            'smtp_password' => '',
            'from_email' => '',
            'from_name' => 'Admin Team'
        ];
    }
}

// Add alternative configuration function
function getAlternativeMailConfig() {
    $config = getMailConfigFromDB();
    
    // Try TLS configuration as alternative
    if ($config['smtp_host'] === 'ssl://smtp.gmail.com' && $config['smtp_port'] == 465) {
        $config['smtp_host'] = 'tls://smtp.gmail.com';
        $config['smtp_port'] = 587;
    }
    
    return $config;
}

function sendGmailSMTP($to, $subject, $message, $config = null) {
    // Use provided config or get from database
    if ($config === null) {
        $config = getMailConfigFromDB();
    }
    
    $smtp_server = $config['smtp_host'];
    $port = $config['smtp_port'];
    $from_email = $config['from_email'];
    $from_password = $config['smtp_password'];
    $timeout = 30;
    
    // Validate required fields
    if (empty($smtp_server) || empty($port) || empty($from_email) || empty($from_password)) {
        return "Missing required SMTP configuration fields";
    }
    
    $socket = fsockopen($smtp_server, $port, $errno, $errstr, $timeout);
    
    if (!$socket) {
        // Try alternative configuration if first attempt fails
        if ($config['smtp_host'] === 'ssl://smtp.gmail.com') {
            $altConfig = getAlternativeMailConfig();
            if ($altConfig['smtp_host'] !== $config['smtp_host'] || $altConfig['smtp_port'] !== $config['smtp_port']) {
                error_log("Retrying with alternative SMTP configuration...");
                return sendGmailSMTP($to, $subject, $message, $altConfig);
            }
        }
        return "Failed to connect: $errstr ($errno)";
    }
    
    // Read server greeting
    $response = fgets($socket, 4096);
    if (substr($response, 0, 3) != '220') {
        fclose($socket);
        return "Connection error: $response";
    }
    
    // Send EHLO
    fputs($socket, "EHLO localhost\r\n");
    $response = fgets($socket, 4096);
    if (substr($response, 0, 3) != '250') {
        fclose($socket);
        return "EHLO error: $response";
    }
    
    // For TLS, start TLS negotiation
    if (strpos($smtp_server, 'tls://') === 0) {
        fputs($socket, "STARTTLS\r\n");
        $response = fgets($socket, 4096);
        if (substr($response, 0, 3) != '220') {
            fclose($socket);
            return "STARTTLS error: $response";
        }
        // Enable TLS encryption
        stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
        
        // Send EHLO again after STARTTLS
        fputs($socket, "EHLO localhost\r\n");
        $response = fgets($socket, 4096);
        if (substr($response, 0, 3) != '250') {
            fclose($socket);
            return "EHLO after STARTTLS error: $response";
        }
    }
    
    // Authenticate
    fputs($socket, "AUTH LOGIN\r\n");
    $response = fgets($socket, 4096);
    if (substr($response, 0, 3) != '334') {
        fclose($socket);
        return "AUTH LOGIN error: $response";
    }
    
    // Send username (base64 encoded)
    fputs($socket, base64_encode($from_email) . "\r\n");
    $response = fgets($socket, 4096);
    if (substr($response, 0, 3) != '334') {
        fclose($socket);
        return "Username error: $response";
    }
    
    // Send password (base64 encoded)
    fputs($socket, base64_encode($from_password) . "\r\n");
    $response = fgets($socket, 4096);
    if (substr($response, 0, 3) != '235') {
        fclose($socket);
        return "Password error: $response";
    }
    
    // Set sender
    fputs($socket, "MAIL FROM: <$from_email>\r\n");
    $response = fgets($socket, 4096);
    if (substr($response, 0, 3) != '250') {
        fclose($socket);
        return "MAIL FROM error: $response";
    }
    
    // Set recipient
    fputs($socket, "RCPT TO: <$to>\r\n");
    $response = fgets($socket, 4096);
    if (substr($response, 0, 3) != '250') {
        fclose($socket);
        return "RCPT TO error: $response";
    }
    
    // Start data
    fputs($socket, "DATA\r\n");
    $response = fgets($socket, 4096);
    if (substr($response, 0, 3) != '354') {
        fclose($socket);
        return "DATA error: $response";
    }
    
    // Send message
    $headers = "From: $from_email\r\n";
    $headers .= "To: $to\r\n";
    $headers .= "Subject: $subject\r\n";
    $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
    $headers .= "X-Mailer: PHP/" . phpversion() . "\r\n";
    $headers .= "\r\n";
    
    $full_message = $headers . $message . "\r\n.\r\n";
    fputs($socket, $full_message);
    
    $response = fgets($socket, 4096);
    if (substr($response, 0, 3) != '250') {
        fclose($socket);
        return "Message sending error: $response";
    }
    
    // Quit
    fputs($socket, "QUIT\r\n");
    fclose($socket);
    
    return true;
}
?>