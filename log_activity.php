<?php
function logActivity($pdo, $username, $action, $details = "") {
    $ip = $_SERVER['REMOTE_ADDR'];

    $stmt = $pdo->prepare("
        INSERT INTO activity_log (username, action, details, ip_address)
        VALUES (?, ?, ?, ?)
    ");
    $stmt->execute([$username, $action, $details, $ip]);

    return $pdo->lastInsertId(); // return row id for logout update
}
?>

