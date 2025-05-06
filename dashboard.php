<?php
session_start();
require_once "config/database.php";
require_once "includes/qr_generator.php";

// Check if user is logged in
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
    header("location: index.php");
    exit;
}

// Get user's details
$user_id = $_SESSION["id"];
$user_details = array();

$sql = "SELECT username, full_name, email FROM users WHERE id = ?";
if($stmt = mysqli_prepare($conn, $sql)){
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    if(mysqli_stmt_execute($stmt)){
        $result = mysqli_stmt_get_result($stmt);
        $user_details = mysqli_fetch_assoc($result);
    }
    mysqli_stmt_close($stmt);
}

// Get user's attendance records
$attendance_records = array();

$sql = "SELECT * FROM attendance WHERE user_id = ? ORDER BY check_in DESC LIMIT 10";
if($stmt = mysqli_prepare($conn, $sql)){
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    if(mysqli_stmt_execute($stmt)){
        $result = mysqli_stmt_get_result($stmt);
        while($row = mysqli_fetch_assoc($result)){
            $attendance_records[] = $row;
        }
    }
    mysqli_stmt_close($stmt);
}

// Get user's QR code
$qr_path = getQRCodePath($user_details['username']);

// If QR code doesn't exist, generate a new one
if (!$qr_path) {
    $userData = [
        'id' => $user_id,
        'username' => $user_details['username'],
        'full_name' => $user_details['full_name'],
        'email' => $user_details['email']
    ];
    $qr_path = generateUserQRCode($userData);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>QR Attendance System - Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .dashboard-container {
            max-width: 1200px;
            margin: 30px auto;
            padding: 20px;
        }
        .qr-container {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            text-align: center;
            margin-bottom: 20px;
        }
        .attendance-container {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        .nav-link {
            color: #333;
        }
        .nav-link.active {
            color: #0d6efd;
        }
        .qr-wrapper {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            margin: 20px 0;
        }
        .qr-wrapper img {
            max-width: 250px;
            height: auto;
        }
        .print-button {
            margin-top: 20px;
            padding: 10px 25px;
            background: #3498db;
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        .print-button:hover {
            background: #2980b9;
            transform: translateY(-2px);
        }
        @media print {
            body * {
                visibility: hidden;
            }
            .qr-container, .qr-container * {
                visibility: visible;
            }
            .qr-container {
                position: absolute;
                left: 0;
                top: 0;
                width: 100%;
                box-shadow: none;
                padding: 0;
            }
            .qr-wrapper {
                background: none;
                padding: 0;
                margin: 0;
                text-align: center;
            }
            .qr-wrapper img {
                max-width: 300px;
                margin: 20px auto;
            }
            .print-button, .navbar, .attendance-container {
                display: none !important;
            }
            .qr-container h4 {
                margin-bottom: 20px;
            }
            .qr-container p {
                margin-top: 20px;
            }
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
                        <a class="nav-link active" href="dashboard.php">Dashboard</a>
                    </li>
                    <?php if($_SESSION["role"] === "admin"): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="admin.php">Admin Panel</a>
                    </li>
                    <?php endif; ?>
                </ul>
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <a class="nav-link" href="logout.php">Logout</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="dashboard-container">
        <div class="row">
            <div class="col-md-4">
                <div class="qr-container">
                    <h4>Your QR Code</h4>
                    <div class="qr-wrapper">
                        <img src="<?php echo htmlspecialchars($qr_path); ?>" alt="QR Code">
                    </div>
                    <p class="text-muted">Show this QR code to mark your attendance</p>
                    <button class="print-button" onclick="window.print()">
                        <i class="fas fa-print"></i> Print QR Code
                    </button>
                </div>
            </div>
            <div class="col-md-8">
                <div class="attendance-container">
                    <h4>Recent Attendance Records</h4>
                    <div class="table-responsive mt-3">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Check In</th>
                                    <th>Check Out</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($attendance_records as $record): ?>
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
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 