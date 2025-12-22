<?php
require "config/database.php";

$db = new Database();
$pdo = $db->getConnection();

// Fetch all tables
$stmt = $pdo->query("SHOW TABLES");
$allTables = $stmt->fetchAll(PDO::FETCH_COLUMN);

// Filter out sensitive tables
$sensitiveTables = ['activity_log', 'admin_login'];
$tables = array_filter($allTables, function($table) use ($sensitiveTables) {
    return !in_array($table, $sensitiveTables);
});

echo "<h1>Database Tables Analysis</h1>";
echo "<p>Total tables: " . count($tables) . "</p>";

// Check each table
foreach ($tables as $table) {
    echo "<h2>Table: " . htmlspecialchars($table) . "</h2>";
    
    // Get column names
    $colsStmt = $pdo->query("SHOW COLUMNS FROM `$table`");
    $columns = $colsStmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "<p>Columns: " . implode(', ', array_map('htmlspecialchars', $columns)) . "</p>";
    
    // Look for country-related columns
    $countryColumns = [
        'country', 'country_name', 'nation', 'market', 'destination', 
        'import_country', 'export_country', 'trading_partner', 'partner'
    ];
    
    $countryColumn = null;
    foreach ($columns as $column) {
        $lowerColumn = strtolower($column);
        foreach ($countryColumns as $countryCol) {
            if (strpos($lowerColumn, $countryCol) !== false) {
                $countryColumn = $column;
                break 2;
            }
        }
    }
    
    if ($countryColumn) {
        echo "<p>Found country column: <strong>" . htmlspecialchars($countryColumn) . "</strong></p>";
        
        // Fetch sample data
        $sampleStmt = $pdo->query("SELECT DISTINCT `$countryColumn` FROM `$table` WHERE `$countryColumn` IS NOT NULL AND `$countryColumn` != '' LIMIT 10");
        $samples = $sampleStmt->fetchAll(PDO::FETCH_COLUMN);
        
        echo "<p>Sample values: " . implode(', ', array_map(function($s) {
            return '"' . htmlspecialchars($s) . '"';
        }, $samples)) . "</p>";
    } else {
        echo "<p>No country column found</p>";
    }
    
    echo "<hr>";
}
?>