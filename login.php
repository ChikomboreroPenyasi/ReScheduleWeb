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
            // Check matching username OR student_number
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
                $error = "Invalid credentials. Please check your username/student number and password.";
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
</head>
<body class="auth-body">
    <div class="auth-card">
        <h2>Reschedule Login</h2>
        <p>Access your timetable and course schedules</p>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger" style="background:#fee2e2; color:#991b1b; padding:0.75rem; border-radius:6px; margin-bottom:1rem; font-size:0.85rem;">
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

            <button type="submit" class="btn-submit" style="width: 100%;">Sign In</button>
        </form>
    </div>
</body>
</html>
