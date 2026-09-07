<?php
header('Content-Type: application/json');
require_once 'db.php';

// ... authentication logic ...

$stmt = $pdo->prepare("
    SELECT u.id, u.username, u.fullname, u.role, u.program_id, 
           p.program_name, p.year, p.semester
    FROM users u
    LEFT JOIN programmes p ON u.program_id = p.id
    WHERE u.username = :username AND u.password = :password
");
$stmt->execute([':username' => $username, ':password' => $password]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if ($user) {
    echo json_encode([
        'status' => 'success',
        'user'   => [
            'id'             => $user['id'],
            'username'       => $user['username'],
            'fullname'       => $user['fullname'],
            'role'           => $user['role'],
            'program_id'     => $user['program_id'],
            'programme_name' => $user['program_name'] ?? 'Not Assigned',
            'year'           => $user['year'] ?? '1',
            'semester'       => $user['semester'] ?? '1'
        ]
    ]);
}
?>
