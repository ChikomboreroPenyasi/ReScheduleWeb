<?php
header('Content-Type: application/json');
require_once 'db.php';

$login_input = trim($_POST['username'] ?? $_GET['username'] ?? '');
$password = trim($_POST['password'] ?? $_GET['password'] ?? '');

if (empty($login_input) || empty($password)) {
    echo json_encode(['status' => 'error', 'message' => 'Please enter both ID/Name and password']);
    exit();
}

try {
    // Queries student_number or fullname from your users table
    $stmt = $pdo->prepare("
        SELECT * FROM users 
        WHERE student_number = :input OR fullname = :input 
        LIMIT 1
    ");
    $stmt->execute([':input' => $login_input]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($password, $user['password_hash'])) {
        unset($user['password_hash']); // Security: omit hash from response
        echo json_encode([
            'status' => 'success',
            'message' => 'Login successful',
            'user' => $user
        ]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Invalid Student Number or Password']);
    }
} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
