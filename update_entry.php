<?php
session_start();
include("db_connect.php");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'];
    $days_booking = $_POST['days_booking'];
    $days_delivery = $_POST['days_delivery'];
    $total_pendancy = $_POST['total_pendancy'];
    $pendancy_beyond_48 = $_POST['pendancy_beyond_48'];
    $pendancy_48_percent = $_POST['pendancy_48_percent'];

    $sql = "UPDATE district_entries 
            SET days_booking = ?, 
                days_delivery = ?, 
                total_pendancy = ?, 
                pendancy_beyond_48 = ?, 
                pendancy_48_percent = ?
            WHERE id = ?";

    $stmt = $conn->prepare($sql);
    
    if ($stmt) {
        $stmt->bind_param("iiiiii", 
            $days_booking, 
            $days_delivery, 
            $total_pendancy, 
            $pendancy_beyond_48, 
            $pendancy_48_percent, 
            $id
        );
        $stmt->execute();
        $stmt->close();
    } else {
        die("Error preparing update statement.");
    }
}

header("Location: report.php?date=" . date('Y-m-d'));
exit();
