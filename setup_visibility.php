<?php
require "config/database.php";

$db = new Database();
$pdo = $db->getConnection();

if ($pdo) {
    // Create table_visibility table
    try {
        $pdo->exec('CREATE TABLE IF NOT EXISTS table_visibility (
            id INT AUTO_INCREMENT PRIMARY KEY, 
            table_name VARCHAR(255) UNIQUE, 
            is_visible BOOLEAN DEFAULT TRUE, 
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, 
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )');
        echo "Table 'table_visibility' created successfully\n";
    } catch (Exception $e) {
        echo "Error creating table: " . $e->getMessage() . "\n";
    }
    
    // Insert default visibility settings for sensitive tables (set to invisible)
    $sensitiveTables = [
        'privacy_policy',
        'otp_verification', 
        'mail_settings',
        'contact_submissions',
        'master_sections_web',
        'master_sections_web2'
    ];
    
    foreach ($sensitiveTables as $table) {
        try {
            $stmt = $pdo->prepare("INSERT IGNORE INTO table_visibility (table_name, is_visible) VALUES (?, ?)");
            $stmt->execute([$table, false]);
            echo "Set visibility for '$table' to hidden\n";
        } catch (Exception $e) {
            echo "Error setting visibility for '$table': " . $e->getMessage() . "\n";
        }
    }
    
    echo "Setup completed successfully!\n";
} else {
    echo "Database connection failed\n";
}
?>