<?php
session_start();
require "config/database.php";

if (isset($_SESSION['log_id'])) {
    $db = new Database();
    $pdo = $db->getConnection();

    $stmt = $pdo->prepare("UPDATE activity_log SET logout_time = NOW(), action='LOGOUT' WHERE id = ?");
    $stmt->execute([$_SESSION['log_id']]);
}

session_unset();
session_destroy();

header("Location: login.php");
exit();
?>
