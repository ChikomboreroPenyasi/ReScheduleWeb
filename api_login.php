<?php
// Prevent PHP warnings from corrupting the JSON payload
ob_start();
error_reporting(E_ALL);
ini_set('display_errors', 0);

header('Content-Type: application/json; charset=utf-8');
require_once 'db.php';

try {
    $rawInput = file_get_contents('php://input');
    $jsonInput = json_decode($rawInput, true);

    $username = trim($jsonInput['username'] ?? $_POST['username'] ?? '');
    $password = trim($jsonInput['password'] ?? $_POST['password'] ?? '');

    if (empty($username) || empty($password)) {
        ob_clean();
        echo json_encode([
            'status'  => 'error',
            'message' => 'Username and password are required'
        ]);
        exit();
    }

    // Query exact database columns: year_level and semester
    $stmt = $pdo->prepare("
        SELECT 
            u.id, 
            u.username, 
            u.password, 
            u.fullname, 
            u.role, 
            u.program_id, 
            COALESCE(p.program_name, 'General Studies') AS program_name,
            COALESCE(p.year_level, '1') AS year_level,
            COALESCE(p.semester, '1') AS semester
        FROM users u
        LEFT JOIN programmes p ON u.program_id = p.id
        WHERE u.username = :username
        LIMIT 1
    ");
    $stmt->execute([':username' => $username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && ($password === $user['password'] || password_verify($password, $user['password']))) {
        ob_clean();
        echo json_encode([
            'status' => 'success',
            'message' => 'Login successful',
            'user'   => [
                'id'             => intval($user['id']),
                'username'       => $user['username'],
                'fullname'       => $user['fullname'] ?? $user['username'],
                'role'           => $user['role'] ?? 'Student',
                'program_id'     => intval($user['program_id'] ?? 0),
                'programme_name' => $user['program_name'],
                'year'           => (string)$user['year_level'],
                'semester'       => (string)$user['semester']
            ]
        ]);
        exit();
    } else {
        ob_clean();
        echo json_encode([
            'status'  => 'error',
            'message' => 'Invalid username or password'
        ]);
        exit();
    }

} catch (PDOException $e) {
    ob_clean();
    echo json_encode([
        'status'  => 'error',
        'message' => 'Database error: ' . $e->getMessage()
    ]);
    exit();
} catch (Exception $e) {
    ob_clean();
    echo json_encode([
        'status'  => 'error',
        'message' => 'Server error: ' . $e->getMessage()
    ]);
    exit();
}
?>
