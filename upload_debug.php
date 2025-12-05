<?php
// upload_debug.php - Debug upload issues
require_once 'config/database.php';
require_once 'includes/functions.php';
require_once 'includes/SimpleExcelReader.php';

session_start();
$_SESSION['user_logged_in'] = true;
$_SESSION['username'] = 'admin';

$database = new Database();
$conn = $database->getConnection();

$tables = getAllTables($conn);
$debugInfo = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['excel_file'])) {
    
    // Capture ALL POST data
    $debugInfo['POST Data'] = $_POST;
    $debugInfo['FILES Data'] = $_FILES['excel_file'];
    
    try {
        $file = $_FILES['excel_file'];
        
        // Get table name
        $tableName = !empty($_POST['new_table_name']) ? 
                     sanitizeTableName($_POST['new_table_name']) : 
                     sanitizeTableName($_POST['table_name']);
        
        $debugInfo['Table Name (Processed)'] = $tableName;
        
        // Check create new table
        $createNewTable = !empty($_POST['new_table_name']) || isset($_POST['create_new_table']);
        $debugInfo['Create New Table'] = $createNewTable ? 'YES' : 'NO';
        $debugInfo['new_table_name field'] = $_POST['new_table_name'] ?? 'NOT SET';
        $debugInfo['create_new_table checkbox'] = isset($_POST['create_new_table']) ? 'CHECKED' : 'NOT CHECKED';
        
        // Check if table exists
        $tableExists = in_array($tableName, $tables);
        $debugInfo['Table Exists in DB'] = $tableExists ? 'YES' : 'NO';
        $debugInfo['Existing Tables'] = implode(', ', $tables);
        
        if (empty($tableName)) {
            throw new Exception('Table name is required');
        }
        
        if ($file['error'] !== UPLOAD_ERR_OK) {
            throw new Exception('File upload error code: ' . $file['error']);
        }
        
        // Read file
        $data = SimpleExcelReader::read($file['tmp_name']);
        $debugInfo['Rows Read'] = count($data);
        
        if (empty($data)) {
            throw new Exception('File is empty');
        }
        
        // Get headers
        $headers = array_shift($data);
        $headers = array_map(function($header) {
            return sanitizeColumnName(trim($header));
        }, $headers);
        $headers = array_filter($headers);
        
        $debugInfo['Headers Found'] = implode(', ', $headers);
        $debugInfo['Number of Columns'] = count($headers);
        
        if (empty($headers)) {
            throw new Exception('No valid column headers');
        }
        
        // Decision point
        if ($createNewTable) {
            $debugInfo['Action'] = 'CREATE NEW TABLE';
            
            if ($tableExists) {
                $debugInfo['WARNING'] = "Table '$tableName' already exists! Will fail.";
                throw new Exception("Table '$tableName' already exists. Choose different name.");
            }
            
            // Create table
            $columns = [];
            $columns[] = "id INT PRIMARY KEY AUTO_INCREMENT";
            
            foreach ($headers as $header) {
                $columns[] = "`$header` VARCHAR(255)";
            }
            
            $sql = "CREATE TABLE `$tableName` (" . implode(', ', $columns) . ")";
            $debugInfo['SQL Query'] = $sql;
            
            $conn->exec($sql);
            $debugInfo['Table Creation'] = 'SUCCESS';
            
        } else {
            $debugInfo['Action'] = 'ADD TO EXISTING TABLE';
            
            if (!$tableExists) {
                $debugInfo['ERROR'] = "Table '$tableName' does not exist!";
                throw new Exception("Table '$tableName' does not exist. Check 'Create New Table'.");
            }
            
            $debugInfo['Table Selection'] = 'Table exists, will add rows';
        }
        
        // Insert data
        $inserted = 0;
        $placeholders = implode(', ', array_fill(0, count($headers), '?'));
        $columnList = '`' . implode('`, `', $headers) . '`';
        
        $insertSql = "INSERT INTO `$tableName` ($columnList) VALUES ($placeholders)";
        $stmt = $conn->prepare($insertSql);
        
        foreach ($data as $row) {
            $rowData = array_slice($row, 0, count($headers));
            while (count($rowData) < count($headers)) {
                $rowData[] = '';
            }
            if (empty(array_filter($rowData))) continue;
            
            try {
                $stmt->execute($rowData);
                $inserted++;
            } catch (Exception $e) {
                continue;
            }
        }
        
        $debugInfo['Rows Inserted'] = $inserted;
        $success = true;
        
    } catch (Exception $e) {
        $debugInfo['ERROR MESSAGE'] = $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload Debug</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <div class="card">
            <div class="card-header bg-warning">
                <h4 class="mb-0">🔍 Upload Debug Tool</h4>
            </div>
            <div class="card-body">
                <div class="alert alert-info">
                    <strong>Purpose:</strong> This page shows EXACTLY what's happening during upload.
                </div>

                <form method="POST" enctype="multipart/form-data">
                    <div class="mb-3">
                        <label class="form-label"><strong>Select Existing Table OR Enter New Name Below:</strong></label>
                        <select class="form-select" name="table_name" id="tableName">
                            <option value="">-- Create New Table --</option>
                            <?php foreach ($tables as $table): ?>
                                <option value="<?php echo htmlspecialchars($table); ?>">
                                    <?php echo htmlspecialchars($table); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3" id="newTableDiv">
                        <label class="form-label"><strong>New Table Name:</strong></label>
                        <input type="text" class="form-control" name="new_table_name" id="newTableName" 
                               placeholder="Enter new table name">
                    </div>

                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="create_new_table" 
                                   id="createNewTable" checked>
                            <label class="form-check-label" for="createNewTable">
                                <strong>Create new table if doesn't exist</strong>
                            </label>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label"><strong>Upload File:</strong></label>
                        <input type="file" class="form-control" name="excel_file" accept=".xlsx,.csv" required>
                    </div>

                    <button type="submit" class="btn btn-primary">
                        🔍 Debug Upload
                    </button>
                    <a href="upload.php" class="btn btn-secondary">
                        ← Back to Normal Upload
                    </a>
                </form>

                <?php if (!empty($debugInfo)): ?>
                    <hr>
                    <h5>📊 Debug Information:</h5>
                    
                    <?php if ($success): ?>
                        <div class="alert alert-success">
                            <strong>✅ SUCCESS!</strong> Upload completed successfully.
                        </div>
                    <?php else: ?>
                        <div class="alert alert-danger">
                            <strong>❌ FAILED!</strong> Upload did not complete.
                        </div>
                    <?php endif; ?>

                    <table class="table table-bordered table-sm">
                        <tbody>
                            <?php foreach ($debugInfo as $key => $value): ?>
                                <tr>
                                    <th style="width: 30%"><?php echo htmlspecialchars($key); ?></th>
                                    <td>
                                        <?php 
                                        if (is_array($value)) {
                                            echo '<pre>' . print_r($value, true) . '</pre>';
                                        } else {
                                            echo htmlspecialchars($value); 
                                        }
                                        ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        const tableName = document.getElementById('tableName');
        const newTableDiv = document.getElementById('newTableDiv');
        const newTableName = document.getElementById('newTableName');
        const createNewTable = document.getElementById('createNewTable');

        function toggleInputs() {
            if (tableName.value === '') {
                newTableDiv.style.display = 'block';
                newTableName.required = true;
                createNewTable.checked = true;
                createNewTable.disabled = true;
            } else {
                newTableDiv.style.display = 'none';
                newTableName.required = false;
                newTableName.value = '';
                createNewTable.disabled = false;
            }
        }

        toggleInputs();
        tableName.addEventListener('change', toggleInputs);
    </script>
</body>
</html>