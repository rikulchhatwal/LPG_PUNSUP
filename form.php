<?php 
session_start();
include("db_connect.php");

if (!isset($_GET['district'])) {
    header('Location: index.php');
    exit();
}

$district = $_GET['district'];
$successMsg = "";

// Map district to agency name with suffix
$agency_map = [
    'Kharar' => 'PUNSUP Kharar (IO)',
    'Barnala' => 'PUNSUP Barnala (IO)',
    'Ludhiana' => 'PUNSUP Ludhiana (IO)',
    'Bathinda' => 'PUNSUP Bathinda (BG)',
    'Amritsar' => 'PUNSUP Amritsar (BG)',
    'Jalandhar' => 'PUNSUP Jalandhar (BG)',
    'Muktsar' => 'PUNSUP Muktsar (BG)'
];
$agency_name = isset($agency_map[$district]) ? $agency_map[$district] : 'PUNSUP ' . $district;

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $booking_date = isset($_POST['booking_date']) ? $_POST['booking_date'] : '';
    $days_booking = isset($_POST['days_booking']) ? $_POST['days_booking'] : 0;
    $days_delivery = isset($_POST['days_delivery']) ? $_POST['days_delivery'] : 0;
    $total_pendancy = isset($_POST['total_pendancy']) ? $_POST['total_pendancy'] : 0;
    $pendancy_beyond_48 = isset($_POST['pendancy_beyond_48']) ? $_POST['pendancy_beyond_48'] : 0;
    $pendancy_48_percent = isset($_POST['pendancy_48_percent']) ? $_POST['pendancy_48_percent'] : 0;

    // Check if entry already exists
    $check = $conn->prepare("SELECT id FROM district_entries WHERE district_name = ? AND agency_name = ? AND booking_date = ?");
    $check->bind_param("sss", $district, $agency_name, $booking_date);
    $check->execute();
    $check->store_result();

    if ($check->num_rows > 0) {
        // Update existing
        $stmt = $conn->prepare("UPDATE district_entries SET days_booking=?, days_delivery=?, total_pendancy=?, pendancy_beyond_48=?, pendancy_48_percent=? WHERE district_name=? AND agency_name=? AND booking_date=?");
        $stmt->bind_param("iiiiisss", $days_booking, $days_delivery, $total_pendancy, $pendancy_beyond_48, $pendancy_48_percent, $district, $agency_name, $booking_date);
    } else {
        // Insert new
        $stmt = $conn->prepare("INSERT INTO district_entries (district_name, agency_name, booking_date, days_booking, days_delivery, total_pendancy, pendancy_beyond_48, pendancy_48_percent) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sssiiiii", $district, $agency_name, $booking_date, $days_booking, $days_delivery, $total_pendancy, $pendancy_beyond_48, $pendancy_48_percent);
    }

    if ($stmt && $stmt->execute()) {
        $successMsg = "Data saved successfully!";
        $stmt->close();
    } else {
        $successMsg = "Error: Unable to save data.";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>LPG PUNSUP - <?php echo htmlspecialchars($district); ?></title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <style>
        body {
            background-color: #ecf0f1;
        }
        .container {
            margin-top: 50px;
            max-width: 600px;
            background: #fff;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0px 0px 10px #bdc3c7;
        }
        h3 {
            color: #3498db;
        }
        .btn-primary {
            background-color: #3498db;
            border: none;
        }
        .btn-primary:hover {
            background-color: #2980b9;
        }
        .alert-success {
            margin-top: 20px;
        }
    </style>
</head>
<body>
<div class="container">
    <h3 class="text-center">Data Entry - <?php echo htmlspecialchars($district); ?></h3>

    <?php if ($successMsg) { echo '<div class="alert alert-success text-center">'.$successMsg.'</div>'; } ?>

    <form method="POST">
        <div class="form-group">
            <label for="booking_date">Select Date</label>
            <input type="date" name="booking_date" class="form-control" required>
        </div>
        <div class="form-group">
            <label for="days_booking">Day's Booking</label>
            <input type="number" name="days_booking" class="form-control" required>
        </div>
        <div class="form-group">
            <label for="days_delivery">Day's Delivery</label>
            <input type="number" name="days_delivery" class="form-control" required>
        </div>
        <div class="form-group">
            <label for="total_pendancy">Total Pendency</label>
            <input type="number" name="total_pendancy" class="form-control" required>
        </div>
        <div class="form-group">
            <label for="pendancy_beyond_48">Pendency Beyond 48 Hours</label>
            <input type="number" name="pendancy_beyond_48" class="form-control" required>
        </div>
        <div class="form-group">
            <label for="pendancy_48_percent">48 Hours Pendency (%)</label>
            <input type="number" name="pendancy_48_percent" class="form-control" step="0.01" required>
        </div>
        <button type="submit" class="btn btn-primary btn-block">Save Entry</button>
        <a href="index.php" class="btn btn-secondary btn-block">Back to Home</a>
    </form>
</div>
</body>
</html>
