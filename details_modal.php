<?php
include("db_connect.php");

$district = isset($_GET['district']) ? $_GET['district'] : '';
$type = isset($_GET['type']) ? $_GET['type'] : '';
$clicked_total = isset($_GET['clicked']) ? intval($_GET['clicked']) : 0;
$from_date = isset($_GET['from_date']) ? $_GET['from_date'] : '';
$to_date = isset($_GET['to_date']) ? $_GET['to_date'] : '';

if (!$district || !in_array($type, ['total', 'beyond48']) || $clicked_total <= 0) {
    echo "<div class='text-danger'>Invalid request parameters.</div>";
    exit;
}

// Always fetch 'beyond_48' data
$type_db = 'beyond_48';

$query = "SELECT input_date, district_name, agency_name, pendancy_value 
          FROM pendency_breakup 
          WHERE district_name = ? AND pendancy_type = ?";
$params = [$district, $type_db];
$types = "ss";

if (!empty($from_date) && !empty($to_date)) {
    $query .= " AND input_date BETWEEN ? AND ?";
    $params[] = $from_date;
    $params[] = $to_date;
    $types .= "ss";
}

$query .= " ORDER BY input_date DESC";

$stmt = $conn->prepare($query);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo "<div class='text-warning'>No breakup data available for this entry.</div>";
    exit;
}

// Fetch into array
$rows = [];
while ($row = $result->fetch_assoc()) {
    $rows[] = $row;
}

// For beyond48: remove most recent 3 dates
if ($type === 'beyond48' && count($rows) > 3) {
    $rows = array_slice($rows, 3);
}
?>

<style>
    @media print {
        @page {
            size: A4 landscape;
            margin: 10mm;
        }
        body {
            background: #fff !important;
            color: #000 !important;
            margin: 0;
            padding: 0;
            font-size: 14px;
            zoom: 95%;
        }
        .no-print {
            display: none !important;
        }
        .print-container {
            width: 100%;
            padding: 10px;
        }
        .print-header {
            text-align: center;
            font-size: 20px;
            font-weight: bold;
            margin-bottom: 10px;
        }
    }

    .print-container {
        padding: 15px;
    }

    .print-header {
        text-align: center;
        font-size: 20px;
        font-weight: bold;
        margin-bottom: 10px;
    }
</style>

<div class="print-container">
    <div class="print-header">
        Punjab State Civil Supplies Corporation Limited<br>
        LPG PUNSUP - <?= htmlspecialchars($district); ?> Report<br>
        
    </div>

    <table class='table table-bordered table-sm text-center'>
        <thead class='thead-dark'>
            <tr>
                <th>Date</th>
                <th>District</th>
                <th>Agency</th>
                <th>Pendency</th>
                <th>Percentage</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $total = 0;
            $percentages = [];

            foreach ($rows as $row) {
                if ($row['pendancy_value'] == 0) continue;
                if ($total + $row['pendancy_value'] > $clicked_total) continue;
                $total += $row['pendancy_value'];

                // ✅ Get total pendency from pendency_breakup where type = 'total'
                $total_stmt = $conn->prepare("SELECT pendancy_value FROM pendency_breakup WHERE district_name = ? AND agency_name = ? AND input_date = ? AND pendancy_type = 'total' LIMIT 1");
                $total_stmt->bind_param("sss", $row['district_name'], $row['agency_name'], $row['input_date']);
                $total_stmt->execute();
                $res_total = $total_stmt->get_result();
                $trow = $res_total->fetch_assoc();
                $total_for_date = $trow ? $trow['pendancy_value'] : 0;

                $percentage = ($total_for_date > 0) ? ($row['pendancy_value'] / $total_for_date) * 100 : 0;
                $percentages[] = $percentage;

                echo "<tr>";
                echo "<td>" . date('d-m-Y', strtotime($row['input_date'])) . "</td>";
                echo "<td>{$row['district_name']}</td>";
                echo "<td>{$row['agency_name']}</td>";
                echo "<td>{$row['pendancy_value']}</td>";
                echo "<td>" . round($percentage, 2) . "%</td>";
                echo "</tr>";

                if ($total >= $clicked_total) break;
            }

            $average_percentage = count($percentages) ? array_sum($percentages) / count($percentages) : 0;
            ?>
        </tbody>
        <tfoot>
            <tr class='font-weight-bold'>
                <td colspan='3'>Total</td>
                <td><?= $total ?></td>
                <td><?= round($average_percentage, 2) ?>%</td>
            </tr>
        </tfoot>
    </table>
</div>
