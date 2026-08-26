<?php
session_start();
require_once 'db.php';

$error = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $login_input = trim($_POST['username_or_student_number'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (empty($login_input) || empty($password)) {
        $error = "Please fill in all fields.";
    } else {
        try {
            $stmt = $pdo->prepare("
                SELECT * FROM users 
                WHERE username = :input OR student_number = :input 
                LIMIT 1
            ");
            $stmt->execute([':input' => $login_input]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password_hash'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['fullname'] = $user['fullname'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['program_id'] = $user['program_id'];
                $_SESSION['student_number'] = $user['student_number'];

                header("Location: dashboard.php");
                exit();
            } else {
                $error = "Invalid credentials. Check username/student number and password.";
            }
        } catch (PDOException $e) {
            $error = "Database error: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reschedule - Login</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        body.auth-page {
            background-color: #f8fafc;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0;
            font-family: system-ui, -apple-system, sans-serif;
        }
        .auth-container {
            width: 100%;
            max-width: 420px;
            padding: 1rem;
        }
        .auth-card {
            background: #ffffff;
            border-radius: 12px;
            padding: 2.5rem;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.05), 0 8px 10px -6px rgba(0, 0, 0, 0.05);
            border: 1px solid #e2e8f0;
        }
        .auth-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        .auth-header h2 {
            margin: 0 0 0.5rem 0;
            font-size: 1.75rem;
            color: #0f172a;
        }
        .auth-header p {
            margin: 0;
            color: #64748b;
            font-size: 0.9rem;
        }
        .form-group {
            margin-bottom: 1.25rem;
        }
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-size: 0.875rem;
            font-weight: 600;
            color: #334155;
        }
        .form-group input {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            font-size: 0.95rem;
            box-sizing: border-box;
            transition: border-color 0.2s;
        }
        .form-group input:focus {
            outline: none;
            border-color: #2563eb;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
        }
        .btn-primary {
            width: 100%;
            padding: 0.75rem;
            background-color: #0284c7;
            color: white;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            font-size: 1rem;
            cursor: pointer;
            transition: background-color 0.2s;
        }
        .btn-primary:hover {
            background-color: #0369a1;
        }
        .auth-footer {
            margin-top: 1.5rem;
            text-align: center;
            font-size: 0.875rem;
            color: #64748b;
        }
        .auth-footer a {
            color: #2563eb;
            text-decoration: none;
            font-weight: 600;
        }
    </style>
</head>
<body class="auth-page">
    <div class="auth-container">
        <div class="auth-card">
            <div class="auth-header">
                <h2>Reschedule Login</h2>
                <p>Access your timetable and course schedules</p>
            </div>

            <?php if (!empty($error)): ?>
                <div style="background:#fee2e2; color:#991b1b; padding:0.75rem; border-radius:6px; margin-bottom:1.25rem; font-size:0.85rem; border: 1px solid #fca5a5;">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <form action="login.php" method="POST">
                <div class="form-group">
                    <label for="username_or_student_number">Username or Student Number</label>
                    <input type="text" id="username_or_student_number" name="username_or_student_number" placeholder="Enter username or student ID" required>
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" placeholder="Enter password" required>
                </div>

                <button type="submit" class="btn-primary">Sign In</button>
            </form>

            <div class="auth-footer">
                Don't have an account? <a href="signup.php">Create Account</a>
            </div>
        </div>
    </div>
</body>
</html>
