<?php
header('Content-Type: application/json');
require_once 'db.php';

$program_id = isset($_GET['program_id']) ? intval($_GET['program_id']) : 0;

try {
    if ($program_id > 0) {
        $stmt = $pdo->prepare("
            SELECT s.id, COALESCE(c.course_code, s.course_code) AS course_code, 
                   s.type, s.day_of_week, s.date, s.start_time, s.end_time
            FROM schedules s
            LEFT JOIN courses c ON s.course_id = c.id
            WHERE s.program_id = :program_id
            ORDER BY s.id DESC
        ");
        $stmt->execute([':program_id' => $program_id]);
    } else {
        $stmt = $pdo->query("
            SELECT s.id, COALESCE(c.course_code, s.course_code) AS course_code, 
                   s.type, s.day_of_week, s.date, s.start_time, s.end_time
            FROM schedules s
            LEFT JOIN courses c ON s.course_id = c.id
            ORDER BY s.id DESC
        ");
    }
    
    $schedules = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode(['status' => 'success', 'data' => $schedules]);
} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>