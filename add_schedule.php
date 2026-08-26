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

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $programme = $_POST['programme'] ?? $_POST['program_id'] ?? '';
    $title     = $_POST['course_name'] ?? $_POST['title'] ?? '';
    $dateTime  = $_POST['date_time'] ?? ($_POST['date'] . ' ' . $_POST['start_time']);
    $venue     = $_POST['venue'] ?? $_POST['room_id'] ?? '';
    $type      = $_POST['type'] ?? 'Class';

    if (empty($programme) || empty($title) || empty($dateTime) || empty($venue)) {
        $error = "Please fill in all required fields.";
    } else {
        // Construct payload for Supabase REST API
        $payload = [
            'programme' => $programme,
            'title'     => $title,
            'date_time' => $dateTime,
            'venue'     => $venue,
            'type'      => $type
        ];

        // POST request to Supabase 'schedules' table
        $result = supabase_request('schedules', 'POST', $payload);

        if ($result['status'] === 201 || $result['status'] === 200) {
            $message = "Schedule published successfully to Supabase!";
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
            <h2>Publish Schedule (Supabase)</h2>
            <a href="dashboard.php">&larr; Dashboard</a>

            <?php if (!empty($message)): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div>
            <?php endif; ?>
            <?php if (!empty($error)): ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <form action="add_schedule.php" method="POST">
                <div class="form-group">
                    <label for="type">Schedule Category</label>
                    <select name="type" id="type" class="form-control">
                        <option value="Class">Regular Class</option>
                        <option value="CA">Continuous Assessment (CA)</option>
                        <option value="Exam">Final Exam</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="programme">Programme Name / Code</label>
                    <input type="text" name="programme" id="programme" class="form-control" placeholder="e.g. BSc ICT" required>
                </div>

                <div class="form-group">
                    <label for="title">Course / Title</label>
                    <input type="text" name="title" id="title" class="form-control" placeholder="e.g. ICT 211 - Web Development" required>
                </div>

                <div class="form-group">
                    <label for="venue">Venue / Room</label>
                    <input type="text" name="venue" id="venue" class="form-control" placeholder="e.g. Lab 2" required>
                </div>

                <div class="form-group">
                    <label for="date_time">Date & Time</label>
                    <input type="text" name="date_time" id="date_time" class="form-control" placeholder="e.g. 2026-08-30 09:00:00 or Mon 09:00" required>
                </div>

                <button type="submit" class="btn-submit">Publish Schedule</button>
            </form>
        </div>
    </main>
</body>
</html>