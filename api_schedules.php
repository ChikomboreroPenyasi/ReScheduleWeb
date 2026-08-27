<?php
header('Content-Type: application/json');
require_once 'db.php';

$program_id = $_GET['program_id'] ?? $_POST['program_id'] ?? '';

if (empty($program_id)) {
    echo json_encode(['status' => 'error', 'message' => 'Missing program_id']);
    exit();
}

try {
    // Join schedules with rooms and courses to fetch displayable names
    $stmt = $pdo->prepare("
        SELECT 
            s.id,
            s.course_code,
            s.course_name,
            s.day_of_week,
            s.start_time,
            s.end_time,
            s.type,
            s.date,
            r.room_name
        FROM schedules s
        LEFT JOIN rooms r ON s.room_id = r.id
        WHERE s.program_id = :program_id
        ORDER BY s.date ASC, s.start_time ASC
    ");
    $stmt->execute([':program_id' => $program_id]);
    $schedules = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $timetable = [];
    $assessments = [];

    foreach ($schedules as $row) {
        if ($row['type'] === 'Class') {
            $timetable[] = $row;
        } else if ($row['type'] === 'CA' || $row['type'] === 'Exam') {
            $assessments[] = $row;
        }
    }

    echo json_encode([
        'status' => 'success',
        'timetable' => $timetable,
        'assessments' => $assessments
    ]);

} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
}
?>