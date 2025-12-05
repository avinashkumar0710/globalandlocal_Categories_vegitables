<?php
// upload.php - Excel/CSV Upload with modern UI
require_once 'config/database.php';
require_once 'includes/functions.php';
require_once 'includes/SimpleExcelReader.php';

requireLogin();

$database = new Database();
$conn = $database->getConnection();

$message = '';
$messageType = '';
$tables = getAllTables($conn);

// Get table creation dates and row counts
$tablesWithInfo = [];
foreach ($tables as $table) {
    $stmt = $conn->query("SELECT COUNT(*) as row_count FROM `$table`");
    $rowCount = $stmt->fetch(PDO::FETCH_ASSOC)['row_count'];
    
    // Get creation date
    $stmt = $conn->query("SELECT CREATE_TIME FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = '$table'");
    $createTime = $stmt->fetch(PDO::FETCH_ASSOC)['CREATE_TIME'];
    
    $tablesWithInfo[] = [
        'name' => $table,
        'rows' => $rowCount,
        'created' => $createTime ? date('Y-m-d', strtotime($createTime)) : 'N/A'
    ];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['excel_file'])) {
    try {
        $file = $_FILES['excel_file'];
        
        // Get table name from either text input or dropdown
        $tableName = !empty($_POST['new_table_name']) ? 
                     sanitizeTableName($_POST['new_table_name']) : 
                     sanitizeTableName($_POST['table_name']);
        
        // If new table name is provided, force create new table
        $createNewTable = !empty($_POST['new_table_name']) || isset($_POST['create_new_table']);
        
        if (empty($tableName)) {
            throw new Exception('Table name is required');
        }
        
        // Validate file upload
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $errorMessages = [
                UPLOAD_ERR_INI_SIZE => 'File exceeds upload_max_filesize in php.ini',
                UPLOAD_ERR_FORM_SIZE => 'File exceeds MAX_FILE_SIZE in HTML form',
                UPLOAD_ERR_PARTIAL => 'File was only partially uploaded',
                UPLOAD_ERR_NO_FILE => 'No file was uploaded',
                UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder',
                UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
                UPLOAD_ERR_EXTENSION => 'File upload stopped by extension',
            ];
            throw new Exception($errorMessages[$file['error']] ?? 'File upload error');
        }
        
        // Validate file size (10MB max)
        $maxFileSize = 10 * 1024 * 1024; // 10MB
        if ($file['size'] > $maxFileSize) {
            throw new Exception('File size exceeds 10MB limit');
        }
        
        // Validate file extension
        $allowedExtensions = ['xlsx', 'csv'];
        $fileExtension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        
        if (!in_array($fileExtension, $allowedExtensions)) {
            throw new Exception('Invalid file type. Only .xlsx and .csv files are allowed');
        }
        
        // Read the file
        try {
            $data = SimpleExcelReader::read($file['tmp_name']);
        } catch (Exception $e) {
            throw new Exception('File reading error: ' . $e->getMessage() . ' (Extension: ' . $fileExtension . ')');
        }
        
        if (empty($data)) {
            throw new Exception('File is empty or cannot be read');
        }
        
        // First row as column headers
        $headers = array_shift($data);
        $headers = array_map(function($header) {
            return sanitizeColumnName(trim($header));
        }, $headers);
        
        // Remove empty headers
        $headers = array_filter($headers);
        
        if (empty($headers)) {
            throw new Exception('No valid column headers found in first row');
        }
        
        // Check for duplicate column names
        if (count($headers) !== count(array_unique($headers))) {
            throw new Exception('Duplicate column names found. Each column must have a unique name.');
        }
        
        if ($createNewTable) {
            // Check if table already exists
            if (in_array($tableName, $tables)) {
                throw new Exception("Table '$tableName' already exists. Choose a different name or uncheck 'Create New Table'.");
            }
            
            // Create new table with auto-detected columns
            $columns = [];
            $columns[] = "id INT PRIMARY KEY AUTO_INCREMENT";
            
            foreach ($headers as $header) {
                $columnType = detectColumnType($data, array_search($header, $headers));
                $columns[] = "`$header` $columnType";
            }
            
            $sql = "CREATE TABLE `$tableName` (" . implode(', ', $columns) . ")";
            $conn->exec($sql);
            $message = "✅ Table '$tableName' created successfully with " . count($headers) . " columns. ";
        } else {
            // Check if table exists
            if (!in_array($tableName, $tables)) {
                throw new Exception("Table '$tableName' does not exist. Check 'Create New Table' to create it.");
            }
            
            // Verify columns match
            $existingColumns = getTableColumns($conn, $tableName);
            $existingColumnNames = array_map(function($col) {
                return $col['Field'];
            }, $existingColumns);
            
            // Remove 'id' from comparison if it exists
            $existingColumnNames = array_diff($existingColumnNames, ['id']);
            
            // Check if uploaded columns match existing table
            $missingColumns = array_diff($headers, $existingColumnNames);
            if (!empty($missingColumns)) {
                throw new Exception("Column mismatch! These columns don't exist in table: " . implode(', ', $missingColumns));
            }
        }
        
        // Insert data
        $inserted = 0;
        $failed = 0;
        $errors = [];
        
        $placeholders = implode(', ', array_fill(0, count($headers), '?'));
        $columnList = '`' . implode('`, `', $headers) . '`';
        
        $insertSql = "INSERT INTO `$tableName` ($columnList) VALUES ($placeholders)";
        $stmt = $conn->prepare($insertSql);
        
        foreach ($data as $rowIndex => $row) {
            $rowData = array_slice($row, 0, count($headers));
            
            while (count($rowData) < count($headers)) {
                $rowData[] = '';
            }
            
            if (empty(array_filter($rowData, function($val) { return $val !== ''; }))) {
                continue;
            }
            
            try {
                $stmt->execute($rowData);
                $inserted++;
            } catch (Exception $e) {
                $failed++;
                if ($failed <= 5) {
                    $errors[] = "Row " . ($rowIndex + 2) . ": " . $e->getMessage();
                }
                continue;
            }
        }
        
        $message .= "✅ $inserted rows imported successfully!";
        if ($failed > 0) {
            $message .= " ⚠️ $failed rows failed.";
            if (!empty($errors)) {
                $message .= " Errors: " . implode('; ', $errors);
            }
        }
        $messageType = 'success';
        
        // Refresh tables list
        $tables = getAllTables($conn);
        $tablesWithInfo = [];
        foreach ($tables as $table) {
            $stmt = $conn->query("SELECT COUNT(*) as row_count FROM `$table`");
            $rowCount = $stmt->fetch(PDO::FETCH_ASSOC)['row_count'];
            $stmt = $conn->query("SELECT CREATE_TIME FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = '$table'");
            $createTime = $stmt->fetch(PDO::FETCH_ASSOC)['CREATE_TIME'];
            $tablesWithInfo[] = [
                'name' => $table,
                'rows' => $rowCount,
                'created' => $createTime ? date('Y-m-d', strtotime($createTime)) : 'N/A'
            ];
        }
        
    } catch (Exception $e) {
        $message = '❌ Error: ' . $e->getMessage();
        $messageType = 'danger';
    }
}

function detectColumnType($data, $columnIndex) {
    $sample = array_slice($data, 0, min(50, count($data)));
    $isNumeric = true;
    $isInteger = true;
    $isDate = true;
    $maxLength = 0;
    
    foreach ($sample as $row) {
        if (!isset($row[$columnIndex])) continue;
        
        $value = trim($row[$columnIndex]);
        
        if (empty($value)) continue;
        
        $maxLength = max($maxLength, strlen($value));
        
        if (!is_numeric($value)) {
            $isNumeric = false;
            $isInteger = false;
        } elseif (strpos($value, '.') !== false) {
            $isInteger = false;
        }
        
        if (!strtotime($value)) {
            $isDate = false;
        }
    }
    
    if ($isInteger) {
        return 'INT';
    }
    
    if ($isNumeric) {
        return 'DECIMAL(15,2)';
    }
    
    if ($isDate) {
        return 'DATETIME';
    }
    
    if ($maxLength > 255) {
        return 'TEXT';
    }
    
    return 'VARCHAR(255)';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload Excel/CSV - Database Manager</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            height: 100vh;
            overflow: hidden;
            background: linear-gradient(135deg, #f8fafc 0%, #e0e7ff 100%);
        }
        
        /* Navigation */
        .navbar {
            background: linear-gradient(135deg, #7c3aed 0%, #a146e5 100%);
            color: white;
            padding: 12px 24px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        
        .navbar-brand {
            display: flex;
            align-items: center;
            gap: 12px;
            color: white;
            text-decoration: none;
            font-size: 16px;
            font-weight: 600;
        }
        
        .navbar-brand:hover {
            color: #e0e7ff;
        }
        
        .navbar-user {
            background: rgba(255,255,255,0.2);
            padding: 6px 16px;
            border-radius: 20px;
            font-size: 13px;
        }
        
        /* Main Container */
        .main-container {
            display: flex;
            height: calc(100vh - 48px);
            overflow: hidden;
        }
        
        /* Left Sidebar - 30% */
        .sidebar {
            width: 30%;
            background: white;
            border-right: 1px solid #e2e8f0;
            overflow-y: auto;
            padding: 16px;
        }
        
        .sidebar::-webkit-scrollbar {
            width: 6px;
        }
        
        .sidebar::-webkit-scrollbar-track {
            background: #f1f5f9;
        }
        
        .sidebar::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 3px;
        }
        
        .sidebar::-webkit-scrollbar-thumb:hover {
            background: #94a3b8;
        }
        
        /* Section Headers */
        .section-header {
            background: linear-gradient(135deg, #10b981 0%, #14b8a6 100%);
            color: white;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 16px;
            box-shadow: 0 4px 6px rgba(16, 185, 129, 0.2);
        }
        
        .section-header h3 {
            font-size: 11px;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 8px;
            margin: 0;
        }
        
        .section-header p {
            font-size: 13px;
            margin: 4px 0 0 0;
            opacity: 0.9;
        }
        
        /* Sample Table */
        .sample-table-container {
            background: #f8fafc;
            border-radius: 8px;
            padding: 12px;
            margin-bottom: 16px;
            border: 1px solid #e2e8f0;
        }
        
        .sample-table {
            width: 100%;
            font-size: 13px;
            border-collapse: collapse;
        }
        
        .sample-table thead th {
            background: #1e293b;
            color: white;
            padding: 8px;
            text-align: left;
            font-weight: 600;
            font-size: 13px;
        }
        
        .sample-table tbody td {
            padding: 6px 8px;
            border-bottom: 1px solid #e2e8f0;
            font-size: 13px;
        }
        
        .sample-table tbody tr:last-child td {
            border-bottom: none;
        }
        
        /* Important Rules */
        .info-box {
            background: #fef3c7;
            border: 1px solid #fbbf24;
            border-radius: 8px;
            padding: 12px;
            margin-bottom: 16px;
        }
        
        .info-box h4 {
            font-size: 13px;
            font-weight: 700;
            color: #92400e;
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            gap: 4px;
        }
        
        .info-box ul {
            list-style: none;
            padding-left: 0;
            margin: 0;
        }
        
        .info-box li {
            font-size: 13px;
            color: #78350f;
            margin-bottom: 4px;
        }
        
        /* Tables Section Header */
        .tables-header {
            background: linear-gradient(135deg, #3b82f6 0%, #06b6d4 100%);
            color: white;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 12px;
            box-shadow: 0 4px 6px rgba(59, 130, 246, 0.2);
        }
        
        .tables-header h3 {
            font-size: 13px;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 8px;
            margin: 0;
        }
        
        /* Table Cards */
        .table-card {
            background: white;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 10px;
            margin-bottom: 8px;
            cursor: pointer;
            transition: all 0.2s;
            text-decoration: none;
            display: block;
            color: inherit;
        }
        
        .table-card:hover {
            border-color: #3b82f6;
            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.15);
            transform: translateY(-2px);
        }
        
        .table-card-header {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 6px;
        }
        
        .table-card-name {
            font-size: 13px;
            font-weight: 600;
            color: #1e293b;
        }
        
        .table-card-meta {
            display: flex;
            gap: 12px;
            font-size: 15px;
            color: #64748b;
        }
        
        /* Right Main Area - 70% */
        .main-content {
            flex: 1;
            overflow-y: auto;
            padding: 24px;
            background: linear-gradient(135deg, #f8fafc 0%, #dbeafe 100%);
        }
        
        .main-content::-webkit-scrollbar {
            width: 8px;
        }
        
        .main-content::-webkit-scrollbar-track {
            background: #f1f5f9;
        }
        
        .main-content::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 4px;
        }
        
        .content-wrapper {
            max-width: 1000px;
            margin: 0 auto;
        }
        
        /* Main Header */
        .main-header {
            background: linear-gradient(135deg, #7c3aed 0%, #a146e5 100%);
            color: white;
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 20px;
            box-shadow: 0 8px 16px rgba(124, 58, 237, 0.3);
            display: flex;
            align-items: center;
            gap: 16px;
        }
        
        .main-header-icon {
            background: rgba(255,255,255,0.2);
            padding: 12px;
            border-radius: 10px;
        }
        
        .main-header h2 {
            font-size: 16px;
            font-weight: 700;
            margin: 0 0 4px 0;
        }
        
        .main-header p {
            font-size: 13px;
            margin: 0;
            opacity: 0.9;
        }
        
        /* Alert Messages */
        .alert {
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 16px;
            font-size: 13px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        
        .alert-success {
            background: #d1fae5;
            color: #065f46;
            border: 1px solid #10b981;
        }
        
        .alert-danger {
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid #ef4444;
        }
        
        .alert-close {
            background: none;
            border: none;
            font-size: 18px;
            cursor: pointer;
            color: inherit;
            opacity: 0.7;
        }
        
        .alert-close:hover {
            opacity: 1;
        }
        
        /* Form Sections */
        .form-section {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
            padding: 20px;
            margin-bottom: 16px;
        }
        
        .form-label {
            display: block;
            font-size: 13px;
            font-weight: 700;
            color: #334155;
            margin-bottom: 8px;
        }
        
        .form-label .required {
            color: #ef4444;
        }
        
        .form-control {
            width: 100%;
            padding: 10px 12px;
            font-size: 13px;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            transition: all 0.2s;
        }
        
        .form-control:focus {
            outline: none;
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }
        
        .form-text {
            font-size: 13px;
            color: #64748b;
            margin-top: 6px;
        }
        
        /* Checkbox */
        .checkbox-container {
            background: #dbeafe;
            border: 1px solid #3b82f6;
            border-radius: 8px;
            padding: 12px;
            margin-top: 16px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .checkbox-container input[type="checkbox"] {
            width: 16px;
            height: 16px;
            cursor: pointer;
        }
        
        .checkbox-container label {
            font-size: 13px;
            font-weight: 600;
            color: #1e3a8a;
            margin: 0;
            cursor: pointer;
        }
        /* Upload Box */
        .upload-box {
            border: 3px dashed #cbd5e1;
            border-radius: 12px;
            padding: 40px;
            text-align: center;
            background: #f8fafc;
            transition: all 0.3s;
            cursor: pointer;
        }
        
        .upload-box:hover {
            border-color: #3b82f6;
            background: #dbeafe;
        }
        
        .upload-box.dragover {
            border-color: #3b82f6;
            background: #bfdbfe;
            transform: scale(1.02);
        }
        
        .upload-box.has-file {
            border-color: #10b981;
            background: #d1fae5;
        }
        
        .upload-icon {
            width: 48px;
            height: 48px;
            margin: 0 auto 16px;
            background: #e2e8f0;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            color: #64748b;
        }
        
        .upload-box.has-file .upload-icon {
            background: #a7f3d0;
            color: #059669;
        }
        
        .upload-box h5 {
            font-size: 15px;
            font-weight: 700;
            color: #334155;
            margin: 0 0 6px 0;
        }
        
        .upload-box p {
            font-size: 15px;
            color: #64748b;
            margin: 0 0 12px 0;
        }
        
        .upload-box .file-name {
            font-size: 15px;
            font-weight: 700;
            color: #059669;
            margin-bottom: 4px;
        }
        
        .upload-box .file-size {
            font-size: 9px;
            color: #10b981;
        }
        
        .btn-browse {
            background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            box-shadow: 0 4px 6px rgba(59, 130, 246, 0.3);
        }
        
        .btn-browse:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 12px rgba(59, 130, 246, 0.4);
        }
        
        .btn-remove {
            background: none;
            border: none;
            color: #dc2626;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            margin-top: 8px;
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }
        
        .btn-remove:hover {
            color: #991b1b;
            text-decoration: underline;
        }
        
        .file-formats {
            margin-top: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 16px;
            font-size: 15px;
            color: #64748b;
        }
        
        /* Info Card */
        .info-card {
            background: linear-gradient(135deg, #ecfeff 0%, #dbeafe 100%);
            border: 1px solid #06b6d4;
            border-radius: 12px;
            padding: 16px;
            margin-bottom: 16px;
        }
        
        .info-card h4 {
            font-size: 15px;
            font-weight: 700;
            color: #0e7490;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        
        .info-card ul {
            list-style: none;
            padding-left: 0;
            margin: 0;
        }
        
        .info-card li {
            font-size: 12px;
            color: #0e7490;
            margin-bottom: 6px;
            padding-left: 16px;
            position: relative;
        }
        
        .info-card li:before {
            content: "✓";
            position: absolute;
            left: 0;
            font-weight: 700;
        }
        
        /* Upload Button */
        .btn-upload {
            width: 100%;
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
            padding: 16px;
            border: none;
            border-radius: 12px;
            font-size: 13px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.2s;
            box-shadow: 0 8px 16px rgba(16, 185, 129, 0.3);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }
        
        .btn-upload:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 24px rgba(16, 185, 129, 0.4);
        }
        
        .btn-upload:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }
        
        /* Hidden input */
        .hidden {
            display: none;
        }
        
        /* Spinner */
        .spinner {
            display: inline-block;
            width: 14px;
            height: 14px;
            border: 2px solid rgba(255,255,255,0.3);
            border-top-color: white;
            border-radius: 50%;
            animation: spin 0.6s linear infinite;
        }
        
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        
        /* Responsive */
        @media (max-width: 1024px) {
            .sidebar {
                width: 35%;
            }
        }
        
        @media (max-width: 768px) {
            .main-container {
                flex-direction: column;
            }
            
            .sidebar {
                width: 100%;
                max-height: 40vh;
            }
            
            .main-content {
                height: 60vh;
            }
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <div class="navbar">
        <a href="index.php" class="navbar-brand">
            <i class="bi bi-arrow-left"></i>
            <span>Database Manager</span>
            <span style="opacity: 0.7;">/ Upload Data</span>
        </a>
        <div class="navbar-user">
            Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?>
        </div>
    </div>

    <!-- Main Container -->
    <div class="main-container">
        <!-- Left Sidebar - 30% -->
        <div class="sidebar">
            <!-- Sample Format Section -->
            <!-- <div class="section-header">
                <h3>
                    <i class="bi bi-file-earmark-excel"></i>
                    Sample Format
                </h3>
                <p>First row = column names</p>
            </div> -->

            <!-- <div class="sample-table-container">
                <table class="sample-table">
                    <thead>
                        <tr>
                            <th>name</th>
                            <th>email</th>
                            <th>age</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>John Doe</td>
                            <td style="color: #3b82f6;">john@mail.com</td>
                            <td>25</td>
                        </tr>
                        <tr>
                            <td>Jane Smith</td>
                            <td style="color: #3b82f6;">jane@mail.com</td>
                            <td>30</td>
                        </tr>
                        <tr>
                            <td>Mike Ross</td>
                            <td style="color: #3b82f6;">mike@mail.com</td>
                            <td>28</td>
                        </tr>
                    </tbody>
                </table>
            </div> -->

            <!-- Important Rules -->
            <div class="info-box">
                <h4>
                    <i class="bi bi-exclamation-triangle"></i>
                    Important Rules
                </h4>
                <ul>
                    <li>• First row = column names</li>
                    <li>• Column names must be unique</li>
                    <li>• No special characters</li>
                    <li>• Don't include 'id' column</li>
                    <li>• Max file size: 10MB</li>
                </ul>
            </div>

            <!-- Existing Tables -->
            <div class="tables-header">
                <h3>
                    <i class="bi bi-database"></i>
                    Existing Tables (<?php echo count($tablesWithInfo); ?>)
                </h3>
            </div>

            <?php if (!empty($tablesWithInfo)): ?>
                <?php foreach ($tablesWithInfo as $table): ?>
                    <a href="index.php?table=<?php echo urlencode($table['name']); ?>" class="table-card">
                        <div class="table-card-header">
                            <i class="bi bi-table" style="color: #3b82f6; font-size: 15px;"></i>
                            <span class="table-card-name"><?php echo htmlspecialchars($table['name']); ?></span>
                        </div>
                        <div class="table-card-meta">
                            <span>📅 <?php echo htmlspecialchars($table['created']); ?></span>
                            <span>📊 <?php echo number_format($table['rows']); ?> rows</span>
                        </div>
                    </a>
                <?php endforeach; ?>
            <?php else: ?>
                <div style="text-align: center; padding: 20px; color: #64748b; font-size: 15px;">
                    <i class="bi bi-inbox" style="font-size: 15px; display: block; margin-bottom: 8px;"></i>
                    No tables yet. Create your first table by uploading a file!
                </div>
            <?php endif; ?>
        </div>

        <!-- Right Main Content - 70% -->
        <div class="main-content">
            <div class="content-wrapper">
                <!-- Main Header -->
                <div class="main-header">
                    <div class="main-header-icon">
                        <i class="bi bi-cloud-upload" style="font-size: 15px;"></i>
                    </div>
                    <div>
                        <h2>Upload Excel/CSV File</h2>
                        <p>Import your data instantly</p>
                    </div>
                </div>

                <!-- Alert Messages -->
                <?php if ($message): ?>
                    <div class="alert alert-<?php echo $messageType; ?>">
                        <span><?php echo $message; ?></span>
                        <button type="button" class="alert-close" onclick="this.parentElement.remove()">×</button>
                    </div>
                <?php endif; ?>

                <!-- Upload Form -->
                <form method="POST" enctype="multipart/form-data" id="uploadForm">
                    <!-- Table Selection -->
                    <div class="form-section">
                        <label class="form-label">Select Destination Table</label>
                        <select class="form-control" name="table_name" id="tableName">
                            <option value="">-- Create New Table --</option>
                            <?php foreach ($tables as $table): ?>
                                <option value="<?php echo htmlspecialchars($table); ?>">
                                    <?php echo htmlspecialchars($table); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>

                        <!-- New Table Name Input -->
                        <div id="newTableNameDiv" style="margin-top: 16px;">
                            <label class="form-label">
                                New Table Name <span class="required">*</span>
                            </label>
                            <input 
                                type="text" 
                                class="form-control" 
                                name="new_table_name" 
                                id="newTableName"
                                placeholder="e.g., customers, products, employees"
                            >
                            <div class="form-text">Use letters, numbers, and underscores only</div>
                        </div>

                        <!-- Create New Table Checkbox -->
                        <div class="checkbox-container">
                            <input 
                                type="checkbox" 
                                name="create_new_table" 
                                id="create_new_table" 
                                checked
                            >
                            <label for="create_new_table">
                                Create new table if it doesn't exist
                            </label>
                        </div>
                    </div>

                    <!-- File Upload Section -->
                    <div class="form-section">
                        <label class="form-label">
                            Select File <span class="required">*</span>
                        </label>
                        
                        <div class="upload-box" id="uploadBox" onclick="document.getElementById('excelFile').click()">
                            <div class="upload-icon" id="uploadIcon">
                                <i class="bi bi-cloud-upload"></i>
                            </div>
                            <div id="uploadContent">
                                <h5>Drag & Drop File Here</h5>
                                <p>or click to browse</p>
                                <button type="button" class="btn-browse">
                                    <i class="bi bi-folder2-open"></i> Browse Files
                                </button>
                            </div>
                            <input 
                                type="file" 
                                class="hidden" 
                                name="excel_file" 
                                id="excelFile" 
                                accept=".xlsx,.csv" 
                                required
                            >
                        </div>
                        
                        <div class="file-formats">
                            <span>📄 Formats: .xlsx, .csv</span>
                            <span>📦 Max: 10 MB</span>
                        </div>
                    </div>

                    <!-- Info Card -->
                    <!-- <div class="info-card">
                        <h4>
                            <i class="bi bi-info-circle"></i>
                            How It Works
                        </h4>
                        <ul>
                            <li>First row = column names (name, email, age)</li>
                            <li>Auto-detects data types (INT, VARCHAR, TEXT)</li>
                            <li>Empty rows skipped automatically</li>
                            <li>Auto-increment 'id' column added</li>
                            <li>Column names must be unique</li>
                        </ul>
                    </div> -->

                    <!-- Upload Button -->
                    <button type="submit" class="btn-upload" id="uploadBtn">
                        <i class="bi bi-upload"></i>
                        Upload and Import Data
                    </button>
                </form>
            </div>
        </div>
    </div>

    <script>
        // DOM Elements
        const uploadBox = document.getElementById('uploadBox');
        const fileInput = document.getElementById('excelFile');
        const uploadIcon = document.getElementById('uploadIcon');
        const uploadContent = document.getElementById('uploadContent');
        const tableName = document.getElementById('tableName');
        const newTableNameDiv = document.getElementById('newTableNameDiv');
        const newTableName = document.getElementById('newTableName');
        const createNewTable = document.getElementById('create_new_table');
        const uploadBtn = document.getElementById('uploadBtn');
        
        let selectedFile = null;

        // Show/hide new table name input
        function toggleNewTableInput() {
            if (tableName.value === '') {
                newTableNameDiv.style.display = 'block';
                newTableName.required = true;
                createNewTable.checked = true;
                createNewTable.disabled = true;
            } else {
                newTableNameDiv.style.display = 'none';
                newTableName.required = false;
                newTableName.value = '';
                createNewTable.disabled = false;
            }
        }

        // Initialize
        toggleNewTableInput();

        // Handle table selection changes
        tableName.addEventListener('change', toggleNewTableInput);

        // Prevent default drag behaviors
        ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
            uploadBox.addEventListener(eventName, preventDefaults, false);
            document.body.addEventListener(eventName, preventDefaults, false);
        });

        function preventDefaults(e) {
            e.preventDefault();
            e.stopPropagation();
        }

        // Highlight on drag
        ['dragenter', 'dragover'].forEach(eventName => {
            uploadBox.addEventListener(eventName, () => {
                uploadBox.classList.add('dragover');
            }, false);
        });

        ['dragleave', 'drop'].forEach(eventName => {
            uploadBox.addEventListener(eventName, () => {
                uploadBox.classList.remove('dragover');
            }, false);
        });

        // Handle drop
        uploadBox.addEventListener('drop', (e) => {
            const dt = e.dataTransfer;
            const files = dt.files;
            
            if (files.length > 0) {
                handleFiles(files);
            }
        });

        // Handle file input change
        fileInput.addEventListener('change', (e) => {
            if (e.target.files.length > 0) {
                handleFiles(e.target.files);
            }
        });

        // Handle files
        function handleFiles(files) {
            const file = files[0];
            
            // Validate file
            const allowedExtensions = ['xlsx', 'csv'];
            const maxSize = 10 * 1024 * 1024; // 10MB
            
            const extension = file.name.split('.').pop().toLowerCase();
            
            if (!allowedExtensions.includes(extension)) {
                alert('❌ Invalid file type! Only .xlsx and .csv files are allowed.');
                fileInput.value = '';
                return;
            }
            
            if (file.size > maxSize) {
                alert('❌ File too large! Maximum size is 10MB.');
                fileInput.value = '';
                return;
            }
            
            // Store file and update UI
            selectedFile = file;
            displayFileInfo(file);
        }

        // Display file info
        function displayFileInfo(file) {
            const extension = file.name.split('.').pop().toLowerCase();
            let iconClass = 'bi-file-earmark-excel';
            let iconColor = '#10b981';
            
            if (extension === 'csv') {
                iconClass = 'bi-file-earmark-text';
                iconColor = '#3b82f6';
            }
            
            const sizeKB = (file.size / 1024).toFixed(2);
            const sizeMB = (file.size / (1024 * 1024)).toFixed(2);
            const sizeText = file.size > 1024 * 1024 ? `${sizeMB} MB` : `${sizeKB} KB`;
            
            // Update upload box
            uploadBox.classList.add('has-file');
            
            uploadIcon.innerHTML = `<i class="${iconClass}" style="font-size: 20px; color: ${iconColor};"></i>`;
            
            uploadContent.innerHTML = `
                <div class="file-name">${file.name}</div>
                <div class="file-size">${sizeText}</div>
                <button type="button" class="btn-remove" onclick="clearFile(event)">
                    <i class="bi bi-x-circle"></i> Remove File
                </button>
            `;
        }

        // Clear file
        function clearFile(event) {
            if (event) {
                event.preventDefault();
                event.stopPropagation();
            }
            
            fileInput.value = '';
            selectedFile = null;
            
            uploadBox.classList.remove('has-file');
            
            uploadIcon.innerHTML = '<i class="bi bi-cloud-upload"></i>';
            
            uploadContent.innerHTML = `
                <h5>Drag & Drop File Here</h5>
                <p>or click to browse</p>
                <button type="button" class="btn-browse">
                    <i class="bi bi-folder2-open"></i> Browse Files
                </button>
            `;
        }

        // Form validation
        document.getElementById('uploadForm').addEventListener('submit', function(e) {
            const selectedTable = tableName.value;
            const newTable = newTableName.value.trim();
            
            if (selectedTable === '' && newTable === '') {
                e.preventDefault();
                alert('❌ Please enter a table name!');
                newTableName.focus();
                return false;
            }
            
            if (!fileInput.files.length) {
                e.preventDefault();
                alert('❌ Please select a file to upload!');
                return false;
            }
            
            // Show loading state
            uploadBtn.disabled = true;
            uploadBtn.innerHTML = `
                <span class="spinner"></span>
                Uploading...
            `;
        });

        // Prevent upload box click when clicking remove button
        uploadBox.addEventListener('click', function(e) {
            if (e.target.closest('.btn-remove')) {
                e.stopPropagation();
            }
        });
    </script>
</body>
</html>