<?php
// api.php
require_once 'config/database.php';
require_once 'includes/functions.php';

requireLogin();

header('Content-Type: application/json');

$database = new Database();
$conn = $database->getConnection();

$action = $_POST['action'] ?? $_GET['action'] ?? '';

try {
    switch ($action) {
        case 'get_table_data':
            // NEW: Load table data with pagination
            $tableName = sanitizeTableName($_GET['table']);
            $offset = intval($_GET['offset'] ?? 0);
            $limit = intval($_GET['limit'] ?? 100);
            
            if (empty($tableName)) {
                throw new Exception('Table name is required');
            }
            
            $sql = "SELECT * FROM `$tableName` LIMIT :limit OFFSET :offset";
            $stmt = $conn->prepare($sql);
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();
            $data = $stmt->fetchAll();
            
            echo json_encode(['success' => true, 'data' => $data]);
            break;
            
        case 'create_table':
            $tableName = sanitizeTableName($_POST['table_name']);
            $numColumns = intval($_POST['num_columns']);
            
            if (empty($tableName)) {
                throw new Exception('Table name is required');
            }
            
            $sql = "CREATE TABLE `$tableName` (";
            $columns = [];
            
            for ($i = 0; $i < $numColumns; $i++) {
                $colName = sanitizeColumnName($_POST["col_name_$i"]);
                $colType = $_POST["col_type_$i"];
                $isPK = isset($_POST["col_pk_$i"]);
                $isAI = isset($_POST["col_ai_$i"]);
                
                if (empty($colName)) continue;
                
                $colDef = "`$colName` $colType";
                if ($isPK) $colDef .= " PRIMARY KEY";
                if ($isAI) $colDef .= " AUTO_INCREMENT";
                
                $columns[] = $colDef;
            }
            
            if (empty($columns)) {
                throw new Exception('At least one column is required');
            }
            
            $sql .= implode(', ', $columns) . ")";
            
            $conn->exec($sql);
            echo json_encode(['success' => true, 'message' => 'Table created successfully']);
            break;
            
        case 'delete_table':
            $tableName = sanitizeTableName($_POST['table_name']);
            if (empty($tableName)) {
                throw new Exception('Table name is required');
            }
            
            $stmt = $conn->prepare("DROP TABLE `$tableName`");
            $stmt->execute();
            echo json_encode(['success' => true, 'message' => 'Table deleted successfully']);
            break;
            
        case 'rename_table':
            $oldName = sanitizeTableName($_POST['old_name']);
            $newName = sanitizeTableName($_POST['new_name']);
            
            if (empty($oldName) || empty($newName)) {
                throw new Exception('Both old and new table names are required');
            }
            
            $stmt = $conn->prepare("RENAME TABLE `$oldName` TO `$newName`");
            $stmt->execute();
            echo json_encode(['success' => true, 'message' => 'Table renamed successfully']);
            break;
            
        case 'add_column':
            $tableName = sanitizeTableName($_POST['table_name']);
            $columnName = sanitizeColumnName($_POST['column_name']);
            $dataType = $_POST['data_type'];
            $nullable = isset($_POST['nullable']) ? 'NULL' : 'NOT NULL';
            
            if (empty($tableName) || empty($columnName)) {
                throw new Exception('Table name and column name are required');
            }
            
            $sql = "ALTER TABLE `$tableName` ADD `$columnName` $dataType $nullable";
            $conn->exec($sql);
            echo json_encode(['success' => true, 'message' => 'Column added successfully']);
            break;
            
        case 'delete_column':
            $tableName = sanitizeTableName($_POST['table_name']);
            $columnName = sanitizeColumnName($_POST['column_name']);
            
            if (empty($tableName) || empty($columnName)) {
                throw new Exception('Table name and column name are required');
            }
            
            $sql = "ALTER TABLE `$tableName` DROP COLUMN `$columnName`";
            $conn->exec($sql);
            echo json_encode(['success' => true, 'message' => 'Column deleted successfully']);
            break;
            
        case 'add_row':
            $tableName = sanitizeTableName($_POST['table_name']);
            if (empty($tableName)) {
                throw new Exception('Table name is required');
            }
            
            $columns = [];
            $values = [];
            $placeholders = [];
            
            foreach ($_POST as $key => $value) {
                if ($key === 'action' || $key === 'table_name') continue;
                $columns[] = "`" . sanitizeColumnName($key) . "`";
                $values[] = $value;
                $placeholders[] = '?';
            }
            
            if (empty($columns)) {
                throw new Exception('No data to insert');
            }
            
            $sql = "INSERT INTO `$tableName` (" . implode(', ', $columns) . ") VALUES (" . implode(', ', $placeholders) . ")";
            $stmt = $conn->prepare($sql);
            $stmt->execute($values);
            echo json_encode(['success' => true, 'message' => 'Row added successfully']);
            break;
            
        case 'update_row':
            $tableName = sanitizeTableName($_POST['table_name']);
            $primaryKey = sanitizeColumnName($_POST['primary_key']);
            
            if (empty($tableName) || empty($primaryKey)) {
                throw new Exception('Table name and primary key are required');
            }
            
            $setClauses = [];
            $values = [];
            $pkValue = null;
            
            foreach ($_POST as $key => $value) {
                if (in_array($key, ['action', 'table_name', 'primary_key'])) continue;
                
                $cleanKey = sanitizeColumnName($key);
                if ($key === $primaryKey) {
                    $pkValue = $value;
                } else {
                    $setClauses[] = "`$cleanKey` = ?";
                    $values[] = $value;
                }
            }
            
            if (empty($setClauses) || $pkValue === null) {
                throw new Exception('No data to update');
            }
            
            $values[] = $pkValue;
            $sql = "UPDATE `$tableName` SET " . implode(', ', $setClauses) . " WHERE `$primaryKey` = ?";
            $stmt = $conn->prepare($sql);
            $stmt->execute($values);
            echo json_encode(['success' => true, 'message' => 'Row updated successfully']);
            break;
            
        case 'delete_row':
            $tableName = sanitizeTableName($_POST['table_name']);
            $pkColumn = sanitizeColumnName($_POST['pk_column']);
            $pkValue = $_POST['pk_value'];
            
            if (empty($tableName) || empty($pkColumn)) {
                throw new Exception('Table name and primary key column are required');
            }
            
            $sql = "DELETE FROM `$tableName` WHERE `$pkColumn` = ?";
            $stmt = $conn->prepare($sql);
            $stmt->execute([$pkValue]);
            echo json_encode(['success' => true, 'message' => 'Row deleted successfully']);
            break;
            
        default:
            throw new Exception('Invalid action');
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>