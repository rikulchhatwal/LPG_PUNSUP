<?php
session_start();
include("db_connect.php");

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $district = $_POST['district'];
    $agency = $_POST['agency'];
    $booking_date = $_POST['booking_date'];
    $days_booking = $_POST['days_booking'];
    $days_delivery = $_POST['days_delivery'];
    $pendancy_0_2 = $_POST['pendancy_0_2'];
    $pendancy_2_5 = $_POST['pendancy_2_5'];
    $pendancy_5_7 = $_POST['pendancy_5_7'];
    $pendancy_7_15 = $_POST['pendancy_7_15'];
    $pendancy_15_plus = $_POST['pendancy_15_plus'];
    $total_pendancy = $_POST['total_pendancy'];
    $yesterday_pendancy = $_POST['yesterday_pendancy'];

    $stmt = $mysqli->prepare("INSERT INTO district_entries 
        (district_name, booking_date, agency_name, days_booking, days_delivery, pendancy_0_2, pendancy_2_5, pendancy_5_7, pendancy_7_15, pendancy_15_plus, total_pendancy, yesterday_pendancy) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

    $stmt->bind_param("sssiiiiiiiii", 
        $district, 
        $booking_date, 
        $agency, 
        $days_booking, 
        $days_delivery, 
        $pendancy_0_2, 
        $pendancy_2_5, 
        $pendancy_5_7, 
        $pendancy_7_15, 
        $pendancy_15_plus, 
        $total_pendancy, 
        $yesterday_pendancy
    );

    $stmt->execute();
    $stmt->close();
    $mysqli->close();

    header('Location: http://117.220.114.74/lpg_punsup/index.php?success=1');
    exit();
} else {
    header('Location: http://117.220.114.74/lpg_punsup/index.php');
    exit();
}
?>