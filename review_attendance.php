<?php
session_start();
require_once "config/database.php";

// Check if user is logged in
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
    header("location: index.php");
    exit;
}

$message = "";
$message_type = "";
$attendance_data = null;

if($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["qr_data"])){
    $qr_data = json_decode($_POST["qr_data"], true);
    
    if($qr_data && isset($qr_data["user_id"]) && isset($qr_data["timestamp"]) && isset($qr_data["token"])){
        $user_id = $qr_data["user_id"];
        $timestamp = $qr_data["timestamp"];
        $token = $qr_data["token"];
        
        // Verify token
        $expected_token = md5($user_id . $timestamp . 'secret_key');
        if($token === $expected_token){
            
            // Check if QR code is not expired (5 minutes validity)
            if(time() - $timestamp <= 300){
                
                // Fetch attendance records for the user
                $sql = "SELECT a.*, u.username 
                        FROM attendance a 
                        JOIN users u ON a.user_id = u.id 
                        WHERE a.user_id = ? 
                        ORDER BY a.check_in DESC 
                        LIMIT 10";
                
                if($stmt = mysqli_prepare($conn, $sql)){
                    mysqli_stmt_bind_param($stmt, "i", $user_id);
                    
                    if(mysqli_stmt_execute($stmt)){
                        $result = mysqli_stmt_get_result($stmt);
                        $attendance_data = mysqli_fetch_all($result, MYSQLI_ASSOC);
                        
                        if(empty($attendance_data)){
                            $message = "No attendance records found.";
                            $message_type = "warning";
                        } else {
                            $message = "Attendance records retrieved successfully!";
                            $message_type = "success";
                        }
                    } else {
                        $message = "Error retrieving attendance records.";
                        $message_type = "danger";
                    }
                }
            } else {
                $message = "QR code has expired. Please generate a new one.";
                $message_type = "warning";
            }
        } else {
            $message = "Invalid QR code.";
            $message_type = "danger";
        }
    } else {
        $message = "Invalid QR code data.";
        $message_type = "danger";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Review Attendance - QR Attendance System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://unpkg.com/html5-qrcode"></script>
    <style>
        body {
            background-color: #f8f9fa;
        }
        .scanner-container {
            max-width: 800px;
            margin: 30px auto;
            padding: 20px;
        }
        #reader {
            width: 100%;
            background: white;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        .message-container {
            margin-bottom: 20px;
        }
        .attendance-table {
            background: white;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm">
        <div class="container">
            <a class="navbar-brand" href="#">QR Attendance System</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="dashboard.php">Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="review_attendance.php">Review Attendance</a>
                    </li>
                </ul>
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <a class="nav-link" href="logout.php">Logout</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="scanner-container">
        <?php if(!empty($message)): ?>
        <div class="alert alert-<?php echo $message_type; ?> message-container">
            <?php echo $message; ?>
        </div>
        <?php endif; ?>

        <div id="reader"></div>
        
        <form id="qr-form" method="post" style="display: none;">
            <input type="hidden" name="qr_data" id="qr-data">
        </form>

        <?php if($attendance_data): ?>
        <div class="attendance-table">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Check In</th>
                        <th>Check Out</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($attendance_data as $record): ?>
                    <tr>
                        <td><?php echo date('Y-m-d', strtotime($record['check_in'])); ?></td>
                        <td><?php echo date('H:i:s', strtotime($record['check_in'])); ?></td>
                        <td><?php echo $record['check_out'] ? date('H:i:s', strtotime($record['check_out'])) : '-'; ?></td>
                        <td>
                            <span class="badge bg-<?php echo $record['status'] === 'present' ? 'success' : ($record['status'] === 'late' ? 'warning' : 'danger'); ?>">
                                <?php echo ucfirst($record['status']); ?>
                            </span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function onScanSuccess(decodedText, decodedResult) {
            // Stop scanning
            html5QrcodeScanner.pause();
            
            // Submit the form
            document.getElementById('qr-data').value = decodedText;
            document.getElementById('qr-form').submit();
        }

        function onScanFailure(error) {
            // Handle scan failure, usually ignore
            console.warn(`QR code scanning failed: ${error}`);
        }

        let html5QrcodeScanner = new Html5QrcodeScanner(
            "reader",
            { fps: 10, qrbox: {width: 250, height: 250} },
            false
        );
        html5QrcodeScanner.render(onScanSuccess, onScanFailure);
    </script>
</body>
</html> 