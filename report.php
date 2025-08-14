<?php
include("db_connect.php");
$current_date = date('Y-m-d');
$selected_date = isset($_GET['date']) ? $_GET['date'] : $current_date;
$report_date = date('d-m-Y', strtotime($selected_date));
$agencyMap = [
    "Amritsar" => "Bharat Gas",
    "Bathinda" => "Bharat Gas",
    "Jalandhar" => "Bharat Gas",
    "Muktsar" => "Bharat Gas",
    "Barnala" => "Indian Oil",
    "Ludhiana" => "Indian Oil",
    "SAS Nagar" => "Indian Oil"
];
?>

<!DOCTYPE html>
<html>
<head>
    <title>LPG PUNSUP Report</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <style>
        body { background-color: #f4f6f9; }
        .custom-header {
            background-color: #3498db;
            color: white;
            padding: 20px;
            text-align: center;
            font-size: 28px;
            font-weight: bold;
        }
        .status-upto {
            text-align: right;
            font-weight: bold;
            margin-top: 10px;
        }
        @media print {
            @page { size: A4 landscape; margin: 0; }
            body {
                margin: 0;
                padding: 0;
                transform: scale(0.95);
                transform-origin: top center;
                width: 100%;
            }
            .no-print { display: none !important; }
        }
    </style>
</head>
<body>
    <div class="custom-header">
        Punjab State Civil Supplies Corporation Limited
    </div>
    <div class="status-upto container">
        Status Upto <?= $report_date ?>
    </div>

    <div class="container mt-4">
        <div class="mb-3 no-print">
            <form method="get">
                <div class="form-row align-items-end">
                    <div class="col-md-3">
                        <label for="date">Select Date:</label>
                        <input type="date" id="date" name="date" value="<?= $selected_date ?>" class="form-control">
                    </div>
                    <div class="col-md-6 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary mr-2">Search</button>
                        <a href="index.php" class="btn btn-secondary mr-2">Back to Home</a>
                        <button type="button" class="btn btn-success" onclick="window.print()">Print Report</button>
                    </div>
                </div>
            </form>
        </div>

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
                $stmt = $conn->prepare("SELECT id, district_name, days_booking, days_delivery, total_pendancy, pendancy_beyond_48, pendancy_48_percent FROM district_entries WHERE booking_date = ?");
                $stmt->bind_param("s", $selected_date);
                $stmt->execute();
                $stmt->store_result();
                $stmt->bind_result($id, $district, $booking, $delivery, $total, $beyond48, $p48);

                $entries = [];
                while ($stmt->fetch()) {
                    $agency = isset($agencyMap[$district]) ? $agencyMap[$district] : '–';
                    $entries[] = [
                        'district' => $district,
                        'agency' => $agency,
                        'booking' => $booking,
                        'delivery' => $delivery,
                        'total' => $total,
                        'beyond48' => $beyond48,
                        'p48' => $p48
                    ];
                }
                $stmt->close();

                usort($entries, function($a, $b) {
                    $agencyCmp = strcmp($a['agency'], $b['agency']);
                    return $agencyCmp === 0 ? strcmp($a['district'], $b['district']) : $agencyCmp;
                });

                $sr = 1;
                foreach ($entries as $entry) {
                    echo "<tr>";
                    echo "<td>$sr</td>";
                    echo "<td>{$entry['district']}</td>";
                    echo "<td>{$entry['agency']}</td>";
                    echo "<td>{$entry['booking']}</td>";
                    echo "<td>{$entry['delivery']}</td>";
                    echo "<td><a href='#' class='show-detail' data-district='{$entry['district']}' data-type='total' data-value='{$entry['total']}' data-toggle='modal' data-target='#detailsModal'>{$entry['total']}</a></td>";
                    echo "<td><a href='#' class='show-detail' data-district='{$entry['district']}' data-type='beyond48' data-value='{$entry['beyond48']}' data-toggle='modal' data-target='#detailsModal'>{$entry['beyond48']}</a></td>";
                    echo "<td>{$entry['p48']}%</td>";
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

    <!-- Scripts -->
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
