<?php
session_start();
require "config/database.php";
require "log_activity.php";

$error = "";

// Create DB connection
$db = new Database();
$pdo = $db->getConnection();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    // Fetch user from DB
    $stmt = $pdo->prepare("SELECT * FROM admin_login WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    // Validate credentials (plain text as requested)
    if ($user && $password === $user['password']) {
        // Store session
        $_SESSION['user_logged_in'] = true;
        $_SESSION['username'] = $username;
        $_SESSION['LAST_ACTIVITY'] = time(); // for auto logout

        // Log login activity
        $log = $pdo->prepare("
            INSERT INTO activity_log (username, action, details, ip_address, created_at)
            VALUES (?, 'LOGIN', 'User logged in successfully', ?, NOW())
        ");
        $log->execute([$username, $_SERVER['REMOTE_ADDR']]);

        // Store last log id for updating logout time later
        $_SESSION['log_id'] = $pdo->lastInsertId();

        header("Location: index.php");
        exit();
    } else {
        $error = "Invalid username or password!";
    }
}

// If already logged in then go to index
if (isset($_SESSION['user_logged_in']) && $_SESSION['user_logged_in'] === true) {
    header("Location: index.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Database Manager</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.9rem;
        }
        .login-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            padding: 40px;
            width: 100%;
            max-width: 420px;
            animation: fadeIn .6s ease-in-out;
        }
        @keyframes fadeIn {
            from { opacity:0; transform: scale(0.97); }
            to { opacity:1; transform: scale(1); }
        }
        .mini-options a {
            font-size: 0.8rem;
            text-decoration: none;
        }
        .mini-options a:hover {
            text-decoration: underline;
        }
    </style>
</head>

<body>
    <div class="login-card">
        <h3 class="text-center mb-3">Database Manager</h3>

        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="mb-2">
                <label class="form-label">Username</label>
                <input type="text" class="form-control form-control-sm" name="username" required autofocus>
            </div>

            <div class="mb-3">
                <label class="form-label">Password</label>
                <input type="password" class="form-control form-control-sm" name="password" required>
            </div>

            <button type="submit" class="btn btn-primary w-100 mb-2">Login</button>
        </form>

        <!-- <p class="text-muted text-center mt-3 mb-0" style="font-size: 0.8rem;">
            Default Login: admin / admin123
        </p> -->
    </div>
</body>
</html>