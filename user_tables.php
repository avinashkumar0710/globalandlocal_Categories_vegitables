<?php
require "config/database.php";

$db = new Database();
$pdo = $db->getConnection();

// Get all tables
$stmt = $pdo->query("SHOW TABLES");
$tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
?>
