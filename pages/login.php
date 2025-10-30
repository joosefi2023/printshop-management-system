<?php
// pages/login.php - Login Page

// Include session management to check if already logged in
require_once '../includes/session.php';

// Redirect if already logged in
if (isLoggedIn()) {
    header("Location: ../dashboard.php");
    exit;
}

$error_message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!empty($username) && !empty($password)) {
        // Include database connection
        require_once '../includes/db.php';

        try {
            // Prepare SQL statement to prevent SQL injection
            $stmt = $pdo->prepare("SELECT id, user_name, password, first_name, last_name, role, is_active FROM users WHERE user_name = :username");

            // Bind the parameter
            $stmt->bindParam(':username', $username, PDO::PARAM_STR);

            // Execute the statement
            $stmt->execute();

            // Fetch the user data
            $user = $stmt->fetch();

            // Verify user exists, is active, and password is correct
            if ($user && $user['is_active'] == 1 && password_verify($password, $user['password'])) {
                // Password is correct, start session
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['user_name'];
                $_SESSION['first_name'] = $user['first_name'];
                $_SESSION['last_name'] = $user['last_name'];
                $_SESSION['role'] = $user['role'];

                // Redirect to dashboard
                header("Location: ../dashboard.php");
                exit;
            } else {
                $error_message = 'نام کاربری یا رمز عبور نادرست است.';
            }
        } catch (PDOException $e) {
            // Log the error
            error_log("Login Error: " . $e->getMessage());
            $error_message = 'خطایی در پردازش درخواست رخ داد. لطفاً بعداً دوباره تلاش کنید.';
        }
    } else {
        $error_message = 'لطفاً نام کاربری و رمز عبور را وارد کنید.';
    }
}
?>

<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ورود به سیستم - چاپسون</title>
    <style>
        body {
            font-family: Tahoma, Arial, sans-serif; /* Use default theme font */
            background-color: #f0f2f5;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
        }
        .login-container {
            background-color: white;
            padding: 2rem;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            width: 100%;
            max-width: 400px;
        }
        h2 {
            text-align: center;
            margin-bottom: 1.5rem;
            color: #333;
        }
        .form-group {
            margin-bottom: 1rem;
        }
        label {
            display: block;
            margin-bottom: 0.5rem;
            color: #555;
        }
        input[type="text"],
        input[type="password"] {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #ccc;
            border-radius: 4px;
            box-sizing: border-box;
        }
        button {
            width: 100%;
            padding: 0.75rem;
            background-color: #007bff;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 1rem;
        }
        button:hover {
            background-color: #0056b3;
        }
        .error {
            color: red;
            text-align: center;
            margin-bottom: 1rem;
        }
    </style>
</head>
<body>

    <div class="login-container">
        <h2>ورود به سیستم</h2>

        <?php if (!empty($error_message)): ?>
            <div class="error"><?php echo $error_message; ?></div>
        <?php endif; ?>

        <form method="post" action="">
            <div class="form-group">
                <label for="username">نام کاربری:</label>
                <input type="text" id="username" name="username" required>
            </div>
            <div class="form-group">
                <label for="password">رمز عبور:</label>
                <input type="password" id="password" name="password" required>
            </div>
            <button type="submit">ورود</button>
        </form>
    </div>

</body>
</html>
