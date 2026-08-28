<?php
session_start();
require_once 'db.php';

// Ensure user is logged in as Lecturer or Administrator
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] !== 'Lecturer' && $_SESSION['role'] !== 'Administrator')) {
    header("Location: login.php");
    exit();
}

$message = '';

// Handle Status Updates (Approve / Reject)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'], $_POST['request_id'])) {
    $request_id = intval($_POST['request_id']);
    $new_status = ($_POST['action'] === 'approve') ? 'Approved' : 'Rejected';

    try {
        $stmt = $pdo->prepare("UPDATE reschedule_requests SET status = :status WHERE id = :id");
        $stmt->execute([
            ':status' => $new_status,
            ':id' => $request_id
        ]);
        $message = "Request #$request_id has been marked as $new_status.";
    } catch (PDOException $e) {
        $message = "Error updating request: " . $e->getMessage();
    }
}

// Fetch Reschedule Requests with Student and Schedule Details
try {
    $query = "
        SELECT 
            r.id AS request_id,
            r.reason,
            r.preferred_time,
            r.status,
            r.created_at,
            u.fullname AS student_name,
            u.student_number,
            s.course_code,
            s.course_name,
            s.day_of_week,
            s.start_time,
            s.end_time,
            rm.room_name
        FROM reschedule_requests r
        JOIN users u ON r.student_id = u.id
        JOIN schedules s ON r.schedule_id = s.id
        LEFT JOIN rooms rm ON s.room_id = rm.id
        ORDER BY r.created_at DESC
    ";
    $stmt = $pdo->query($query);
    $requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Error fetching requests: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Reschedule Requests</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .container { max-width: 1000px; margin: 30px auto; padding: 20px; font-family: Arial, sans-serif; }
        .alert { padding: 10px 15px; margin-bottom: 20px; background-color: #e2e3e5; color: #383d41; border-radius: 4px; }
        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        th, td { padding: 12px; border: 1px solid #ddd; text-align: left; }
        th { background-color: #007bff; color: white; }
        .status-Pending { color: #d97706; font-weight: bold; }
        .status-Approved { color: #16a34a; font-weight: bold; }
        .status-Rejected { color: #dc2626; font-weight: bold; }
        .btn { padding: 6px 12px; border: none; border-radius: 4px; cursor: pointer; color: white; font-weight: bold; }
        .btn-approve { background-color: #16a34a; }
        .btn-reject { background-color: #dc2626; }
        .btn:hover { opacity: 0.85; }
    </style>
</head>
<body>

<div class="container">
    <h2>Class Reschedule Requests</h2>

    <?php if (!empty($message)): ?>
        <div class="alert"><?php echo htmlspecialchars($message); ?></div>
    <?php endif; ?>

    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Student</th>
                <th>Course</th>
                <th>Current Slot</th>
                <th>Reason</th>
                <th>Preferred Time</th>
                <th>Status</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($requests)): ?>
                <tr>
                    <td colspan="8" style="text-align: center;">No reschedule requests submitted yet.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($requests as $req): ?>
                    <tr>
                        <td>#<?php echo $req['request_id']; ?></td>
                        <td>
                            <strong><?php echo htmlspecialchars($req['student_name']); ?></strong><br>
                            <small><?php echo htmlspecialchars($req['student_number'] ?? 'N/A'); ?></small>
                        </td>
                        <td>
                            <strong><?php echo htmlspecialchars($req['course_code']); ?></strong><br>
                            <small><?php echo htmlspecialchars($req['course_name']); ?></small>
                        </td>
                        <td>
                            <?php echo htmlspecialchars($req['day_of_week']); ?><br>
                            <small><?php echo htmlspecialchars($req['start_time'] . ' - ' . $req['end_time']); ?></small>
                        </td>
                        <td><?php echo htmlspecialchars($req['reason']); ?></td>
                        <td><?php echo htmlspecialchars($req['preferred_time'] ?? 'Flexible'); ?></td>
                        <td>
                            <span class="status-<?php echo $req['status']; ?>">
                                <?php echo htmlspecialchars($req['status']); ?>
                            </span>
                        </td>
                        <td>
                            <?php if ($req['status'] === 'Pending'): ?>
                                <form method="POST" style="display:inline;">
                                    <input type="hidden" name="request_id" value="<?php echo $req['request_id']; ?>">
                                    <button type="submit" name="action" value="approve" class="btn btn-approve">Approve</button>
                                    <button type="submit" name="action" value="reject" class="btn btn-reject">Reject</button>
                                </form>
                            <?php else: ?>
                                <em>Processed</em>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

</body>
</html>