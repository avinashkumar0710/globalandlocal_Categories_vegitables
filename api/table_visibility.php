<?php
require_once '../config/database.php';

header('Content-Type: application/json');

try {
    $db = new Database();
    $pdo = $db->getConnection();
    
    if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'get_tables') {
        // Fetch all tables
        $stmt = $pdo->query("SHOW TABLES");
        $allTables = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        // Fetch visibility settings
        $visibilityStmt = $pdo->query("SELECT table_name, is_visible FROM table_visibility");
        $visibilitySettings = [];
        while ($row = $visibilityStmt->fetch(PDO::FETCH_ASSOC)) {
            $visibilitySettings[$row['table_name']] = (bool)$row['is_visible'];
        }
        
        // Sensitive tables that should always be hidden
        $sensitiveTables = ['activity_log', 'admin_login'];
        
        // Prepare response data
        $tablesData = [];
        foreach ($allTables as $table) {
            // Skip sensitive tables
            if (in_array($table, $sensitiveTables)) continue;
            
            // Get current visibility setting
            $isVisible = isset($visibilitySettings[$table]) ? $visibilitySettings[$table] : true;
            
            $tablesData[] = [
                'name' => $table,
                'visible' => $isVisible
            ];
        }
        
        echo json_encode([
            'success' => true,
            'tables' => $tablesData
        ]);
        exit;
    }
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_visibility') {
        // Handle visibility updates
        foreach ($_POST as $key => $value) {
            if (strpos($key, 'visibility_') === 0) {
                $tableName = substr($key, 11); // Remove 'visibility_' prefix
                $isVisible = $value === '1' ? 1 : 0;
                
                try {
                    $stmt = $pdo->prepare("INSERT INTO table_visibility (table_name, is_visible) VALUES (?, ?) ON DUPLICATE KEY UPDATE is_visible = ?");
                    $stmt->execute([$tableName, $isVisible, $isVisible]);
                } catch (Exception $e) {
                    // Handle error
                    echo json_encode([
                        'success' => false,
                        'message' => 'Error updating visibility for table: ' . $tableName
                    ]);
                    exit;
                }
            }
        }
        
        echo json_encode([
            'success' => true,
            'message' => 'Table visibility settings updated successfully!'
        ]);
        exit;
    }
    
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request'
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?>