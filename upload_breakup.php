<?php
session_start();
include("db_connect.php");

$updates = [];

if (isset($_POST['submit'])) {
    if (isset($_FILES['csv_file']) && $_FILES['csv_file']['error'] == 0) {
        $file = fopen($_FILES['csv_file']['tmp_name'], "r");

        while (($data = fgetcsv($file, 1000, ",")) !== FALSE) {
            if (count($data) != 5) continue;

            list($district_name, $agency_name, $input_date_raw, $pendency_type, $pendency_value) = $data;

            $date_obj = DateTime::createFromFormat('d-m-Y', trim($input_date_raw));
            if (!$date_obj) continue;
            $input_date = $date_obj->format('Y-m-d');

            $pendency_type = strtolower(trim($pendency_type));
            $pendency_value = intval($pendency_value);

            if (!in_array($pendency_type, ['total', 'beyond_48'])) continue;

            $check = $conn->prepare("SELECT id FROM pendency_breakup WHERE district_name=? AND agency_name=? AND input_date=? AND pendancy_type=?");
            $check->bind_param("ssss", $district_name, $agency_name, $input_date, $pendency_type);
            $check->execute();
            $check->store_result();

            if ($check->num_rows == 0) {
                $stmt = $conn->prepare("INSERT INTO pendency_breakup (district_name, agency_name, input_date, pendancy_type, pendancy_value) VALUES (?, ?, ?, ?, ?)");
                $stmt->bind_param("ssssi", $district_name, $agency_name, $input_date, $pendency_type, $pendency_value);
                $stmt->execute();
                $updates[] = "Inserted: $district_name | $agency_name | $input_date | $pendency_type = $pendency_value";
                $stmt->close();
            } else {
                $update = $conn->prepare("UPDATE pendency_breakup SET pendancy_value=? WHERE district_name=? AND agency_name=? AND input_date=? AND pendancy_type=?");
                $update->bind_param("issss", $pendency_value, $district_name, $agency_name, $input_date, $pendency_type);
                $update->execute();
                $updates[] = "Updated: $district_name | $agency_name | $input_date | $pendency_type = $pendency_value";
            }
        }

        fclose($file);
    } else {
        $updates[] = "No file uploaded or upload error.";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Upload Bifurcated Pendency - LPG PUNSUP</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <style>
        body { background-color: #ecf0f1; }
        .container { margin-top: 50px; max-width: 700px; background: #fff; padding: 30px; border-radius: 10px; }
        .btn-primary { background-color: #3498db; border: none; }
    </style>
</head>
<body>
<div class="container">
    <h4 class="text-center mb-4">Import Bifurcated Pendency CSV</h4>

    <?php if ($updates): ?>
        <div class="alert alert-info">
            <?php foreach ($updates as $msg): echo "<p>" . htmlspecialchars($msg) . "</p>"; endforeach; ?>
        </div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data">
        <div class="form-group">
            <label>Choose CSV File (DD-MM-YYYY):</label>
            <input type="file" name="csv_file" class="form-control" accept=".csv" required>
        </div>
        <button type="submit" name="submit" class="btn btn-primary btn-block">Upload</button>
        <a href="index.php" class="btn btn-secondary btn-block">Back to Home</a>
    </form>
</div>
</body>
</html>
