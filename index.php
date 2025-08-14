<?php
include("db_connect.php");
$yesterday = date('Y-m-d', strtotime('-1 day'));
$display_date = date('d-m-Y', strtotime('-1 day'));
?>

<!DOCTYPE html>
<html>
<head>
    <title>LPG PUNSUP Dashboard</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <style>
        body { background-color: #f4f6f9; }
        .custom-header {
            background-color: #3498db;
            color: white;
            padding: 10px;
            text-align: center;
        }
        .custom-header span:first-child {
            font-size: 28px;
            font-weight: bold;
            display: block;
        }
        .custom-header span.subtitle {
            font-size: 18px;
            display: block;
            margin-top: 3px;
        }
        .btn-custom { margin-right: 10px; }
        .status-upto {
            text-align: right;
            margin-bottom: 10px;
            font-weight: bold;
        }
        @media print {
            @page { size: landscape; }
            .btn, .text-right { display: none !important; }
        }
    </style>
    <script>
        function updateAgency(district, elementId) {
            const agencyMap = {
                "SAS Nagar": "Indian Oil",
                "Barnala": "Indian Oil",
                "Ludhiana": "Indian Oil",
                "Bathinda": "Bharat Gas",
                "Amritsar": "Bharat Gas",
                "Jalandhar": "Bharat Gas",
                "Muktsar": "Bharat Gas"
            };
            document.getElementById(elementId).innerText = agencyMap[district] || "–";
        }

        function handlePrint() {
            window.print();
        }
    </script>
</head>
<body>
    <div class="custom-header">
        <span>Punjab State Civil Supplies Corporation Limited</span>
        <span class="subtitle">Delivering detail of Gas Agencies</span>
    </div>

    <div class="container mt-4">
        <div class="text-right mb-3">
            <a href="upload_report.php" class="btn btn-warning btn-custom">Import CSV</a>
            <a href="report.php" class="btn btn-primary btn-custom">View Report</a>
            <button onclick="handlePrint()" class="btn btn-success btn-custom">Print</button>
        </div>
        <div class="status-upto">Status Upto <?= $display_date ?></div>

        <table class="table table-bordered table-striped text-center">
            <thead class="thead-dark">
                <tr>
                    <th>Sr. No.</th>
                    <th>District</th>
                    <th>Agency</th>
                    <th>Day's Booking</th>
                    <th>Day's Delivery</th>
                    <th>Total Pendency</th>
                    <th>Pendency Beyond 48 Hours</th>
                    <th>48 Hours Pendency (%)</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $districts = ["SAS Nagar", "Barnala", "Ludhiana", "Bathinda", "Amritsar", "Jalandhar", "Muktsar"];
                $agencyMap = [
                    "SAS Nagar" => "Indian Oil",
                    "Barnala" => "Indian Oil",
                    "Ludhiana" => "Indian Oil",
                    "Bathinda" => "Bharat Gas",
                    "Amritsar" => "Bharat Gas",
                    "Jalandhar" => "Bharat Gas",
                    "Muktsar" => "Bharat Gas"
                ];

                usort($districts, function($a, $b) use ($agencyMap) {
                    $agencyCmp = strcmp($agencyMap[$a], $agencyMap[$b]);
                    return $agencyCmp === 0 ? strcmp($a, $b) : $agencyCmp;
                });

                $sr = 1;
                foreach ($districts as $district) {
                    echo "<tr>";
                    echo "<td>{$sr}</td>";
                    echo "<td><a href='district_report.php?district=" . urlencode($district) . "'>{$district}</a></td>";
                    echo "<td id='agency_{$sr}'></td>";

                    $stmt = $conn->prepare("SELECT days_booking, days_delivery, total_pendancy, pendancy_beyond_48, pendancy_48_percent FROM district_entries WHERE district_name = ? AND booking_date = ? ORDER BY id DESC LIMIT 1");
                    $stmt->bind_param("ss", $district, $yesterday);
                    $stmt->execute();
                    $stmt->bind_result($booking, $delivery, $pendency, $beyond48, $percent48);

                    if ($stmt->fetch()) {
                        echo "<td>{$booking}</td>";
                        echo "<td>{$delivery}</td>";
                        echo "<td><a href='#' class='show-detail' data-district='{$district}' data-type='total' data-value='{$pendency}' data-toggle='modal' data-target='#detailsModal'>{$pendency}</a></td>";
                        echo "<td><a href='#' class='show-detail' data-district='{$district}' data-type='beyond48' data-value='{$beyond48}' data-toggle='modal' data-target='#detailsModal'>{$beyond48}</a></td>";
                        echo "<td>{$percent48}%</td>";
                    } else {
                        echo "<td>–</td><td>–</td><td>–</td><td>–</td><td>–%</td>";
                    }
                    $stmt->close();

                    echo "<script>updateAgency('{$district}', 'agency_{$sr}');</script>";
                    echo "</tr>";
                    $sr++;
                }
                ?>
            </tbody>
        </table>
    </div>

    <!-- Modal -->
    <div class="modal fade" id="detailsModal" tabindex="-1" role="dialog" aria-labelledby="detailsLabel" aria-hidden="true">
      <div class="modal-dialog modal-xl" role="document">
        <div class="modal-content">
          <div class="modal-header bg-primary text-white">
            <h5 class="modal-title" id="detailsLabel">Detail View</h5>
            <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close"><span>&times;</span></button>
          </div>
          <div class="modal-body" id="detailsContent">
            <div class="text-center">Loading...</div>
          </div>
        </div>
      </div>
    </div>

    <!-- Required JS -->
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.0/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>

    <script>
    $(document).ready(function(){
        $('.show-detail').click(function(e){
            e.preventDefault();
            var district = $(this).data('district');
            var type = $(this).data('type');
            var clickedValue = parseInt($(this).data('value'));

            $('#detailsLabel').text((type === 'total' ? 'Total Pendency' : 'Pendency Beyond 48 Hours') + ' - ' + district);
            $('#detailsContent').html('<div class="text-center">Loading...</div>');

            $.get('details_modal.php', {
                district: district,
                type: type,
                clicked: clickedValue
            }, function(data){
                $('#detailsContent').html(data);
            });
        });
    });
    </script>
</body>
</html>
