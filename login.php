<?php
session_start();

// Import shared database setup (Supabase PostgreSQL connection)
require_once 'db.php';

// If already logged in, redirect to dashboard
if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit();
}

$message = "";
$statusClass = "";
$fullname = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $fullname = trim($_POST['fullname'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (empty($fullname) || empty($password)) {
        $message = "Please enter both Full Name and Password.";
        $statusClass = "error";
    } else {
        try {
            // Query user using shared $pdo from db.php
            $stmt = $pdo->prepare("SELECT * FROM users WHERE fullname = :fullname LIMIT 1");
            $stmt->execute([':fullname' => $fullname]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password_hash'])) {
                // Set session variables
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['fullname'] = $user['fullname'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['program_id'] = $user['program_id'];

                header("Location: dashboard.php");
                exit();
            } else {
                $message = "Invalid Full Name or Password.";
                $statusClass = "error";
            }
        } catch (PDOException $e) {
            $message = "Database connection error: " . $e->getMessage();
            $statusClass = "error";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reschedule - Log In</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body class="form-body">

    <div class="form-wrapper">
        <div class="form-header">
            <div class="brand-logo">Reschedule<span>.</span></div>
            <h2>Welcome Back</h2>
            <p>Log in to access your timetable</p>
        </div>

        <?php if (!empty($message)): ?>
            <div class="alert-box <?php echo $statusClass; ?>">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <form action="login.php" method="POST" id="loginForm">
            <div class="form-group">
                <label for="fullname">Full Name</label>
                <input type="text" id="fullname" name="fullname" value="<?php echo htmlspecialchars($fullname); ?>" placeholder="e.g. John Doe" required>
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" placeholder="••••••••" required>
            </div>

            <button type="submit" class="btn-submit">Log In</button>
        </form>

        <div class="form-footer">
            <p>Don't have an account? <a href="signup.php">Sign Up</a></p>
            <p><a href="index.php">&larr; Return to Landing Page</a></p>
        </div>
    </div>

    <script src="js/main.js"></script>
</body>
</html>
