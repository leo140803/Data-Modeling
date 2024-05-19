<?php 
    require 'vendor/autoload.php';

    use Predis\Client;
    $redis = new Client();
    if($_SERVER["REQUEST_METHOD"] == "POST"){
        if (!empty($_FILES['file'])) {
            $fileTmpPath = $_FILES['file']['tmp_name'];
            $csv= fopen($fileTmpPath, 'r');
            $headers= fgetcsv($csv, 0, ',');
            foreach($headers as $header){
                if($header == 'dt'){
                    continue;
                };
                $redis->executeRaw(['DEL',$header]);
                $redis->executeRaw(['DEL',$header.'agr']);
                $redis->executeRaw(['TS.CREATE',$header, 'LABELS', 'data', 'raw']);
                $redis->executeRaw(['TS.CREATE',$header.'agr', 'LABELS', 'data', 'agr']);
                if(str_contains($header,'Average')){
                    $redis->executeRaw(['TS.CREATERULE',$header, $header.'agr','AGGREGATION', 'avg',31556952000]);
                }else if(str_contains($header,'Max')){
                    $redis->executeRaw(['TS.CREATERULE',$header, $header.'agr','AGGREGATION', 'max',31556952000]);
                }else if(str_contains($header,'Min')){
                    $redis->executeRaw(['TS.CREATERULE',$header, $header.'agr','AGGREGATION', 'min',31556952000]);
                }else if(str_contains($header,'Sum')){
                    $redis->executeRaw(['TS.CREATERULE',$header, $header.'agr','AGGREGATION', 'sum',31556952000]);
                }else{
                    $redis->executeRaw(['TS.CREATERULE',$header, $header.'agr','AGGREGATION', 'avg',31556952000]);
                }
            }
            while (($data = fgetcsv($csv, 0, ',')) !== false) {
                list($month, $day, $year) = explode('/', $data[0]);
                date_default_timezone_set("UTC");
                $dateString = sprintf('%04d-%02d-%02d 00:00:00', $year, $month, $day);
                $timestamp = (new DateTime($dateString))->getTimestamp() * 1000;
                for ($i = 1; $i < count($data); $i++) {
                    $redis->executeRaw(['TS.ADD', $headers[$i], $timestamp, $data[$i]]);
                }
            }
            $data = [];
            $dataagr = [];

            foreach ($headers as $header) {
                if ($header == 'dt') continue;
                $temp = $redis->executeRaw(['TS.RANGE', $header, '-', '+']);
                foreach ($temp as $i => $entry) {
                    $timestamp = $entry[0] / 1000;
                    $value = round(floatval($entry[1]->getPayload()), 3);

                    if (!isset($data[$i])) {
                        $data[$i][] = gmdate('Y-m-d', $timestamp);
                    }
                    $data[$i][] = $value;
                }
                $tempagr = $redis->executeRaw(['TS.RANGE', $header . 'agr', '-', '+']);
                foreach ($tempagr as $j => $entry) {
                    $timestamp = $entry[0] / 1000;
                    $value = round(floatval($entry[1]->getPayload()), 3);

                    if (!isset($dataagr[$j])) {
                        $dataagr[$j][] = gmdate('Y-m-d', $timestamp);
                    }
                    $dataagr[$j][] = $value;
                }
            }

            fclose($csv);
            echo json_encode(['status'=>'success', 'msg'=>'Success to Upload File!', 'rawData'=> $data, 'agrData'=> $dataagr, 'titles' => $headers]);
            exit;
        }else{
            echo json_encode(['status'=>'failed', 'msg'=>'Please upload a file!']);
            exit;
        }
    }
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Temperature Data Viewer</title>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <style>
        #rawTable, #agrTable {
            width: 500vw !important; 
            overflow-x: auto; 
        }
        table { 
            width: 100%; 
            table-layout: fixed;
        }
        th, td { 
            font-size: 0.8em; 
            padding: 5px; 
            border: 1px solid #ddd; 
        }
    </style>
</head>
<body>
<div class="container mt-2">
    <div class="row">
        <div class="col-12 mb-3">
            <h2 class="text-center">GLOBAL LAND TEMPERATURE</h2>
            <div class="row">
                <div class="col-3">
                    <input type="file" class="form-control-file" id="fileInput" accept=".csv" name="file">
                </div>
                <div class="col-5">
                    <button class="btn btn-primary" style="height: 6vh;" id="upload">upload</button>
                </div>
            </div>
            
        </div>
    </div>

    <ul class="nav nav-tabs">
        <li class="nav-item">
            <a class="nav-link active" data-toggle="tab" href="#rawTable">RAW</a>
        </li>
        <li class="nav-item">
            <a class="nav-link" data-toggle="tab" href="#agrTable">AGR</a>
        </li>
        <li class="nav-item">
            <a class="nav-link" data-toggle="tab" href="#rawChart">RAW Chart</a>
        </li>
        <li class="nav-item">
            <a class="nav-link" data-toggle="tab" href="#agrChart">AGR Chart</a>
        </li>
    </ul>
    <div class="tab-content">
        <div id="rawTable" class="tab-pane fade show active" style="width: 25vw;">
            <h3>Raw Table</h3>
        </div>
        <div id="agrTable" class="tab-pane fade">
            <h3>AGR Table</h3>
        </div>
        <div id="rawChart" class="tab-pane fade">
            <h3>Raw Chart</h3>
            <canvas id="rawChartCanvas"></canvas>
        </div>
        <div id="agrChart" class="tab-pane fade">
            <h3>AGR Chart</h3>
            <canvas id="agrChartCanvas"></canvas>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.2/dist/umd/popper.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    function getRandomColor() {
    var letters = '0123456789ABCDEF';
    var color = '#';
    for (var i = 0; i < 6; i++) {
        color += letters[Math.floor(Math.random() * 16)];
    }
    return color;
}

</script>
<script>
    $(document).ready(function(){
    $("#upload").click(function(){
        var fileData = $("#fileInput").prop('files')[0];
        var formData = new FormData();
        formData.append("file", fileData);
        $.ajax({
            type: 'POST', 
            data: formData,
            contentType: false,
            processData: false, 
            success: function(response) {
                var response= JSON.parse(response);
                if(response.status == 'success'){
                    Swal.fire({
                        title: response.status === 'success' ? 'Success' : 'Error',
                        icon: response.status,
                        text: response.msg
                    });
                    var headers = response.titles;
                    var data = response.rawData;
                    var agrData= response.agrData;

                    var html = "<thead><tr>";
                    headers.forEach(function(header){
                        html += "<th>" + header + "</th>";
                    });
                    html += "</tr></thead><tbody>";
                    data.forEach(function(row){
                        html += "<tr>";
                        row.forEach(function(cell){
                            html += "<td>" + cell + "</td>";
                        });
                        html += "</tr>";
                    });
                    html += "</tbody>";
                    $("#rawTable").html(html);

                    

                    var html2 = "<thead><tr>";
                    headers.forEach(function(header){
                        html2 += "<th>" + header + "</th>";
                    });
                    html2 += "</tr></thead><tbody>";
                    agrData.forEach(function(row){
                        html2 += "<tr>";
                        row.forEach(function(cell){
                            html2 += "<td>" + cell + "</td>";
                        });
                        html2 += "</tr>";
                    });
                    html2 += "</tbody>";
                    $("#agrTable").html(html2);


                    var labels = data.map(function(item) {
                        return item[0];
                    });

                    var datasets = [];
                    for (var i = 1; i < headers.length; i++) {
                        var dataSeries = data.map(function(item) {
                            return item[i];
                        });
                        datasets.push({
                            label: headers[i],
                            data: dataSeries,
                            fill: false,
                            borderColor: getRandomColor()
                        });
                    }

                    var chartData = {
                        labels: labels,
                        datasets: datasets
                    };

                    var ctx = document.getElementById('rawChartCanvas').getContext('2d');
                    new Chart(ctx, {
                        type: 'line',
                        data: chartData,
                        options: {
                            scales: {
                                y: {
                                    beginAtZero: true
                                }
                            }
                        }
                    });

                    var labels = agrData.map(function(item) {
                        return item[0]; 
                    });

                    var datasets = [];
                    for (var i = 1; i < headers.length; i++) {
                        var dataSeries = agrData.map(function(item) {
                            return item[i];
                        });
                        datasets.push({
                            label: headers[i],
                            data: dataSeries,
                            fill: false,
                        });
                    }

                    var chartData = {
                        labels: labels,
                        datasets: datasets
                    };

                    var ctx = document.getElementById('agrChartCanvas').getContext('2d');
                    new Chart(ctx, {
                        type: 'line', 
                        data: chartData,
                        options: {
                            scales: {
                                y: {
                                    beginAtZero: true
                                }
                            }
                        }
                    })
                    
                }else{
                    Swal.fire({
                                title : 'Error!',
                                text : response.msg,
                                icon : 'error',
                    });
                };
                },
                error: function(xhr, status, error) {
                            alert("Error: " + xhr.responseText);
                        }
                    });
                });

                
});
</script>
</body>
</html>