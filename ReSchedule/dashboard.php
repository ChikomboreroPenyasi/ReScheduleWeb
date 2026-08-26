<?php
session_start();
require_once 'db.php'; // Include Supabase helper

// Auth Guard
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$fullname = $_SESSION['fullname'] ?? 'User';
$role = $_SESSION['role'] ?? 'Student';

$studentProgram = null;
$classSchedules = [];
$assessmentSchedules = [];
$totalSchedules = 0;
$totalCourses = 0;
$dbError = '';

try {
    // Note: User profile data still uses local PDO or can be migrated. 
    // Here we focus on fetching schedules from Supabase.
    
    // Example program code for student (Replace with your session logic if available)
    $programCode = $_SESSION['program_code'] ?? 'BSc ICT';

    if ($role === 'Student') {
        // 1. Fetch Regular Weekly Classes from Supabase
        $classResponse = supabase_request("schedules?programme=eq." . urlencode($programCode) . "&type=eq.Class");
        if ($classResponse['status'] === 200) {
            $classSchedules = $classResponse['data'];
        }

        // 2. Fetch Assessments (CA & Exams) from Supabase
        $assessmentResponse = supabase_request("schedules?programme=eq." . urlencode($programCode) . "&type=in.(CA,Exam)");
        if ($assessmentResponse['status'] === 200) {
            $assessmentSchedules = $assessmentResponse['data'];
        }
    } else {
        // Fetch all schedules count for Admin/Lecturer
        $allResponse = supabase_request("schedules?select=id");
        if ($allResponse['status'] === 200) {
            $totalSchedules = count($allResponse['data']);
        }
    }
} catch (Exception $e) {
    $dbError = $e->getMessage();
}

$daysOfWeek = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reschedule - Dashboard</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body class="dashboard-body">

    <nav class="navbar">
        <div class="logo">Reschedule<span>.</span></div>
        <div class="user-profile">
            <span><?php echo htmlspecialchars($fullname); ?> (<?php echo htmlspecialchars($role); ?>)</span>
            <a href="logout.php" class="btn-logout">Log Out</a>
        </div>
    </nav>

    <main class="dashboard-container">
        <?php if (!empty($dbError)): ?>
            <div class="alert alert-danger"><strong>Error:</strong> <?php echo htmlspecialchars($dbError); ?></div>
        <?php endif; ?>

        <header class="welcome-banner">
            <h1>Welcome, <?php echo htmlspecialchars($fullname); ?>!</h1>
        </header>

        <!-- CA & Exam Schedule Table -->
        <div class="dash-card">
            <h3>CA & Exam Schedule</h3>
            <?php if (empty($assessmentSchedules)): ?>
                <p>No scheduled CAs or Exams found.</p>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>Type</th>
                            <th>Title/Course</th>
                            <th>Date/Time</th>
                            <th>Venue</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($assessmentSchedules as $as): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($as['type'] ?? 'CA'); ?></td>
                                <td><?php echo htmlspecialchars($as['title'] ?? $as['course_name'] ?? ''); ?></td>
                                <td><?php echo htmlspecialchars($as['date_time'] ?? $as['date'] ?? ''); ?></td>
                                <td><?php echo htmlspecialchars($as['venue'] ?? $as['room_name'] ?? ''); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>

        <!-- Weekly Class Timetable Table -->
        <div class="dash-card">
            <h3>Weekly Class Timetable</h3>
            <?php if (empty($classSchedules)): ?>
                <p>No active class slots found.</p>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>Title/Course</th>
                            <th>Time</th>
                            <th>Venue</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($classSchedules as $cs): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($cs['title'] ?? $cs['course_name'] ?? ''); ?></td>
                                <td><?php echo htmlspecialchars($cs['date_time'] ?? $cs['start_time'] ?? ''); ?></td>
                                <td><?php echo htmlspecialchars($cs['venue'] ?? $cs['room_name'] ?? ''); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </main>
</body>
</html>