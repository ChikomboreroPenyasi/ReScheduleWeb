<?php
header('Content-Type: application/json');
require_once 'db.php';

$login_input = trim($_POST['username'] ?? '');
$password = trim($_POST['password'] ?? '');

if (empty($login_input) || empty($password)) {
    echo json_encode(['status' => 'error', 'message' => 'Missing student number or password']);
    exit();
}

try {
    // Check against username or student_number column
    $stmt = $pdo->prepare("
        SELECT * FROM users 
        WHERE username = :input OR student_number = :input 
        LIMIT 1
    ");
    $stmt->execute([':input' => $login_input]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    // Verify raw input against hashed password stored in DB
    if ($user && password_verify($password, $user['password_hash'])) {
        unset($user['password_hash']); // Do not expose the password hash
        echo json_encode([
            'status' => 'success',
            'message' => 'Login successful',
            'user' => $user
        ]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Invalid credentials or user not found']);
    }
} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
}
?>