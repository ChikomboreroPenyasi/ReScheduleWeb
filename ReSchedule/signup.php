<?php
session_start();

// If already logged in, redirect to dashboard
if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit();
}

// Database Configuration
$db_host = 'localhost';
$db_name = 'reschedule_db';
$db_user = 'root';
$db_pass = '';

$message = "";
$statusClass = "";
$fullname = "";

try {
    $pdo = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8mb4", $db_user, $db_pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);

    // Fetch all programmes for the student dropdown
    $programmes = $pdo->query("SELECT id, program_code, program_name FROM programmes ORDER BY program_code ASC")->fetchAll();

    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $fullname   = trim($_POST['fullname'] ?? '');
        $password   = trim($_POST['password'] ?? '');
        $role       = $_POST['role'] ?? 'Student';
        $program_id = !empty($_POST['program_id']) ? intval($_POST['program_id']) : null;

        if (empty($fullname) || empty($password)) {
            $message = "Please fill in all required fields.";
            $statusClass = "error";
        } elseif ($role === 'Student' && empty($program_id)) {
            $message = "Students must select an enrolled programme.";
            $statusClass = "error";
        } else {
            // Check for existing user by Full Name
            $check = $pdo->prepare("SELECT id FROM users WHERE fullname = :fullname");
            $check->execute([':fullname' => $fullname]);

            if ($check->fetch()) {
                $message = "An account with this Full Name already exists.";
                $statusClass = "error";
            } else {
                $password_hash = password_hash($password, PASSWORD_DEFAULT);

                // FIXED: Changed column 'password' to 'password_hash'
                $stmt = $pdo->prepare("
                    INSERT INTO users (fullname, password_hash, role, program_id) 
                    VALUES (:fullname, :password_hash, :role, :program_id)
                ");
                $stmt->execute([
                    ':fullname'      => $fullname,
                    ':password_hash' => $password_hash,
                    ':role'          => $role,
                    ':program_id'    => ($role === 'Student') ? $program_id : null
                ]);

                $message = "Account created successfully! <a href='login.php'>Click here to Log In</a>.";
                $statusClass = "success";
            }
        }
    }

} catch (PDOException $e) {
    $message = "Database connection error: " . $e->getMessage();
    $statusClass = "error";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reschedule - Sign Up</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body class="form-body">

    <div class="form-wrapper">
        <div class="form-header">
            <div class="brand-logo">Reschedule<span>.</span></div>
            <h2>Create Account</h2>
            <p>Join the Reschedule Timetable System</p>
        </div>

        <?php if (!empty($message)): ?>
            <div class="alert-box <?php echo $statusClass; ?>">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <form action="signup.php" method="POST" id="signupForm">
            <div class="form-group">
                <label for="fullname">Full Name</label>
                <input type="text" id="fullname" name="fullname" value="<?php echo htmlspecialchars($fullname); ?>" placeholder="e.g. John Doe" required>
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" placeholder="••••••••" required>
            </div>

            <div class="form-group">
                <label for="role">Account Role</label>
                <select name="role" id="role">
                    <option value="Student">Student</option>
                    <option value="Lecturer">Lecturer</option>
                    <option value="Administrator">Administrator</option>
                </select>
            </div>

            <div class="form-group">
                <label for="program_id">Enrolled Programme (Required for Students)</label>
                <select name="program_id" id="program_id">
                    <option value="">-- Select Your Programme --</option>
                    <?php foreach ($programmes as $p): ?>
                        <option value="<?php echo $p['id']; ?>">
                            [<?php echo htmlspecialchars($p['program_code']); ?>] <?php echo htmlspecialchars($p['program_name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <button type="submit" class="btn-submit">Sign Up</button>
        </form>

        <div class="form-footer">
            <p>Already have an account? <a href="login.php">Log In</a></p>
            <p><a href="index.php">&larr; Return to Landing Page</a></p>
        </div>
    </div>

    <script src="js/main.js"></script>
</body>
</html>