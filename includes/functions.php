<?php
// includes/functions.php
session_start();

function isLoggedIn() {
    return isset($_SESSION['user_logged_in']) && $_SESSION['user_logged_in'] === true;
}

function requireLogin() {
    if (!isLoggedIn()) {
        header("Location: login.php");
        exit();
    }
}

function getAllTables($conn) {
    $stmt = $conn->query("SHOW TABLES");
    return $stmt->fetchAll(PDO::FETCH_COLUMN);
}

function getTableColumns($conn, $tableName) {
    $stmt = $conn->prepare("DESCRIBE `$tableName`");
    $stmt->execute();
    return $stmt->fetchAll();
}

function getTableData($conn, $tableName, $limit = 100) {
    $stmt = $conn->prepare("SELECT * FROM `$tableName` LIMIT :limit");
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll();
}

function sanitizeTableName($name) {
    return preg_replace('/[^a-zA-Z0-9_]/', '', $name);
}

function sanitizeColumnName($name) {
    return preg_replace('/[^a-zA-Z0-9_]/', '', $name);
}

function getPrimaryKey($conn, $tableName) {
    $stmt = $conn->prepare("SHOW KEYS FROM `$tableName` WHERE Key_name = 'PRIMARY'");
    $stmt->execute();
    $result = $stmt->fetch();
    return $result ? $result['Column_name'] : null;
}
?>