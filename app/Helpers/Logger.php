// app/Helpers/Logger.php
class Logger {
    public static function log($action, $table, $details = '') {
        $logFile = '../logs/activity.log';
        $timestamp = date('Y-m-d H:i:s');
        $user = $_SESSION['username'] ?? 'guest';
        $ip = $_SERVER['REMOTE_ADDR'];
        
        $message = "[$timestamp] User: $user | IP: $ip | Action: $action | Table: $table | Details: $details\n";
        
        file_put_contents($logFile, $message, FILE_APPEND);
    }
}

// Usage:
Logger::log('CREATE_TABLE', 'customers', 'Created with 5 columns');
Logger::log('DELETE_ROW', 'customers', 'ID: 25');