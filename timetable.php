<?php
session_start();

// Auth Guard: Direct unauthenticated users to login page
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$fullname = $_SESSION['fullname'] ?? 'User';
$role = $_SESSION['role'] ?? 'Student';

// Database Configuration
$db_host = 'localhost';
$db_name = 'reschedule_db';
$db_user = 'root';
$db_pass = '';

$message = "";
$statusClass = "";

try {
    $pdo = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8mb4", $db_user, $db_pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);

    // Handle Schedule Slot Creation (Admins and Lecturers)
    if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_schedule']) && in_array($role, ['Administrator', 'Lecturer'])) {
        $course_id = intval($_POST['course_id'] ?? 0);
        $room_name_input = trim($_POST['room_name'] ?? '');
        $day = trim($_POST['day'] ?? '');
        $start_time = trim($_POST['start_time'] ?? '');
        $end_time = trim($_POST['end_time'] ?? '');

        if ($course_id <= 0 || empty($room_name_input) || empty($day) || empty($start_time) || empty($end_time)) {
            $message = "All schedule fields, including room name, are required.";
            $statusClass = "error";
        } elseif ($start_time >= $end_time) {
            $message = "Start time must be strictly earlier than end time.";
            $statusClass = "error";
        } else {
            // Find or Create Room
            $roomStmt = $pdo->prepare("SELECT id FROM rooms WHERE room_name = :room_name");
            $roomStmt->execute([':room_name' => $room_name_input]);
            $existingRoom = $roomStmt->fetch();

            if ($existingRoom) {
                $room_id = $existingRoom['id'];
            } else {
                // Dynamically insert new room if it doesn't exist yet
                $createRoomStmt = $pdo->prepare("INSERT INTO rooms (room_name, capacity) VALUES (:room_name, 50)");
                $createRoomStmt->execute([':room_name' => $room_name_input]);
                $room_id = $pdo->lastInsertId();
            }

            // Conflict Detection 1: Room Double-booking Check
            $roomConflictStmt = $pdo->prepare("
                SELECT id FROM schedules 
                WHERE room_id = :room_id AND day_of_week = :day 
                AND ((start_time < :end_time AND end_time > :start_time))
            ");
            $roomConflictStmt->execute([
                ':room_id' => $room_id,
                ':day' => $day,
                ':start_time' => $start_time,
                ':end_time' => $end_time
            ]);

            if ($roomConflictStmt->fetch()) {
                $message = "Conflict Detected! Room '" . htmlspecialchars($room_name_input) . "' is already booked during this time slot.";
                $statusClass = "error";
            } else {
                // Conflict Detection 2: Lecturer Double-booking Check
                $lecturerConflictStmt = $pdo->prepare("
                    SELECT s.id FROM schedules s
                    JOIN courses c ON s.course_id = c.id
                    WHERE c.lecturer_id = (SELECT lecturer_id FROM courses WHERE id = :course_id)
                    AND s.day_of_week = :day
                    AND ((s.start_time < :end_time AND s.end_time > :start_time))
                ");
                $lecturerConflictStmt->execute([
                    ':course_id' => $course_id,
                    ':day' => $day,
                    ':start_time' => $start_time,
                    ':end_time' => $end_time
                ]);

                if ($lecturerConflictStmt->fetch()) {
                    $message = "Conflict Detected! The assigned lecturer has another class scheduled during this time slot.";
                    $statusClass = "error";
                } else {
                    // No conflicts — Insert Schedule Entry
                    $insertStmt = $pdo->prepare("
                        INSERT INTO schedules (course_id, room_id, day_of_week, start_time, end_time) 
                        VALUES (:course_id, :room_id, :day, :start_time, :end_time)
                    ");
                    $insertStmt->execute([
                        ':course_id' => $course_id,
                        ':room_id' => $room_id,
                        ':day' => $day,
                        ':start_time' => $start_time,
                        ':end_time' => $end_time
                    ]);
                    $message = "Schedule entry added successfully for room '" . htmlspecialchars($room_name_input) . "'!";
                    $statusClass = "success";
                }
            }
        }
    }

    // Fetch courses dropdown data
    $courses = $pdo->query("SELECT id, course_code, course_name FROM courses ORDER BY course_code ASC")->fetchAll();

    // Fetch Master Schedule
    $scheduleQuery = "
        SELECT s.id, s.day_of_week, DATE_FORMAT(s.start_time, '%H:%i') AS start_time, DATE_FORMAT(s.end_time, '%H:%i') AS end_time,
               c.course_code, c.course_name, r.room_name, u.fullname AS lecturer_name
        FROM schedules s
        JOIN courses c ON s.course_id = c.id
        JOIN rooms r ON s.room_id = r.id
        LEFT JOIN users u ON c.lecturer_id = u.id
        ORDER BY FIELD(s.day_of_week, 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'), s.start_time ASC
    ";
    $schedules = $pdo->query($scheduleQuery)->fetchAll();

} catch (PDOException $e) {
    $message = "Database Error: " . $e->getMessage();
    $statusClass = "error";
}

$daysOfWeek = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reschedule - Master Timetable</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body class="dashboard-body">

    <nav class="navbar">
        <div class="logo">Reschedule<span>.</span></div>
        <div class="user-profile">
            <a href="dashboard.php" class="card-link">&larr; Back to Dashboard</a>
            <a href="logout.php" class="btn-logout">Log Out</a>
        </div>
    </nav>

    <main class="dashboard-container">
        
        <header class="welcome-banner">
            <h1>Master Timetable</h1>
            <p>View current class schedules and allocate lecture slots.</p>
        </header>

        <?php if (!empty($message)): ?>
            <div class="alert-box <?php echo $statusClass; ?>">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <!-- Schedule Entry Form (Admins / Lecturers Only) -->
        <?php if (in_array($role, ['Administrator', 'Lecturer'])): ?>
            <div class="dash-card" style="margin-bottom: 2rem;">
                <h3>Allocate Time Slot</h3>
                <form action="timetable.php" method="POST" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; align-items: end;">
                    <div class="form-group" style="margin: 0;">
                        <label for="course_id">Course</label>
                        <select id="course_id" name="course_id" required>
                            <option value="">Select Course...</option>
                            <?php foreach ($courses as $c): ?>
                                <option value="<?php echo $c['id']; ?>"><?php echo htmlspecialchars($c['course_code'] . ' - ' . $c['course_name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group" style="margin: 0;">
                        <label for="room_name">Lecture Room</label>
                        <input type="text" id="room_name" name="room_name" placeholder="e.g. Lab 1, LT 2" required>
                    </div>

                    <div class="form-group" style="margin: 0;">
                        <label for="day">Day</label>
                        <select id="day" name="day" required>
                            <option value="">Select Day...</option>
                            <?php foreach ($daysOfWeek as $d): ?>
                                <option value="<?php echo $d; ?>"><?php echo $d; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group" style="margin: 0;">
                        <label for="start_time">Start Time</label>
                        <input type="time" id="start_time" name="start_time" required>
                    </div>

                    <div class="form-group" style="margin: 0;">
                        <label for="end_time">End Time</label>
                        <input type="time" id="end_time" name="end_time" required>
                    </div>

                    <button type="submit" name="add_schedule" class="btn-submit" style="height: 42px;">Add Slot</button>
                </form>
            </div>
        <?php endif; ?>

        <!-- Master Timetable Listing -->
        <div class="dash-card">
            <h3>Weekly Timetable Grid</h3>
            
            <?php if (empty($schedules)): ?>
                <p style="color: #64748b; margin-top: 1rem;">No time slots scheduled yet.</p>
            <?php else: ?>
                <div style="overflow-x: auto; margin-top: 1rem;">
                    <table style="width: 100%; border-collapse: collapse; text-align: left; font-size: 0.9rem;">
                        <thead>
                            <tr style="background-color: #f1f5f9; border-bottom: 2px solid #cbd5e1;">
                                <th style="padding: 0.75rem;">Day</th>
                                <th style="padding: 0.75rem;">Time</th>
                                <th style="padding: 0.75rem;">Course Code</th>
                                <th style="padding: 0.75rem;">Course Title</th>
                                <th style="padding: 0.75rem;">Room</th>
                                <th style="padding: 0.75rem;">Lecturer</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($schedules as $s): ?>
                                <tr style="border-bottom: 1px solid #e2e8f0;">
                                    <td style="padding: 0.75rem; font-weight: 600; color: #2563eb;"><?php echo htmlspecialchars($s['day_of_week']); ?></td>
                                    <td style="padding: 0.75rem; color: #334155;"><?php echo htmlspecialchars($s['start_time']); ?> - <?php echo htmlspecialchars($s['end_time']); ?></td>
                                    <td style="padding: 0.75rem; font-weight: 600;"><?php echo htmlspecialchars($s['course_code']); ?></td>
                                    <td style="padding: 0.75rem;"><?php echo htmlspecialchars($s['course_name']); ?></td>
                                    <td style="padding: 0.75rem;"><span style="background: #e0f2fe; color: #0369a1; padding: 0.2rem 0.5rem; border-radius: 4px; font-weight: 500;"><?php echo htmlspecialchars($s['room_name']); ?></span></td>
                                    <td style="padding: 0.75rem; color: #64748b;"><?php echo htmlspecialchars($s['lecturer_name'] ?? 'Unassigned'); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>

    </main>

</body>
</html>