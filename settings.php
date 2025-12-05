<?php
session_start();
require "config/database.php";

// Create DB connection
$db = new Database();
$pdo = $db->getConnection();

$message = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new_user = $_POST['new_username'];
    $new_pass = $_POST['new_password']; // NO HASH

    $stmt = $pdo->prepare("UPDATE admin_login SET username = ?, password = ? WHERE username = ?");
    $stmt->execute([$new_user, $new_pass, $_SESSION['username']]);

    $_SESSION['username'] = $new_user;
    $message = "Login credentials updated successfully!";
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>Settings - Change Credentials</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.11.1/font/bootstrap-icons.min.css" rel="stylesheet">

    <style>
        body {
            background: linear-gradient(135deg, #1e3c72, #2a5298);
            min-height: 100vh;
        }
        .settings-card {
            border-radius: 15px;
            background: #ffffff;
            padding: 25px;
            font-size: 14px;
        }
        .btn-custom {
            font-size: 14px;
        }
        .top-buttons a {
            font-size: 13px;
        }
    </style>
</head>
<body>

<div class="container d-flex justify-content-center align-items-center" style="min-height:100vh;">
    <div class="settings-card shadow" style="width: 430px;">

        <!-- Top Buttons -->
        <div class="d-flex justify-content-between mb-3 top-buttons">
            <a href="index.php" class="btn btn-outline-primary btn-sm">
                <i class="bi bi-arrow-left-circle"></i> Home
            </a>
            <a href="logout.php" class="btn btn-outline-danger btn-sm">
                <i class="bi bi-box-arrow-right"></i> Logout
            </a>
        </div>

        <h4 class="text-center mb-3">
            <i class="bi bi-gear-fill"></i> Update Login Credentials
        </h4>

        <?php if ($message): ?>
            <div class="alert alert-success p-2 text-center"><?= $message ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="mb-3">
                <label class="form-label fw-semibold">New Username</label>
                <input type="text" name="new_username" class="form-control form-control-sm" required
                       value="<?= $_SESSION['username'] ?>">
            </div>

            <div class="mb-3">
                <label class="form-label fw-semibold">New Password</label>
                <input type="password" name="new_password" class="form-control form-control-sm" required>
            </div>

            <button class="btn btn-primary w-100 btn-custom mt-2">
                <i class="bi bi-check-circle"></i> Update
            </button>

            <a href="index.php" class="btn btn-secondary w-100 btn-custom mt-2">
                <i class="bi bi-x-circle"></i> Cancel
            </a>
        </form>
    </div>
</div>

</body>
</html>
