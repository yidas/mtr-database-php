<?php

// Config file
$config = require __DIR__ . '/config.inc.php';
$dbConfig = & $config['database'];
$config = $config['dashboard'];

if (!$config['enabled']) {
    die("Dashboard is disabled");
}

// HTTP Authentication
if ($config['username']) {

    $username = isset($_SERVER['PHP_AUTH_USER']) ? $_SERVER['PHP_AUTH_USER'] : '';
    $password = isset($_SERVER['PHP_AUTH_PW']) ? $_SERVER['PHP_AUTH_PW'] : '';

    if ($config['username'] != $username || $config['password'] != $password) {
        header('WWW-Authenticate: Basic realm="Auth required"');
        die("Access denied");
    }
}

// Route
$route = isset($_GET['route']) ? $_GET['route'] : null;
switch ($route) {
    case 'get':
        getController($dbConfig);
        exit;
        break;
    
    default:
        break;
}

function getController($dbConfig) {

    // Database connection
    try {

        // Database connection
        $conn = new PDO("mysql:host={$dbConfig['host']};dbname={$dbConfig['database']}", $dbConfig['username'], $dbConfig['password']);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_SILENT);

    } catch (PDOException $e) {
        
        die("Error!: " . $e->getMessage() . "\n");
    }

    $datetimeFormat = 'Y-m-d H:i:s';
    $datetimeFrom = isset($_GET['from']) ? $_GET['from'] : date($datetimeFormat, (time() - 24*3600) );
    $datetimeTo = isset($_GET['to']) ? $_GET['to'] : date($datetimeFormat);

    // SQL value map
    $valueMap = [
        'from' => $datetimeFrom,
        'to' => $datetimeTo,
    ];
    // Base SQL
    $sql = "SELECT * FROM `{$dbConfig['table']}` WHERE `start_datetime` between :from and :to";
    // Category condition
    $category = isset($_GET['category']) ? $_GET['category'] : "";
    if ($category !== " ") {
        $sql .= " AND `category` = :category";
        $valueMap['category'] = $category;
    }
    $sql .= " ORDER BY `start_datetime` DESC LIMIT 1000;";

    // Execute query
    $stmt = $conn->prepare($sql);
    foreach ($valueMap as $key => $value) {
        $stmt->bindValue(":{$key}", $value);
    }
    $stmt->execute();
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    // var_dump($data);exit;

    // Data for chart
    $chartData = [
        'loss' => ['labels'=>[], 'data'=> []],
        'worst' => ['labels'=>[], 'data'=> []],
    ];
    foreach (array_reverse($data) as $key => $row) {
        
        $datetime = date("m/d H:i", strtotime($row['start_datetime']));
        $chartData['loss']['labels'][] = $datetime;
        $chartData['worst']['labels'][] = $datetime;
        // Data value
        $chartData['loss']['data'][] = (float) $row['mtr_loss'];
        $chartData['worst']['data'][] = (float) $row['mtr_worst'];
    }

    // Output
    $outputData = [
        'table' => $data,
        'chart' => $chartData,
    ];
    $json = json_encode($outputData);

    header('Content-Type: application/json; charset=utf-8');
    echo $json;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>MTR Database Dashboard</title>
  <link rel="icon" type="image/x-icon" class="js-site-favicon" href="https://github.com/fluidicon.png">
  <link rel="stylesheet" href="./dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="./dist/css/tempusdominus-bootstrap-4.min.css">
  <link rel="stylesheet" href="./dist/css/jquery.dataTables.min.css">
  <link rel="stylesheet" href="./dist/css/fontawesome.min.css">
  <style>
    html {font-size: 13px;}
    .table-mtr {font-size: 12px;}
    table tr td.tables-details-control {
        text-align: center;
        cursor: pointer;
    }
    i.fa-xs {font-size:9px;} 
  </style>
</head>
<body>
    

<div style="padding:30px 10px; max-width: 1200px; margin: auto;">
  <h3>MTR Database Dashboard <a href="https://github.com/yidas/mtr-database-php"><img src="https://github.com/favicon.ico" height="20" width="20"></a></h3>
  <hr>
  
  <nav class="navbar navbar-expand-lg navbar-light bg-light">
    <!-- <form class="form-inline my-12 my-lg-12"> -->
    <div class="form-inline">
      <button class="btn btn-outline-success mr-2 action-search" type="submit"><i class="fa fa-search" aria-hidden="true"></i></button>

      <div class="input-group date" id="datetimepickerFrom" data-target-input="nearest">
        <input type="text" class="form-control datetimepicker-input datetimepicker-input-from" data-target="#datetimepickerFrom" placeholder="From" />
        <div class="input-group-append" data-target="#datetimepickerFrom" data-toggle="datetimepicker">
          <div class="input-group-text"><i class="fa fa-calendar"></i></div>
        </div>
      </div>
      <span class="ml-2 mr-2">-</span>
      <div class="input-group date" id="datetimepickerTo" data-target-input="nearest">
        <input type="text" class="form-control datetimepicker-input datetimepicker-input-to" data-target="#datetimepickerTo" placeholder="To" />
        <div class="input-group-append" data-target="#datetimepickerTo" data-toggle="datetimepicker">
          <div class="input-group-text"><i class="fa fa-calendar"></i></div>
        </div>
      </div>
    </div>
    <div class="form-inline ml-auto">
      <div class="input-group float-right">
        <select class="custom-select category-input" id="inputGroupSelect02">
          <option value=" ">(Category)</option>
          <?php foreach((array)$config['categories'] as $key => $category): ?>
          <option value="<?=$category?>"><?=$category?></option>
          <?php endforeach ?>
        </select>
        <div class="input-group-append">
          <label class="input-group-text" for="inputGroupSelect02"><i class="fa fa-tags" aria-hidden="true"></i></label>
        </div>
      </div>
    </div>
    <!-- </form> -->
  </nav>

  <hr>

  <div id="chartWorstContainer" style="height:200px; display:none;">
    <canvas id="chartWorst" width="100%"></canvas>
  </div>

  <div id="chartLossContainer" style="height:200px; display:none;">
    <canvas id="chartLoss" width="100%"></canvas>
  </div>

  <hr>
  
  <div id="tableBlock" style="display:none;">
    <table id="mtrTable" class="table table-bordered table-mtr" style="width:100%; min-width:1000px; word-wrap: break-word; table-layout: fixed;">
      <thead>
        <tr>
          <th style="width: 20px">
            
          </th>
          <th style="width: 8%">
            From
          </th>
          <th style="width: 8%">
            To
          </th>
          <th>
            Period
            (min)
          </th>
          <th>
            Cat.
          </th>
          <th style="width: 8%">
            Source
          </th>
          <th style="width: 8%">
            Destination
          </th>
          <th>
            Loss
            (%)
          </th>
          <th>
            Count
          </th>
          <th>
            Avg
            (ms)
          </th>
          <th>
            Best
            (ms)
          </th>
          <th>
            Wrst
            (ms)
          </th>
        </tr>
      </thead>
      <tfoot>
    </table>
  </div>

</div>    

<script src="./dist/js/jquery.min.js"></script>
<script src="./dist/js/bootstrap.min.js"></script>
<script src="./dist/js/moment.min.js"></script>
<script src="./dist/js/tempusdominus-bootstrap-4.min.js"></script>
<script src="./dist/js/jquery.dataTables.min.js"></script>
<script src="./dist/js/jquery.dataTables.bootstrap.min.js"></script>
<script src="./dist/js/chart.min.js"></script>
<script type="text/javascript">

    //  Global variable
    var datatable;

    $(function () {
        // Datetime format
        var datetimeFormat = 'YYYY-MM-DD HH:mm:ss';
        // jQuery DOM
        var $datetimepickerForm = $('.datetimepicker-input-from');
        var $datetimepickerTo = $('.datetimepicker-input-to');
        var $category = $('.category-input');
        // Datetime picker
        $('#datetimepickerFrom, #datetimepickerTo').datetimepicker({
            format: datetimeFormat
        });
        // Default time
        var time = new Date();
        $datetimepickerForm.val(moment(time).subtract(1, 'days').format(datetimeFormat));
        $datetimepickerTo.val(moment(time).format(datetimeFormat));

        // Chart initialization
        var chartLoss = new Chart(
            document.getElementById('chartLoss'),
            {
                type: 'line',
                data: {},
                options: {
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            suggestedMin: 0,
                            suggestedMax: 100,
                        }
                    }
                }
            }
        );
        var chartWorst = new Chart(
            document.getElementById('chartWorst'),
            {
                type: 'line',
                data: {},
                options: {
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            suggestedMin: 0,
                            suggestedMax: 60,
                        }
                    }
                }
            }
        );

        // Search Process
        $('.action-search').click(function () {
            var from = $datetimepickerForm.val();
            var to = $datetimepickerTo.val();
            var category = $category.val();
            $.ajax({
                url: "./",
                data: {'route': "get", 'from': from, 'to': to, 'category': category},
                success: function(data) {

                    // console.log(data.chart)
                    // Chart Loss
                    chartLoss.config.data = {
                        labels: data.chart.loss.labels,
                        datasets: [{
                            label: 'Loss (%)',
                            backgroundColor: 'rgb(255, 99, 132)',
                            borderColor: 'rgb(255, 99, 132)',
                            data: data.chart.loss.data
                        }]
                    };;
                    chartLoss.update();
                    // Chart Worst
                    chartWorst.config.data = {
                        labels: data.chart.worst.labels,
                        datasets: [{
                            label: 'Worst (ms)',
                            fill: false,
                            backgroundColor: 'rgb(75, 192, 192)',
                            borderColor: 'rgb(75, 192, 192)',
                            tension: 0.1,
                            data: data.chart.worst.data
                        }]
                    };;
                    chartWorst.update();
                    // Enable chart display
                    $("#chartLossContainer").fadeIn();
                    $("#chartWorstContainer").fadeIn();

                    // DataTables
                    datatable = $('#mtrTable').DataTable( {
                        "destroy": true,
                        // "scrollX": true,
                        // "scrollCollapse": true,
                        "autoWidth": false,
                        "data": data.table,
                        "columns": [
                            {
                                "className": 'tables-details-control',
                                "orderable": false,
                                "data": null,
                                "defaultContent": '<i class="fa fa-plus fa-xs" aria-hidden="true"></i>'
                            },
                            { "data": 'start_datetime'},
                            { "data": 'end_datetime'},
                            { "data": 'period' },
                            { "data": 'category' },
                            { "data": 'source' },
                            { "data": 'destination' },
                            { "data": 'mtr_loss' },
                            { "data": 'mtr_sent' },
                            { "data": 'mtr_avg' },
                            { "data": 'mtr_best' },
                            { "data": 'mtr_worst' }
                        ],
                        "order": [[ 1, "desc" ]],
                        "initComplete": function(settings, json) {
                            $("#tableBlock").fadeIn();
                        }
                    } );
                }
            });
        });

        // Add event listener for opening and closing details
        $('#mtrTable').on('click', 'tbody td.tables-details-control', function () {
            console.log('dff')
            var tr = $(this).closest('tr');
            var row = datatable.row( tr );
    
            if ( row.child.isShown() ) {
                // This row is already open - close it
                row.child.hide();
                tr.removeClass('shown');
                $(this).html('<i class="fa fa-plus fa-xs" aria-hidden="true"></i>');
            }
            else {
                // Open this row
                row.child( BuildTablesDetail(row.data()) ).show();
                tr.addClass('shown');
                $(this).html('<i class="fa fa-minus fa-xs" aria-hidden="true"></i>');
            }
        });

        function BuildTablesDetail ( data ) {
            // console.log(data);
            var raw = JSON.parse(data.mtr_raw);
            console.log(raw);

            // Check raw
            if (typeof raw.report.hubs === 'undefined') {
                return '';
            }

            // Other info
            var htmlOther = '<table class="table table-striped table-bordered" style="padding-left:50px;">'+
                '<tr>'+
                    '<td>Command:</td>'+
                    '<td>'+data.command+'</td>'+
                '</tr>'+
                '<tr>'+
                    '<td>Target Host:</td>'+
                    '<td>'+data.host+'</td>'+
                '</tr>'+
            '</table>';

            // Hubs info
            var htmlHubs = '<table class="table table-striped table-bordered" style="padding-left:50px;">'+
                '<tr>'+
                    '<td>Node</td>'+
                    '<td>Host</td>'+
                    '<td>Loss(%)</td>'+
                    '<td>Count</td>'+
                    '<td>Avg</td>'+
                    '<td>Best</td>'+
                    '<td>Wrst</td>'+
                    '<td>StDev</td>'+
                '</tr>';

            $.each(raw.report.hubs, function( index, hub  ) {
                htmlHubs += '<tr>'+
                    '<td>'+ hub['count'] +'</td>'+
                    '<td>'+ hub['host'] +'</td>'+
                    '<td>'+ hub['Loss%'] +'</td>'+
                    '<td>'+ hub['Snt'] +'</td>'+
                    '<td>'+ hub['Avg'] +'</td>'+
                    '<td>'+ hub['Best'] +'</td>'+
                    '<td>'+ hub['Wrst'] +'</td>'+
                    '<td>'+ hub['StDev'] +'</td>'+
                '</tr>';
            }); 

            htmlHubs += '</table>';

            return htmlOther + htmlHubs;
        }
    });
</script>
</body>
</html>


