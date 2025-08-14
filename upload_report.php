<?php
session_start();
include("db_connect.php");

$message = "";
$updates = [];

if (isset($_POST['submit'])) {
    if (isset($_FILES['csv_file']) && $_FILES['csv_file']['error'] == 0) {
        $file = fopen($_FILES['csv_file']['tmp_name'], "r");

        while (($data = fgetcsv($file, 1000, ",")) !== FALSE) {
            if (count($data) == 8) {
                // Original format for district_entries
                $district_name = $data[0];
                $agency_name = $data[1];
                $booking_date_raw = $data[2];

                $date_obj = DateTime::createFromFormat('d-m-Y', $booking_date_raw);
                if (!$date_obj) continue;
                $booking_date = $date_obj->format('Y-m-d');

                $days_booking = intval($data[3]);
                $days_delivery = intval($data[4]);
                $total_pendancy = intval($data[5]);
                $pendancy_beyond_48 = intval($data[6]);
                $pendancy_48_percent = floatval($data[7]);

                $check = $conn->prepare("SELECT * FROM district_entries WHERE district_name=? AND agency_name=? AND booking_date=?");
                $check->bind_param("sss", $district_name, $agency_name, $booking_date);
                $check->execute();
                $result = $check->get_result();

                if ($result->num_rows > 0) {
                    $existing = $result->fetch_assoc();

                    if (
                        $existing['days_booking'] != $days_booking ||
                        $existing['days_delivery'] != $days_delivery ||
                        $existing['total_pendancy'] != $total_pendancy ||
                        $existing['pendancy_beyond_48'] != $pendancy_beyond_48 ||
                        $existing['pendancy_48_percent'] != $pendancy_48_percent
                    ) {
                        $changes = [];
                        if ($existing['days_booking'] != $days_booking)
                            $changes[] = "Day's Booking updated from {$existing['days_booking']} to $days_booking";
                        if ($existing['days_delivery'] != $days_delivery)
                            $changes[] = "Day's Delivery updated from {$existing['days_delivery']} to $days_delivery";
                        if ($existing['total_pendancy'] != $total_pendancy)
                            $changes[] = "Total Pendency updated from {$existing['total_pendancy']} to $total_pendancy";
                        if ($existing['pendancy_beyond_48'] != $pendancy_beyond_48)
                            $changes[] = "Pendency > 48 Hrs updated from {$existing['pendancy_beyond_48']} to $pendancy_beyond_48";
                        if ($existing['pendancy_48_percent'] != $pendancy_48_percent)
                            $changes[] = "48 Hrs Pendency (%) updated from {$existing['pendancy_48_percent']} to $pendancy_48_percent";

                        $update = $conn->prepare("UPDATE district_entries SET 
                            days_booking=?, 
                            days_delivery=?, 
                            total_pendancy=?, 
                            pendancy_beyond_48=?, 
                            pendancy_48_percent=? 
                            WHERE id=?");

                        $update->bind_param("iiiiii", 
                            $days_booking, 
                            $days_delivery, 
                            $total_pendancy, 
                            $pendancy_beyond_48, 
                            $pendancy_48_percent, 
                            $existing['id']
                        );
                        $update->execute();
                        $updates[] = "District: $district_name, Agency: $agency_name → " . implode("; ", $changes);
                    } else {
                        $updates[] = "District: $district_name, Agency: $agency_name → Data already exists (no changes)";
                    }
                } else {
                    $stmt = $conn->prepare("INSERT INTO district_entries (
                        district_name, agency_name, booking_date, days_booking,
                        days_delivery, total_pendancy, pendancy_beyond_48, pendancy_48_percent
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");

                    if ($stmt) {
                        $stmt->bind_param("sssiiiii", 
                            $district_name,
                            $agency_name,
                            $booking_date,
                            $days_booking,
                            $days_delivery,
                            $total_pendancy,
                            $pendancy_beyond_48,
                            $pendancy_48_percent
                        );
                        $stmt->execute();
                        $updates[] = "District: $district_name, Agency: $agency_name → New entry inserted.";
                        $stmt->close();
                    }
                }

            } elseif (count($data) == 5) {
                // New format for pendency_breakup
                $district_name = trim($data[0]);
                $agency_name = trim($data[1]);
                $input_date = DateTime::createFromFormat('d-m-Y', trim($data[2]));
                $pendency_type = strtolower(trim($data[3]));
                $pendency_value = intval($data[4]);

                if (!$input_date || !in_array($pendency_type, ['total', 'beyond_48'])) continue;
                $formatted_date = $input_date->format('Y-m-d');

                $check = $conn->prepare("SELECT id FROM pendency_breakup WHERE district_name=? AND agency_name=? AND input_date=? AND pendancy_type=?");
                $check->bind_param("ssss", $district_name, $agency_name, $formatted_date, $pendency_type);
                $check->execute();
                $check->store_result();

                if ($check->num_rows == 0) {
                    $stmt = $conn->prepare("INSERT INTO pendency_breakup (district_name, agency_name, input_date, pendancy_type, pendancy_value) VALUES (?, ?, ?, ?, ?)");
                    $stmt->bind_param("ssssi", $district_name, $agency_name, $formatted_date, $pendency_type, $pendency_value);
                    $stmt->execute();
                    $updates[] = "Breakup: $district_name, $agency_name, $formatted_date, $pendency_type → Inserted $pendency_value";
                    $stmt->close();
                } else {
                    $update = $conn->prepare("UPDATE pendency_breakup SET pendancy_value=? WHERE district_name=? AND agency_name=? AND input_date=? AND pendancy_type=?");
                    $update->bind_param("issss", $pendency_value, $district_name, $agency_name, $formatted_date, $pendency_type);
                    $update->execute();
                    $updates[] = "Breakup: $district_name, $agency_name, $formatted_date, $pendency_type → Updated to $pendency_value";
                }
            }
        }
        fclose($file);
    } else {
        $updates[] = "No file uploaded or error.";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Upload Report - LPG PUNSUP</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <style>
        body { background-color: #ecf0f1; }
        .container { margin-top: 50px; max-width: 700px; background: #fff; padding: 30px; border-radius: 10px; }
        .btn-primary { background-color: #3498db; border: none; }
    </style>
</head>
<body>
<div class="container">
    <h4 class="text-center mb-4">Import CSV Report</h4>

    <?php if ($updates): ?>
        <div class="alert alert-info">
            <?php foreach ($updates as $msg): echo "<p>" . htmlspecialchars($msg) . "</p>"; endforeach; ?>
        </div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data">
        <div class="form-group">
            <label>Select CSV file (DD-MM-YYYY):</label>
            <input type="file" name="csv_file" class="form-control" accept=".csv" required>
        </div>
        <button type="submit" name="submit" class="btn btn-primary btn-block">Upload & Import</button>
        <a href="index.php" class="btn btn-secondary btn-block">Back to Home</a>
    </form>
</div>
</body>
</html>
