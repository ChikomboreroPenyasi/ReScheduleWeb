<?php
session_start();

// Auth Guard
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

$studentProgram = null;
$classSchedules = [];
$assessmentSchedules = [];
$totalSchedules = 0;
$totalCourses = 0;
$dbError = '';

try {
    $pdo = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8mb4", $db_user, $db_pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);

    // Fetch user details including program link
    $userStmt = $pdo->prepare("
        SELECT u.id, u.fullname, u.role, u.program_id, p.program_code, p.program_name 
        FROM users u 
        LEFT JOIN programmes p ON u.program_id = p.id 
        WHERE u.id = :user_id
    ");
    $userStmt->execute([':user_id' => $user_id]);
    $userData = $userStmt->fetch();

    if ($userData) {
        $studentProgram = $userData;
    }

    if ($role === 'Student') {
        if (!empty($userData['program_id'])) {

            // 1. Fetch Regular Weekly Classes
            $classStmt = $pdo->prepare("
                SELECT DISTINCT s.id, s.type, s.day_of_week, 
                       DATE_FORMAT(s.start_time, '%H:%i') AS start_time, 
                       DATE_FORMAT(s.end_time, '%H:%i') AS end_time,
                       COALESCE(c.course_code, s.course_code) AS course_code, 
                       COALESCE(c.course_name, s.course_name) AS course_name, 
                       r.room_name, 
                       u.fullname AS lecturer_name
                FROM schedules s
                LEFT JOIN courses c ON s.course_id = c.id
                LEFT JOIN rooms r ON s.room_id = r.id
                LEFT JOIN course_program cp ON c.id = cp.course_id
                LEFT JOIN users u ON c.lecturer_id = u.id
                WHERE (cp.program_id = :program_id OR s.program_id = :program_id)
                  AND (s.type = 'Class' OR s.type IS NULL OR s.type = '')
                ORDER BY FIELD(s.day_of_week, 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'), s.start_time ASC
            ");
            $classStmt->execute([':program_id' => $userData['program_id']]);
            $classSchedules = $classStmt->fetchAll();

            // 2. Fetch Continuous Assessments (CAs) and Exams
            $assessmentStmt = $pdo->prepare("
                SELECT DISTINCT s.id, s.type, s.date, s.day_of_week, 
                       DATE_FORMAT(s.start_time, '%H:%i') AS start_time, 
                       DATE_FORMAT(s.end_time, '%H:%i') AS end_time,
                       COALESCE(c.course_code, s.course_code) AS course_code, 
                       COALESCE(c.course_name, s.course_name) AS course_name, 
                       r.room_name, 
                       u.fullname AS lecturer_name
                FROM schedules s
                LEFT JOIN courses c ON s.course_id = c.id
                LEFT JOIN rooms r ON s.room_id = r.id
                LEFT JOIN course_program cp ON c.id = cp.course_id
                LEFT JOIN users u ON c.lecturer_id = u.id
                WHERE (cp.program_id = :program_id OR s.program_id = :program_id)
                  AND s.type IN ('CA', 'Exam')
                ORDER BY 
                    CASE s.type WHEN 'Exam' THEN 1 WHEN 'CA' THEN 2 END,
                    s.date ASC, 
                    s.start_time ASC
            ");
            $assessmentStmt->execute([':program_id' => $userData['program_id']]);
            $assessmentSchedules = $assessmentStmt->fetchAll();
        }
    } else {
        $totalSchedules = $pdo->query("SELECT COUNT(*) FROM schedules")->fetchColumn();
        $totalCourses = $pdo->query("SELECT COUNT(*) FROM courses")->fetchColumn();
    }
} catch (PDOException $e) {
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
    <style>
        .badge {
            padding: 0.2rem 0.5rem;
            border-radius: 4px;
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
        }
        .badge-class { background: #e2e8f0; color: #334155; }
        .badge-ca { background: #fef3c7; color: #92400e; }
        .badge-exam { background: #fee2e2; color: #991b1b; }
        .btn-edit {
            color: #2563eb;
            font-weight: 600;
            text-decoration: none;
            padding: 0.2rem 0.5rem;
            border: 1px solid #bfdbfe;
            border-radius: 4px;
            background: #eff6ff;
        }
        .btn-edit:hover {
            background: #2563eb;
            color: #ffffff;
        }
    </style>
</head>
<body class="dashboard-body">

    <nav class="navbar">
        <div class="logo">Reschedule<span>.</span></div>
        <div class="user-profile">
            <span style="margin-right: 1rem; color: #475569; font-weight: 500;">
                <?php echo htmlspecialchars($fullname); ?> (<?php echo htmlspecialchars($role); ?>)
            </span>
            <a href="logout.php" class="btn-logout">Log Out</a>
        </div>
    </nav>

    <main class="dashboard-container">

        <?php if (!empty($dbError)): ?>
            <div style="padding: 1rem; background: #fee2e2; border: 1px solid #fca5a5; color: #991b1b; border-radius: 6px; margin-bottom: 1.5rem;">
                <strong>Database Error:</strong> <?php echo htmlspecialchars($dbError); ?>
            </div>
        <?php endif; ?>
        
        <header class="welcome-banner" style="margin-bottom: 2rem;">
            <h1>Welcome, <?php echo htmlspecialchars($fullname); ?>!</h1>
            <?php if ($role === 'Student' && !empty($studentProgram['program_code'])): ?>
                <p>Enrolled Programme: <strong><?php echo htmlspecialchars($studentProgram['program_code'] . ' - ' . $studentProgram['program_name']); ?></strong></p>
            <?php else: ?>
                <p>Overview of master schedules and system management controls.</p>
            <?php endif; ?>
        </header>

        <!-- Navigation Shortcut Cards -->
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 1.5rem; margin-bottom: 2rem;">
            <div class="dash-card">
                <h3>Master Timetable</h3>
                <p style="color: #64748b; font-size: 0.85rem; margin: 0.5rem 0 1rem 0;">View full institutional schedule grid across all programmes.</p>
                <a href="timetable.php" class="card-link" style="font-weight: 600; color: #2563eb; text-decoration: none;">Open Timetable &rarr;</a>
            </div>

            <?php if (in_array($role, ['Administrator', 'Lecturer'])): ?>
                <div class="dash-card">
                    <h3>Manage Courses</h3>
                    <p style="color: #64748b; font-size: 0.85rem; margin: 0.5rem 0 1rem 0;">Create courses and link them to degree/diploma programmes.</p>
                    <a href="manage_courses.php" class="card-link" style="font-weight: 600; color: #2563eb; text-decoration: none;">Manage Courses &rarr;</a>
                </div>
                <div class="dash-card">
                    <h3>Add Schedule</h3>
                    <p style="color: #64748b; font-size: 0.85rem; margin: 0.5rem 0 1rem 0;">Publish classes, CAs, or Exam dates into the system.</p>
                    <a href="add_schedule.php" class="card-link" style="font-weight: 600; color: #2563eb; text-decoration: none;">Add Schedule &rarr;</a>
                </div>
            <?php endif; ?>
        </div>

        <!-- Student Personal Timetable Section -->
        <?php if ($role === 'Student'): ?>
            
            <?php if (empty($studentProgram['program_id'])): ?>
                <div class="dash-card">
                    <div style="padding: 1rem; background: #fffbebfb; border: 1px solid #fef3c7; border-radius: 6px; color: #b45309;">
                        <strong>No Programme Assigned:</strong> Your student profile is not linked to a programme yet. Please contact an administrator or update your profile.
                    </div>
                </div>
            <?php else: ?>

                <!-- CA & Exam Schedule Table -->
                <div class="dash-card" style="margin-bottom: 2rem;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                        <h3 style="color: #991b1b;">CA & Exam Schedule</h3>
                        <span style="font-size: 0.85rem; color: #64748b; font-weight: 500;">Assessments & Finals</span>
                    </div>

                    <?php if (empty($assessmentSchedules)): ?>
                        <p style="color: #64748b; margin-top: 0.5rem;">No scheduled CAs or Exams found for your programme.</p>
                    <?php else: ?>
                        <div style="overflow-x: auto;">
                            <table style="width: 100%; border-collapse: collapse; text-align: left; font-size: 0.9rem;">
                                <thead>
                                    <tr style="background-color: #fee2e2; border-bottom: 2px solid #fca5a5;">
                                        <th style="padding: 0.75rem;">Type</th>
                                        <th style="padding: 0.75rem;">Date</th>
                                        <th style="padding: 0.75rem;">Time</th>
                                        <th style="padding: 0.75rem;">Course Code</th>
                                        <th style="padding: 0.75rem;">Course Title</th>
                                        <th style="padding: 0.75rem;">Room</th>
                                        <th style="padding: 0.75rem;">Lecturer</th>
                                        <?php if (in_array($role, ['Administrator', 'Lecturer'])): ?>
                                            <th style="padding: 0.75rem;">Action</th>
                                        <?php endif; ?>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($assessmentSchedules as $as): 
                                        $typeClass = strtolower($as['type'] ?? 'ca');
                                    ?>
                                        <tr style="border-bottom: 1px solid #e2e8f0;">
                                            <td style="padding: 0.75rem;">
                                                <span class="badge badge-<?php echo $typeClass; ?>">
                                                    <?php echo htmlspecialchars($as['type']); ?>
                                                </span>
                                            </td>
                                            <td style="padding: 0.75rem; font-weight: 600; color: #dc2626;">
                                                <?php 
                                                    if (!empty($as['date'])) {
                                                        echo htmlspecialchars(date('D, M j, Y', strtotime($as['date'])));
                                                    } else {
                                                        echo htmlspecialchars($as['day_of_week'] ?? 'TBA');
                                                    }
                                                ?>
                                            </td>
                                            <td style="padding: 0.75rem; color: #334155;"><?php echo htmlspecialchars($as['start_time']); ?> - <?php echo htmlspecialchars($as['end_time']); ?></td>
                                            <td style="padding: 0.75rem; font-weight: 600;"><?php echo htmlspecialchars($as['course_code'] ?? 'N/A'); ?></td>
                                            <td style="padding: 0.75rem;"><?php echo htmlspecialchars($as['course_name'] ?? 'N/A'); ?></td>
                                            <td style="padding: 0.75rem;">
                                                <span style="background: #fee2e2; color: #991b1b; padding: 0.2rem 0.5rem; border-radius: 4px; font-weight: 500;">
                                                    <?php echo htmlspecialchars($as['room_name'] ?? 'Unassigned'); ?>
                                                </span>
                                            </td>
                                            <td style="padding: 0.75rem; color: #64748b;"><?php echo htmlspecialchars($as['lecturer_name'] ?? 'Unassigned'); ?></td>
                                            <?php if (in_array($role, ['Administrator', 'Lecturer'])): ?>
                                                <td style="padding: 0.75rem;">
                                                    <a href="edit_schedule.php?id=<?php echo $as['id']; ?>" class="btn-edit">Edit</a>
                                                </td>
                                            <?php endif; ?>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Weekly Class Timetable Table -->
                <div class="dash-card">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                        <h3>Weekly Class Timetable</h3>
                        <a href="timetable.php" style="font-size: 0.85rem; color: #2563eb; font-weight: 600; text-decoration: none;">View Master Grid</a>
                    </div>

                    <?php if (empty($classSchedules)): ?>
                        <p style="color: #64748b; margin-top: 0.5rem;">No active class slots found for your programme.</p>
                    <?php else: ?>
                        <div style="overflow-x: auto;">
                            <table style="width: 100%; border-collapse: collapse; text-align: left; font-size: 0.9rem;">
                                <thead>
                                    <tr style="background-color: #f1f5f9; border-bottom: 2px solid #cbd5e1;">
                                        <th style="padding: 0.75rem;">Day</th>
                                        <th style="padding: 0.75rem;">Time</th>
                                        <th style="padding: 0.75rem;">Course Code</th>
                                        <th style="padding: 0.75rem;">Course Title</th>
                                        <th style="padding: 0.75rem;">Room</th>
                                        <th style="padding: 0.75rem;">Lecturer</th>
                                        <?php if (in_array($role, ['Administrator', 'Lecturer'])): ?>
                                            <th style="padding: 0.75rem;">Action</th>
                                        <?php endif; ?>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($classSchedules as $cs): ?>
                                        <tr style="border-bottom: 1px solid #e2e8f0;">
                                            <td style="padding: 0.75rem; font-weight: 600; color: #2563eb;"><?php echo htmlspecialchars($cs['day_of_week'] ?? 'TBA'); ?></td>
                                            <td style="padding: 0.75rem; color: #334155;"><?php echo htmlspecialchars($cs['start_time']); ?> - <?php echo htmlspecialchars($cs['end_time']); ?></td>
                                            <td style="padding: 0.75rem; font-weight: 600;"><?php echo htmlspecialchars($cs['course_code'] ?? 'N/A'); ?></td>
                                            <td style="padding: 0.75rem;"><?php echo htmlspecialchars($cs['course_name'] ?? 'N/A'); ?></td>
                                            <td style="padding: 0.75rem;">
                                                <span style="background: #e0f2fe; color: #0369a1; padding: 0.2rem 0.5rem; border-radius: 4px; font-weight: 500;">
                                                    <?php echo htmlspecialchars($cs['room_name'] ?? 'Unassigned'); ?>
                                                </span>
                                            </td>
                                            <td style="padding: 0.75rem; color: #64748b;"><?php echo htmlspecialchars($cs['lecturer_name'] ?? 'Unassigned'); ?></td>
                                            <?php if (in_array($role, ['Administrator', 'Lecturer'])): ?>
                                                <td style="padding: 0.75rem;">
                                                    <a href="edit_schedule.php?id=<?php echo $cs['id']; ?>" class="btn-edit">Edit</a>
                                                </td>
                                            <?php endif; ?>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>

            <?php endif; ?>

        <?php else: ?>
            <!-- Admin / Lecturer Summary Cards -->
            <div class="dash-card">
                <h3>System Overview</h3>
                <div style="display: flex; gap: 2rem; margin-top: 1rem;">
                    <div>
                        <span style="font-size: 1.8rem; font-weight: 700; color: #2563eb;"><?php echo $totalSchedules; ?></span>
                        <p style="color: #64748b; font-size: 0.85rem; margin: 0;">Allocated Time Slots</p>
                    </div>
                    <div>
                        <span style="font-size: 1.8rem; font-weight: 700; color: #059669;"><?php echo $totalCourses; ?></span>
                        <p style="color: #64748b; font-size: 0.85rem; margin: 0;">Active Courses</p>
                    </div>
                </div>
            </div>
        <?php endif; ?>

    </main>

</body>
</html>