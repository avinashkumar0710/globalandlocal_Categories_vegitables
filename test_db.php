<?php
require "config/database.php";

$db = new Database();
$pdo = $db->getConnection();

if ($pdo) {
    echo "Connected successfully\n";
    
    // Try to get tables
    try {
        $stmt = $pdo->query("SHOW TABLES");
        $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
        echo "Tables in database:\n";
        foreach ($tables as $table) {
            echo "- " . $table . "\n";
        }
    } catch (Exception $e) {
        echo "Error fetching tables: " . $e->getMessage() . "\n";
    }
} else {
    echo "Connection failed\n";
}
?>