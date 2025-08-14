<?php
include("db_connect.php");

$district = isset($_GET['district']) ? $_GET['district'] : '';
$from_date = isset($_GET['from_date']) ? $_GET['from_date'] : '';
$to_date = isset($_GET['to_date']) ? $_GET['to_date'] : '';
$results = [];

if ($district && $from_date && $to_date) {
    $sql = "SELECT booking_date, days_booking, days_delivery, total_pendancy,
                   pendancy_beyond_48, pendancy_48_percent
            FROM district_entries
            WHERE district_name = ? AND booking_date BETWEEN ? AND ?
            ORDER BY booking_date DESC";

    $stmt = mysqli_prepare($conn, $sql);
    if (!$stmt) {
        die("SQL Prepare Failed: " . mysqli_error($conn));
    }

    mysqli_stmt_bind_param($stmt, 'sss', $district, $from_date, $to_date);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    while ($row = mysqli_fetch_assoc($result)) {
        $results[] = $row;
    }
}

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
    <title><?php echo htmlspecialchars($district); ?> Report</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <style>
        body { background-color: #f4f6f9; }
        .custom-header {
            background-color: #3498db;
            color: white;
            padding: 20px;
            text-align: center;
            font-size: 24px;
            font-weight: bold;
        }
        @media print {
            .noprint, .no-print { display: none; }
            @page { size: landscape; }
        }
        th.small-header {
            font-size: 14px;
            white-space: nowrap;
        }
    </style>
</head>
<body>
    <div class="custom-header">
        <span style="font-size: 36px;">Punjab State Civil Supplies Corporation Limited</span><br>
        <span style="font-size: 24px;">LPG Punsup - <?php echo htmlspecialchars($district); ?> Report</span>
    </div>

    <?php if ($district): ?>
    <div class="text-center mt-3 mb-3 no-print">
        <a href="upload_breakup.php" class="btn btn-warning">Import Bifurcated Data</a>
    </div>
    <?php endif; ?>

    <div class="container mt-4">
        <div class="row noprint mb-3">
            <div class="col-md-12 text-center">
                <form method="GET" class="form-inline justify-content-center">
                    <input type="hidden" name="district" value="<?php echo htmlspecialchars($district); ?>">
                    <label class="mr-2">From:</label>
                    <input type="date" name="from_date" class="form-control mr-3 w-auto" required value="<?php echo htmlspecialchars($from_date); ?>">
                    <label class="mr-2">To:</label>
                    <input type="date" name="to_date" class="form-control mr-3 w-auto" required value="<?php echo htmlspecialchars($to_date); ?>">
                    <button type="submit" class="btn btn-primary mr-2">Search</button>
                    <a href="index.php" class="btn btn-secondary mr-2">Back to Home</a>
                    <button onclick="window.print();" class="btn btn-success">Print Report</button>
                </form>
            </div>
        </div>

        <?php if ($results): ?>
        <table class="table table-bordered table-striped text-center">
            <thead class="thead-dark">
                <tr>
                    <th class="small-header">Sr. No.</th>
                    <th class="small-header">Date</th>
                    <th class="small-header">Agency</th>
                    <th class="small-header">Day's Booking</th>
                    <th class="small-header">Day's Delivery</th>
                    <th class="small-header">Total Pendency</th>
                    <th class="small-header">Pendency Beyond<br>48 Hours</th>
                    <th class="small-header">48 Hours<br>Pendency (%)</th>
                </tr>
            </thead>
            <tbody>
            <?php
            $total_booking = $total_delivery = $total_pendency = $total_beyond_48 = $total_percent_48 = 0;
            $i = 1;
            foreach ($results as $row):
                $beyond_48 = isset($row['pendancy_beyond_48']) ? $row['pendancy_beyond_48'] : 0;
                $percent_48 = isset($row['pendancy_48_percent']) ? round($row['pendancy_48_percent'], 2) : 0;

                $total_booking += $row['days_booking'];
                $total_delivery += $row['days_delivery'];
                $total_pendency += $row['total_pendancy'];
                $total_beyond_48 += $beyond_48;
                $total_percent_48 += $percent_48;
            ?>
                <tr>
                    <td><?php echo $i; ?></td>
                    <td><?php echo date('d-m-Y', strtotime($row['booking_date'])); ?></td>
                    <td><?php echo isset($agencyMap[$district]) ? $agencyMap[$district] : '–'; ?></td>
                    <td><?php echo $row['days_booking']; ?></td>
                    <td><?php echo $row['days_delivery']; ?></td>
                    <td><a href="#" class="show-detail" data-district="<?php echo $district; ?>" data-type="total" data-value="<?php echo $row['total_pendancy']; ?>" data-toggle="modal" data-target="#detailsModal"><?php echo $row['total_pendancy']; ?></a></td>
                    <td><a href="#" class="show-detail" data-district="<?php echo $district; ?>" data-type="beyond48" data-value="<?php echo $beyond_48; ?>" data-toggle="modal" data-target="#detailsModal"><?php echo $beyond_48; ?></a></td>
                    <td><?php echo $percent_48; ?>%</td>
                </tr>
            <?php $i++; endforeach; ?>
            </tbody>
            <tfoot>
                <tr>
                    <th colspan="3" class="text-right">Total Average</th>
                    <th><?php echo round($total_booking / $i, 2); ?></th>
                    <th><?php echo round($total_delivery / $i, 2); ?></th>
                    <th><?php echo round($total_pendency / $i, 2); ?></th>
                    <th><?php echo round($total_beyond_48 / $i, 2); ?></th>
                    <th><?php echo round($total_percent_48 / $i, 2); ?>%</th>
                </tr>
            </tfoot>
        </table>
        <?php elseif ($from_date && $to_date): ?>
            <div class="alert alert-warning text-center">No data found for selected date range.</div>
        <?php endif; ?>
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
