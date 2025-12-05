<?php
// paste_data.php - Copy/Paste data directly from Excel
require_once 'config/database.php';
require_once 'includes/functions.php';

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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Get table name from either dropdown or text input
        $tableName = !empty($_POST['new_table_name']) ? 
                     sanitizeTableName($_POST['new_table_name']) : 
                     sanitizeTableName($_POST['table_name']);
        
        $pastedData = $_POST['pasted_data'] ?? '';
        
        // If new table name is provided, force create new table
        $createNewTable = !empty($_POST['new_table_name']) || isset($_POST['create_new_table']);
        
        $hasHeaders = isset($_POST['has_headers']);
        
        if (empty($tableName)) {
            throw new Exception('Table name is required');
        }
        
        if (empty($pastedData)) {
            throw new Exception('No data pasted');
        }
        
        // Parse pasted data (supports tab-separated and comma-separated)
        $rows = explode("\n", trim($pastedData));
        $data = [];
        
        foreach ($rows as $row) {
            $row = trim($row);
            if (empty($row)) continue;
            
            // Try tab-separated first (from Excel), then comma-separated
            if (strpos($row, "\t") !== false) {
                $cells = explode("\t", $row);
            } else {
                $cells = str_getcsv($row);
            }
            
            $data[] = array_map('trim', $cells);
        }
        
        if (empty($data)) {
            throw new Exception('No valid data found');
        }
        
        // Extract headers
        $headers = [];
        if ($hasHeaders) {
            $headers = array_shift($data);
        } else {
            // Generate column names (col1, col2, col3...)
            $numCols = count($data[0]);
            for ($i = 0; $i < $numCols; $i++) {
                $headers[] = 'col' . ($i + 1);
            }
        }
        
        // Sanitize headers
        $headers = array_map(function($header) {
            return sanitizeColumnName($header);
        }, $headers);
        
        if (empty($headers)) {
            throw new Exception('No valid column headers');
        }
        
        if ($createNewTable) {
            // Create new table
            $columns = [];
            $columns[] = "id INT PRIMARY KEY AUTO_INCREMENT";
            
            foreach ($headers as $index => $header) {
                $columnType = detectColumnTypeFromData($data, $index);
                $columns[] = "`$header` $columnType";
            }
            
            $sql = "CREATE TABLE IF NOT EXISTS `$tableName` (" . implode(', ', $columns) . ")";
            $conn->exec($sql);
            $message = "✅ Table '$tableName' created successfully. ";
        } else {
            // Check if table exists
            if (!in_array($tableName, $tables)) {
                throw new Exception("Table '$tableName' does not exist. Check 'Create new table' option to create it.");
            }
        }
        
        // Insert data
        $inserted = 0;
        $failed = 0;
        $placeholders = implode(', ', array_fill(0, count($headers), '?'));
        $columnList = '`' . implode('`, `', $headers) . '`';
        
        $insertSql = "INSERT INTO `$tableName` ($columnList) VALUES ($placeholders)";
        $stmt = $conn->prepare($insertSql);
        
        foreach ($data as $row) {
            // Adjust row length to match headers
            $rowData = array_slice($row, 0, count($headers));
            
            // Pad with empty strings if needed
            while (count($rowData) < count($headers)) {
                $rowData[] = '';
            }
            
            // Skip completely empty rows
            if (empty(array_filter($rowData, function($val) { return $val !== ''; }))) {
                continue;
            }
            
            try {
                $stmt->execute($rowData);
                $inserted++;
            } catch (Exception $e) {
                $failed++;
                continue;
            }
        }
        
        $message .= "✅ $inserted rows inserted successfully!";
        if ($failed > 0) {
            $message .= " ⚠️ ($failed rows failed)";
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

function detectColumnTypeFromData($data, $columnIndex) {
    $sample = array_slice($data, 0, min(50, count($data)));
    $isNumeric = true;
    $isDate = true;
    $maxLength = 0;
    
    foreach ($sample as $row) {
        if (!isset($row[$columnIndex])) continue;
        
        $value = trim($row[$columnIndex]);
        if (empty($value)) continue;
        
        $maxLength = max($maxLength, strlen($value));
        
        if (!is_numeric($value)) {
            $isNumeric = false;
        }
        
        if (!strtotime($value)) {
            $isDate = false;
        }
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
    <title>Paste Data - Database Manager</title>
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
            background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
        }
        
        /* Navigation */
        .navbar {
            background: linear-gradient(135deg, #2ecb83 0%, #2e83cb 100%);
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
            font-size: 13px;
            font-weight: 600;
        }
        
        .navbar-brand:hover {
            color: #fef3c7;
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
            background: linear-gradient(135deg, #06b6d4 0%, #0891b2 100%);
            color: white;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 16px;
            box-shadow: 0 4px 6px rgba(6, 182, 212, 0.2);
        }
        
        .section-header h3 {
            font-size: 13px;
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
        
        /* How to Use Box */
        .how-to-box {
            background: linear-gradient(135deg, #e0f2fe 0%, #bae6fd 100%);
            border: 1px solid #0ea5e9;
            border-radius: 8px;
            padding: 12px;
            margin-bottom: 16px;
        }
        
        .how-to-box h4 {
            font-size: 13px;
            font-weight: 700;
            color: #075985;
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        
        .how-to-box ol {
            margin: 0;
            padding-left: 16px;
        }
        
        .how-to-box li {
            font-size: 13px;
            color: #0c4a6e;
            margin-bottom: 4px;
        }
        
        /* Sample Data Box */
        .sample-box {
            background: #f0fdf4;
            border: 1px solid #22c55e;
            border-radius: 8px;
            padding: 12px;
            margin-bottom: 16px;
        }
        
        .sample-box h4 {
            font-size: 10px;
            font-weight: 700;
            color: #166534;
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        
        .sample-data-code {
            background: white;
            border: 1px solid #bbf7d0;
            border-radius: 6px;
            padding: 8px;
            font-family: 'Courier New', monospace;
            font-size: 13px;
            color: #166534;
            margin-bottom: 8px;
            white-space: pre;
            overflow-x: auto;
        }
        
        .btn-load-sample {
            background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%);
            color: white;
            border: none;
            padding: 6px 12px;
            border-radius: 6px;
            font-size: 9px;
            font-weight: 600;
            cursor: pointer;
            width: 100%;
            transition: all 0.2s;
        }
        
        .btn-load-sample:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 8px rgba(34, 197, 94, 0.3);
        }
        
        /* Tips Box */
        .tips-box {
            background: #fef3c7;
            border: 1px solid #fbbf24;
            border-radius: 8px;
            padding: 12px;
            margin-bottom: 16px;
        }
        
        .tips-box h4 {
            font-size: 13px;
            font-weight: 700;
            color: #92400e;
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        
        .tips-box ul {
            list-style: none;
            padding-left: 0;
            margin: 0;
        }
        
        .tips-box li {
            font-size: 12px;
            color: #78350f;
            margin-bottom: 4px;
            padding-left: 12px;
            position: relative;
        }
        
        .tips-box li:before {
            content: "•";
            position: absolute;
            left: 0;
            font-weight: 700;
        }
        
        /* Tables Section Header */
        .tables-header {
            background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%);
            color: white;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 12px;
            box-shadow: 0 4px 6px rgba(139, 92, 246, 0.2);
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
            border-color: #8b5cf6;
            box-shadow: 0 4px 12px rgba(139, 92, 246, 0.15);
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
            font-size: 13px;
            color: #64748b;
        }
        
        /* Right Main Area - 70% */
        .main-content {
            flex: 1;
            overflow-y: auto;
            padding: 24px;
            background: linear-gradient(135deg, #fef9e7 0%, #fef3c7 100%);
        }
        
        .main-content::-webkit-scrollbar {
            width: 8px;
        }
        
        .main-content::-webkit-scrollbar-track {
            background: #fef9e7;
        }
        
        .main-content::-webkit-scrollbar-thumb {
            background: #fbbf24;
            border-radius: 4px;
        }
        
        .content-wrapper {
            max-width: 1000px;
            margin: 0 auto;
        }
        
        /* Main Header */
        .main-header {
            background: linear-gradient(135deg, #2ecb83 0%, #2e83cb 100%);
            color: white;
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 20px;
            box-shadow: 0 8px 16px rgba(245, 158, 11, 0.3);
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
            border-color: #f59e0b;
            box-shadow: 0 0 0 3px rgba(245, 158, 11, 0.1);
        }
        
        .form-text {
            font-size: 13px;
            color: #64748b;
            margin-top: 6px;
        }
        
        /* Textarea */
        #pastedData {
            font-family: 'Courier New', monospace;
            font-size: 13px;
            min-height: 200px;
            resize: vertical;
        }
        
        /* Checkbox */
        .checkbox-container {
            background: #fef3c7;
            border: 1px solid #f59e0b;
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
            color: #92400e;
            margin: 0;
            cursor: pointer;
        }
        
        .checkbox-simple {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 12px;
        }
        
        .checkbox-simple input[type="checkbox"] {
            width: 16px;
            height: 16px;
            cursor: pointer;
        }
        
        .checkbox-simple label {
            font-size: 13px;
            font-weight: 600;
            color: #334155;
            margin: 0;
            cursor: pointer;
        }
        
        /* Buttons */
        .btn-preview {
            width: 100%;
            background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
            color: white;
            padding: 12px;
            border: none;
            border-radius: 10px;
            font-size: 13px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.2s;
            box-shadow: 0 4px 8px rgba(59, 130, 246, 0.3);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            margin-bottom: 12px;
        }
        
        .btn-preview:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 12px rgba(59, 130, 246, 0.4);
        }
        
        .btn-import {
            width: 100%;
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
            padding: 14px;
            border: none;
            border-radius: 10px;
            font-size: 13px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.2s;
            box-shadow: 0 6px 12px rgba(16, 185, 129, 0.3);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }
        
        .btn-import:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 16px rgba(16, 185, 129, 0.4);
        }
        
        .btn-import:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }
        
        /* Preview Section */
        .preview-section {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
            padding: 20px;
            margin-bottom: 16px;
        }
        
        .preview-header {
            background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%);
            color: white;
            padding: 12px 16px;
            border-radius: 8px;
            margin: -20px -20px 16px -20px;
            font-size: 13px;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .preview-placeholder {
            text-align: center;
            padding: 60px 20px;
            color: #94a3b8;
        }
        
        .preview-placeholder i {
            font-size: 48px;
            display: block;
            margin-bottom: 16px;
            opacity: 0.5;
        }
        
        .preview-placeholder p {
            font-size: 13px;
            margin: 0;
        }
        
        .preview-table-container {
            max-height: 400px;
            overflow: auto;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
        }
        
        .preview-table-container::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }
        
        .preview-table-container::-webkit-scrollbar-track {
            background: #f1f5f9;
        }
        
        .preview-table-container::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 4px;
        }
        
        .preview-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 13px;
        }
        
        .preview-table thead th {
            background: #1e293b;
            color: white;
            padding: 8px;
            text-align: left;
            font-weight: 600;
            position: sticky;
            top: 0;
            z-index: 10;
        }
        
        .preview-table tbody td {
            padding: 6px 8px;
            border-bottom: 1px solid #e2e8f0;
        }
        
        .preview-table tbody tr:hover {
            background: #f8fafc;
        }
        
        .preview-info {
            background: #dbeafe;
            border: 1px solid #3b82f6;
            border-radius: 8px;
            padding: 10px 12px;
            margin-top: 12px;
            font-size: 13px;
            color: #1e3a8a;
            font-weight: 600;
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
            <span style="opacity: 0.7;">/ Paste Data</span>
        </a>
        <div class="navbar-user">
            Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?>
        </div>
    </div>

    <!-- Main Container -->
    <div class="main-container">
        <!-- Left Sidebar - 30% -->
        <div class="sidebar">
            <!-- How to Use Section -->
            <!-- <div class="section-header">
                <h3>
                    <i class="bi bi-info-circle"></i>
                    How to Use
                </h3>
                <p>Quick & Easy Data Import</p>
            </div> -->

            <!-- <div class="how-to-box">
                <h4>
                    <i class="bi bi-list-ol"></i>
                    Steps
                </h4>
                <ol>
                    <li>Select cells in Excel/Google Sheets</li>
                    <li>Copy (Ctrl+C or Cmd+C)</li>
                    <li>Paste below (Ctrl+V or Cmd+V)</li>
                    <li>Click "Preview Data" to check</li>
                    <li>Click "Import Data" to save</li>
                </ol>
            </div> -->

            <!-- Sample Data Section -->
            <!-- <div class="sample-box">
                <h4>
                    <i class="bi bi-clipboard-check"></i>
                    Try Sample Data
                </h4>
                <div class="sample-data-code">name	email	phone	city	age
John Doe	john@email.com	1234567890	New York	25
Jane Smith	jane@email.com	0987654321	London	30
Mike Johnson	mike@email.com	5551234567	Paris	28</div>
                <button type="button" class="btn-load-sample" onclick="loadSampleData()">
                    <i class="bi bi-clipboard-plus"></i> Load Sample Data
                </button>
            </div> -->

            <!-- Tips Section -->
            <div class="tips-box">
                <h4>
                    <i class="bi bi-lightbulb"></i>
                    Tips & Tricks
                </h4>
                <ul>
                    <li><strong>Excel/Google Sheets:</strong> Select → Copy → Paste</li>
                    <li><strong>Tab-separated:</strong> Automatically detected</li>
                    <li><strong>Comma-separated:</strong> Also supported (CSV)</li>
                    <li><strong>Headers:</strong> First row = column names</li>
                    <li><strong>Empty rows:</strong> Skipped automatically</li>
                    <li><strong>Data types:</strong> Auto-detected (INT, TEXT, etc.)</li>
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
                            <i class="bi bi-table" style="color: #8b5cf6; font-size: 13px;"></i>
                            <span class="table-card-name"><?php echo htmlspecialchars($table['name']); ?></span>
                        </div>
                        <div class="table-card-meta">
                            <span>📅 <?php echo htmlspecialchars($table['created']); ?></span>
                            <span>📊 <?php echo number_format($table['rows']); ?> rows</span>
                        </div>
                    </a>
                <?php endforeach; ?>
            <?php else: ?>
                <div style="text-align: center; padding: 20px; color: #64748b; font-size: 13px;">
                    <i class="bi bi-inbox" style="font-size: 24px; display: block; margin-bottom: 8px;"></i>
                    No tables yet. Create your first table by pasting data!
                </div>
            <?php endif; ?>
        </div>

        <!-- Right Main Content - 70% -->
        <div class="main-content">
            <div class="content-wrapper">
                <!-- Main Header -->
                <div class="main-header">
                    <div class="main-header-icon">
                        <i class="bi bi-clipboard-data" style="font-size: 15px;"></i>
                    </div>
                    <div>
                        <h2>Paste Data from Excel</h2>
                        <p>Copy from Excel/Sheets and paste directly</p>
                    </div>
                </div>

                <!-- Alert Messages -->
                <?php if ($message): ?>
                    <div class="alert alert-<?php echo $messageType; ?>">
                        <span><?php echo $message; ?></span>
                        <button type="button" class="alert-close" onclick="this.parentElement.remove()">×</button>
                    </div>
                <?php endif; ?>

                <!-- Paste Form -->
                <form method="POST" id="pasteForm">
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

                        <!-- Has Headers Checkbox -->
                        <div class="checkbox-simple">
                            <input 
                                type="checkbox" 
                                name="has_headers" 
                                id="hasHeaders" 
                                checked
                            >
                            <label for="hasHeaders">
                                First row contains column names
                            </label>
                        </div>
                    </div>

                    <!-- Paste Data Section -->
                    <div class="form-section">
                        <label class="form-label">
                            Paste Your Data Here <span class="required">*</span>
                        </label>
                        <textarea 
                            class="form-control" 
                            name="pasted_data" 
                            id="pastedData" 
                            placeholder="Paste data from Excel here (Ctrl+V)...

Example:
name	email	age
John	john@email.com	25
Jane	jane@email.com	30" 
                            required
                        ></textarea>
                        <div class="form-text">
                            Supports tab-separated (from Excel) or comma-separated values (CSV)
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <button type="button" class="btn-preview" onclick="previewData()">
                        <i class="bi bi-eye"></i>
                        Preview Data
                    </button>
                    
                    <button type="submit" class="btn-import" id="importBtn">
                        <i class="bi bi-upload"></i>
                        Import Data to Database
                    </button>
                </form>

                <!-- Preview Section -->
                <div class="preview-section">
                    <div class="preview-header">
                        <i class="bi bi-eye"></i>
                        Data Preview
                    </div>
                    <div id="previewContainer">
                        <div class="preview-placeholder">
                            <i class="bi bi-table"></i>
                            <p>Paste data and click "Preview Data" to see how it will look</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script>
        // DOM Elements
        const tableName = document.getElementById('tableName');
        const newTableNameDiv = document.getElementById('newTableNameDiv');
        const newTableName = document.getElementById('newTableName');
        const createNewTable = document.getElementById('create_new_table');
        const pastedData = document.getElementById('pastedData');
        const hasHeaders = document.getElementById('hasHeaders');
        const importBtn = document.getElementById('importBtn');

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

        // Form validation
        document.getElementById('pasteForm').addEventListener('submit', function(e) {
            const selectedTable = tableName.value;
            const newTable = newTableName.value.trim();
            
            if (selectedTable === '' && newTable === '') {
                e.preventDefault();
                alert('❌ Please enter a table name!');
                newTableName.focus();
                return false;
            }
            
            const data = pastedData.value.trim();
            if (!data) {
                e.preventDefault();
                alert('❌ Please paste some data!');
                pastedData.focus();
                return false;
            }

            // Show loading state
            importBtn.disabled = true;
            importBtn.innerHTML = `
                <span class="spinner"></span>
                Importing...
            `;
        });

        // Preview Data Function
        function previewData() {
            const data = pastedData.value.trim();
            const hasHeadersChecked = hasHeaders.checked;
            
            if (!data) {
                alert('❌ Please paste some data first!');
                pastedData.focus();
                return;
            }
            
            // Parse data
            const rows = data.split('\n').filter(row => row.trim());
            const tableData = rows.map(row => {
                if (row.indexOf('\t') !== -1) {
                    // Tab-separated (from Excel)
                    return row.split('\t').map(cell => cell.trim());
                } else {
                    // Comma-separated (CSV)
                    return row.split(',').map(cell => cell.trim());
                }
            });
            
            if (tableData.length === 0) {
                alert('❌ No valid data found!');
                return;
            }
            
            // Build preview table
            let html = '<div class="preview-table-container"><table class="preview-table">';
            
            // Headers
            if (hasHeadersChecked && tableData.length > 0) {
                html += '<thead><tr>';
                tableData[0].forEach(header => {
                    html += `<th>${escapeHtml(header)}</th>`;
                });
                html += '</tr></thead><tbody>';
                
                // Data rows (skip first row as it's header)
                const dataRows = tableData.slice(1, Math.min(tableData.length, 11));
                dataRows.forEach(row => {
                    html += '<tr>';
                    row.forEach(cell => {
                        html += `<td>${escapeHtml(cell)}</td>`;
                    });
                    html += '</tr>';
                });
            } else {
                // No headers - generate column names
                html += '<thead><tr>';
                tableData[0].forEach((_, index) => {
                    html += `<th>Column ${index + 1}</th>`;
                });
                html += '</tr></thead><tbody>';
                
                // All rows are data (show first 10)
                const dataRows = tableData.slice(0, Math.min(tableData.length, 10));
                dataRows.forEach(row => {
                    html += '<tr>';
                    row.forEach(cell => {
                        html += `<td>${escapeHtml(cell)}</td>`;
                    });
                    html += '</tr>';
                });
            }
            
            html += '</tbody></table></div>';
            
            // Add info
            const totalRows = hasHeadersChecked ? tableData.length - 1 : tableData.length;
            const numColumns = tableData[0].length;
            const showing = Math.min(hasHeadersChecked ? tableData.length - 1 : tableData.length, 10);
            
            html += `<div class="preview-info">
                <i class="bi bi-check-circle"></i>
                <strong>Preview:</strong> ${totalRows} rows, ${numColumns} columns
                ${totalRows > 10 ? ` (showing first ${showing} rows)` : ''}
            </div>`;
            
            document.getElementById('previewContainer').innerHTML = html;
        }

        // Escape HTML to prevent XSS
        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        // Load Sample Data
        function loadSampleData() {
            const sample = `name	email	phone	city	age
John Doe	john@email.com	1234567890	New York	25
Jane Smith	jane@email.com	0987654321	London	30
Mike Johnson	mike@email.com	5551234567	Paris	28
Sarah Wilson	sarah@email.com	5559876543	Tokyo	27`;
            
            pastedData.value = sample;
            hasHeaders.checked = true;
            
            // Auto preview
            setTimeout(() => {
                previewData();
                alert('✅ Sample data loaded! You can now preview or import it.');
            }, 100);
        }

        // Auto-preview when data is pasted
        pastedData.addEventListener('paste', function() {
            setTimeout(previewData, 200);
        });

        // Auto-preview when data changes (if enough data)
        pastedData.addEventListener('input', function() {
            const data = pastedData.value.trim();
            if (data.split('\n').length >= 2) {
                clearTimeout(window.previewTimeout);
                window.previewTimeout = setTimeout(previewData, 500);
            }
        });

        // Keyboard shortcuts
        document.addEventListener('keydown', function(e) {
            // Ctrl/Cmd + Enter to preview
            if ((e.ctrlKey || e.metaKey) && e.key === 'Enter') {
                e.preventDefault();
                if (document.activeElement === pastedData) {
                    previewData();
                }
            }
        });

        // Focus on paste area on load
        window.addEventListener('load', function() {
            pastedData.focus();
        });
    </script>
</body>
</html>