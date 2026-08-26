<?php
session_start();
require_once 'db.php';

$message = '';
$statusClass = '';
$programs = [];

// Fetch available programs for the dropdown menu
try {
    // Tries fetching from 'programs' or 'programmes'
    $programsStmt = $pdo->query("
        SELECT id, 
               COALESCE(
                   NULLIF(program_name, ''), 
                   NULLIF(name, ''), 
                   'Program ' || id
               ) AS program_name 
        FROM programs 
        ORDER BY id ASC
    ");
    $programs = $programsStmt->fetchAll();
} catch (PDOException $e) {
    // Fallback if table is named 'programmes' instead
    try {
        $programsStmt = $pdo->query("SELECT id, name AS program_name FROM programmes ORDER BY id ASC");
        $programs = $programsStmt->fetchAll();
    } catch (PDOException $ex) {
        $programs = [];
    }
}
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username'] ?? '');
    $student_number = trim($_POST['student_number'] ?? '');
    $fullname = trim($_POST['fullname'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $confirm_password = trim($_POST['confirm_password'] ?? '');
    $role = $_POST['role'] ?? 'Student';
    $program_id = !empty($_POST['program_id']) ? intval($_POST['program_id']) : null;

    if (empty($username) || empty($fullname) || empty($password)) {
        $message = "Please fill in all required fields.";
        $statusClass = "error";
    } elseif ($role === 'Student' && empty($student_number)) {
        $message = "Student Number is required for Student accounts.";
        $statusClass = "error";
    } elseif ($password !== $confirm_password) {
        $message = "Passwords do not match.";
        $statusClass = "error";
    } else {
        try {
            // Check if username already exists
            $checkStmt = $pdo->prepare("SELECT id FROM users WHERE username = :username OR (student_number = :student_number AND student_number IS NOT NULL AND student_number != '')");
            $checkStmt->execute([
                ':username' => $username,
                ':student_number' => $student_number
            ]);

            if ($checkStmt->fetch()) {
                $message = "Username or Student Number is already registered.";
                $statusClass = "error";
            } else {
                // Hash the password securely
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

                // Insert new user
                $insertStmt = $pdo->prepare("
                    INSERT INTO users (username, student_number, fullname, password_hash, role, program_id)
                    VALUES (:username, :student_number, :fullname, :password_hash, :role, :program_id)
                ");
                $insertStmt->execute([
                    ':username' => $username,
                    ':student_number' => !empty($student_number) ? $student_number : null,
                    ':fullname' => $fullname,
                    ':password_hash' => $hashedPassword,
                    ':role' => $role,
                    ':program_id' => $program_id
                ]);

                $message = "Registration successful! You can now log in.";
                $statusClass = "success";
            }
        } catch (PDOException $e) {
            $message = "Database Error: " . $e->getMessage();
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
    <title>Reschedule - Sign Up</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body class="auth-body">
    <div class="auth-card" style="max-width: 450px;">
        <h2>Create an Account</h2>
        <p>Join the Reschedule Portal</p>

        <?php if (!empty($message)): ?>
            <div class="alert-box <?php echo $statusClass; ?>" style="margin-bottom: 1rem; padding: 0.75rem; border-radius: 6px; font-size: 0.85rem;">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <form action="signup.php" method="POST">
            <div class="form-group">
                <label for="fullname">Full Name *</label>
                <input type="text" id="fullname" name="fullname" placeholder="e.g. John Doe" required>
            </div>

            <div class="form-group">
                <label for="username">Username *</label>
                <input type="text" id="username" name="username" placeholder="Choose a username" required>
            </div>

            <div class="form-group">
                <label for="student_number">Student Number (Required for Students)</label>
                <input type="text" id="student_number" name="student_number" placeholder="e.g. 202410982">
            </div>

            <div class="form-group">
                <label for="role">Role *</label>
                <select id="role" name="role" required>
                    <option value="Student">Student</option>
                    <option value="Lecturer">Lecturer</option>
                    <option value="Administrator">Administrator</option>
                </select>
            </div>

            <div class="form-group">
                <label for="program_id">Academic Program</label>
                <select id="program_id" name="program_id">
                    <option value="">Select Program...</option>
                    <?php foreach ($programs as $prog): ?>
                        <option value="<?php echo $prog['id']; ?>"><?php echo htmlspecialchars($prog['program_name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="password">Password *</label>
                <input type="password" id="password" name="password" placeholder="Create password" required>
            </div>

            <div class="form-group">
                <label for="confirm_password">Confirm Password *</label>
                <input type="password" id="confirm_password" name="confirm_password" placeholder="Confirm password" required>
            </div>

            <button type="submit" class="btn-submit" style="width: 100%;">Sign Up</button>
        </form>

        <p style="margin-top: 1rem; text-align: center; font-size: 0.85rem; color: #64748b;">
            Already have an account? <a href="login.php" style="color: #2563eb; text-decoration: none; font-weight: 600;">Log In</a>
        </p>
    </div>
</body>
</html>
