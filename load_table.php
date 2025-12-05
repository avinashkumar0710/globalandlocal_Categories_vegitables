<?php
require "config/database.php";

// In load_table.php, add:
if (isset($_GET['format']) && $_GET['format'] === 'json') {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'headers' => $headers, // array of column names
        'data' => $data // array of associative arrays
    ]);
    exit;
}

if (!isset($_GET['table'])) {
    http_response_code(400);
    echo "<div class='alert alert-danger' style='margin:20px;'>No table selected</div>";
    exit;
}

$table = $_GET['table'];
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// Basic validation: only allow letters, numbers and underscore
if (!preg_match('/^[A-Za-z0-9_]+$/', $table)) {
    http_response_code(400);
    echo "<div class='alert alert-danger' style='margin:20px;'>Invalid table name</div>";
    exit;
}

// Sensitive protection
$sensitiveTables = ['activity_log', 'admin_login'];
if (in_array($table, $sensitiveTables)) {
    http_response_code(403);
    echo "<div class='alert alert-danger' style='margin:20px;'>Access denied: This table is restricted</div>";
    exit;
}

$db = new Database();
$pdo = $db->getConnection();

// Ensure table exists in the database (prevent arbitrary injections)
$checkStmt = $pdo->prepare("SHOW TABLES LIKE ?");
$checkStmt->execute([$table]);
if ($checkStmt->fetchColumn() === false) {
    http_response_code(404);
    echo "<div class='alert alert-warning' style='margin:20px;'>Table not found</div>";
    exit;
}

// Get column names
$colsStmt = $pdo->query("SHOW COLUMNS FROM `$table`");
$columns = $colsStmt->fetchAll(PDO::FETCH_COLUMN);
if (!$columns) {
    echo "<div class='dashboard-empty'><i class='bi bi-exclamation-circle'></i><h3>No data available</h3><p>This table appears to have no columns.</p></div>";
    exit;
}

// Build query
$sql = "SELECT * FROM `$table`";
$params = [];

if ($search !== '') {
    // Build WHERE using LIKE for each column (use placeholders)
    $where = [];
    foreach ($columns as $col) {
        $where[] = "`$col` LIKE ?";
        $params[] = "%{$search}%";
    }
    $sql .= " WHERE " . implode(" OR ", $where);
}

// Limit safety: if you expect big tables, you might add LIMIT/OFFSET here. For now returning all matched rows.
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Output: only table HTML (no search input) — index.php handles search input persistence
if (!$rows) {
    // Show the table header with columns but no rows (so user still sees the structure)
    echo "<div class='table-info-bar'>
            <div class='table-name'><i class='bi bi-table'></i> " . htmlspecialchars($table) . "</div>
            <div class='table-stats'>
                <span class='stat-badge'><i class='bi bi-list-ol'></i> 0 rows</span>
                <span class='stat-badge'><i class='bi bi-layout-three-columns'></i> " . count($columns) . " columns</span>
            </div>
          </div>";

    echo "<div class='excel-table-container'>";
    echo "<table><thead><tr>";
    foreach ($columns as $col) {
        echo "<th>" . htmlspecialchars($col) . "</th>";
    }
    echo "</tr></thead><tbody>";
    // no rows
    echo "</tbody></table>";
    echo "<div class='dashboard-empty' style='padding:20px;'><i class='bi bi-exclamation-circle'></i><h3>No records found</h3><p>Try a different search or clear the search box.</p></div>";
    echo "</div>";
    exit;
}

// Build table HTML with results
echo "<div class='table-info-bar'>
        <div class='table-name'><i class='bi bi-table'></i> " . htmlspecialchars($table) . "</div>
        <div class='table-stats'>
            <span class='stat-badge'><i class='bi bi-list-ol'></i> " . count($rows) . " rows</span>
            <span class='stat-badge'><i class='bi bi-layout-three-columns'></i> " . count($columns) . " columns</span>
        </div>
      </div>";

echo "<div class='excel-table-container'>";
echo "<table><thead><tr>";
foreach ($columns as $col) {
    echo "<th>" . htmlspecialchars($col) . "</th>";
}
echo "</tr></thead><tbody>";

foreach ($rows as $r) {
    echo "<tr>";
    foreach ($columns as $col) {
        $val = array_key_exists($col, $r) ? $r[$col] : '';
        echo "<td>" . htmlspecialchars((string)$val) . "</td>";
    }
    echo "</tr>";
}

echo "</tbody></table>";
echo "</div>";
