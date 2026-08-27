<?php
session_start();
require_once 'db.php'; // Include Supabase helper

// Auth Guard: Admin and Lecturer access only
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'] ?? '', ['Administrator', 'Lecturer'])) {
    header("Location: login.php");
    exit();
}

$message = '';
$error = '';

// Fetch dropdown data from Supabase
$programmes_res = supabase_request('programmes?select=id,program_code,program_name&order=program_name.asc', 'GET');
$courses_res    = supabase_request('courses?select=id,course_code,course_name&order=course_code.asc', 'GET');
$rooms_res      = supabase_request('rooms?select=id,room_name&order=room_name.asc', 'GET');

$programmes = ($programmes_res['status'] === 200 && is_array($programmes_res['data'])) ? $programmes_res['data'] : [];
$courses    = ($courses_res['status'] === 200 && is_array($courses_res['data'])) ? $courses_res['data'] : [];
$rooms      = ($rooms_res['status'] === 200 && is_array($rooms_res['data'])) ? $rooms_res['data'] : [];

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $program_id  = $_POST['program_id'] ?? '';
    $course_id   = $_POST['course_id'] ?? '';
    $room_id     = $_POST['room_id'] ?? '';
    $type        = $_POST['type'] ?? 'Class';
    $day_of_week = $_POST['day_of_week'] ?? 'Monday';
    $start_time  = $_POST['start_time'] ?? '';
    $end_time    = $_POST['end_time'] ?? '';
    $date        = !empty($_POST['date']) ? $_POST['date'] : null;

    if (empty($program_id) || empty($course_id) || empty($room_id) || empty($start_time) || empty($end_time)) {
        $error = "Please fill in all required fields.";
    } else {
        // Construct payload matching database columns
        $payload = [
            'program_id'  => (int)$program_id,
            'course_id'   => (int)$course_id,
            'room_id'     => (int)$room_id,
            'type'        => $type,
            'day_of_week' => $day_of_week,
            'start_time'  => $start_time,
            'end_time'    => $end_time,
            'date'        => $date
        ];

        // POST request to Supabase 'schedules' table
        $result = supabase_request('schedules', 'POST', $payload);

        if ($result['status'] === 201 || $result['status'] === 200) {
            $message = "Schedule published successfully!";
        } else {
            $error = "Error inserting schedule: " . json_encode($result['data']);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Reschedule - Publish Schedule</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body class="dashboard-body">
    <main class="dashboard-container">
        <div class="form-container">
            <h2>Publish Schedule</h2>
            <a href="dashboard.php">&larr; Dashboard</a>

            <?php if (!empty($message)): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div>
            <?php endif; ?>
            <?php if (!empty($error)): ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <form action="add_schedule.php" method="POST">
                <!-- Schedule Category -->
                <div class="form-group">
                    <label for="type">Schedule Category</label>
                    <select name="type" id="type" class="form-control" required>
                        <option value="Class">Regular Class</option>
                        <option value="CA">Continuous Assessment (CA)</option>
                        <option value="Exam">Final Exam</option>
                    </select>
                </div>

                <!-- Programme Dropdown -->
                <div class="form-group">
                    <label for="program_id">Programme</label>
                    <select name="program_id" id="program_id" class="form-control" required>
                        <option value="">-- Select Programme --</option>
                        <?php foreach ($programmes as $p): ?>
                            <option value="<?php echo htmlspecialchars($p['id']); ?>">
                                <?php echo htmlspecialchars(($p['program_code'] ?? '') . ' - ' . ($p['program_name'] ?? '')); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Course Dropdown -->
                <div class="form-group">
                    <label for="course_id">Course</label>
                    <select name="course_id" id="course_id" class="form-control" required>
                        <option value="">-- Select Course --</option>
                        <?php foreach ($courses as $c): ?>
                            <option value="<?php echo htmlspecialchars($c['id']); ?>">
                                <?php echo htmlspecialchars(($c['course_code'] ?? '') . ' - ' . ($c['course_name'] ?? '')); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Venue / Room Dropdown -->
                <div class="form-group">
                    <label for="room_id">Venue / Room</label>
                    <select name="room_id" id="room_id" class="form-control" required>
                        <option value="">-- Select Venue --</option>
                        <?php foreach ($rooms as $r): ?>
                            <option value="<?php echo htmlspecialchars($r['id']); ?>">
                                <?php echo htmlspecialchars($r['room_name'] ?? ''); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Day of Week -->
                <div class="form-group">
                    <label for="day_of_week">Day of Week</label>
                    <select name="day_of_week" id="day_of_week" class="form-control" required>
                        <option value="Monday">Monday</option>
                        <option value="Tuesday">Tuesday</option>
                        <option value="Wednesday">Wednesday</option>
                        <option value="Thursday">Thursday</option>
                        <option value="Friday">Friday</option>
                    </select>
                </div>

                <!-- Start Time & End Time -->
                <div class="form-group">
                    <label for="start_time">Start Time</label>
                    <input type="time" name="start_time" id="start_time" class="form-control" required>
                </div>

                <div class="form-group">
                    <label for="end_time">End Time</label>
                    <input type="time" name="end_time" id="end_time" class="form-control" required>
                </div>

                <!-- Specific Date for Exams/CAs -->
                <div class="form-group">
                    <label for="date">Date (Optional for Exams/CAs)</label>
                    <input type="date" name="date" id="date" class="form-control">
                </div>

                <button type="submit" class="btn-submit">Publish Schedule</button>
            </form>
        </div>
    </main>
</body>
</html>
