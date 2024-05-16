<?php

    require 'vendor/autoload.php';

    use Predis\Client;
    $redis = new Client();
    $names= $redis->lrange('names', 0 , -1);
    $length= $redis->llen("names");
    $limit = 10;
    if(isset($_POST["lpush"])){
        if(isset($_POST["nama"]) && $_POST["nama"] != "" && $_POST["nama"] != null){
            if($redis->llen("names") < $limit){
                $redis->lpush("names", $_POST["nama"]);
                echo json_encode(array('message'=> 'Success add '. $_POST["nama"]. ' with LPUSH', 'status' => 'success' ));
                exit;
            }else{
                echo json_encode(array('message'=> 'Limit 10 Data', 'status' => 'error' ));
                exit;
            }
        }else{
            echo json_encode(array('message'=> 'Field nama harus terisi', 'status' => 'error' ));
            exit;
        }
    }
    if(isset($_POST["lpop"])){
        if($redis->llen("names") >= 1){
            $redis->lpop("names");
            echo json_encode(array('message'=> 'Success delete data with LPOP', 'status' => 'success' ));
            exit;
        }else{
            echo json_encode(array('message'=> 'Data is empty', 'status' => 'error' ));
            exit;
        }
    }

    if(isset($_POST["rpop"])){
        if($redis->llen("names") >= 1){
            $redis->rpop("names");
            echo json_encode(array('message'=> 'Success delete data with RPOP', 'status' => 'success' ));
            exit;
        }else{
            echo json_encode(array('message'=> 'Data is empty', 'status' => 'error' ));
            exit;
        }
    }

    if(isset($_POST["rpush"])){
        if(isset($_POST["nama"]) && $_POST["nama"] != "" && $_POST["nama"] != null){
            if($redis->llen("names") < $limit){
                $redis->rpush("names", $_POST["nama"]);
                echo json_encode(array('message'=> 'Success add '. $_POST["nama"]. ' with RPUSH', 'status' => 'success' ));
                exit;
            }else{
                echo json_encode(array('message'=> 'Limit 10 Data', 'status' => 'error' ));
                exit;
            }
        }else{
            echo json_encode(array('message'=> 'Field nama harus terisi', 'status' => 'error' ));
            exit;
        }
    }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <title>Redis Week 1</title>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

</head>

<body>
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card shadow">
                    <div class="card-body rounded">
                        <table class="table table-striped">
                            <thead>
                                <tr class="table-light">
                                    <th scope="col" class="text-center" style="width: 20%;">No</th>
                                    <th scope="col" class="text-center" style="width: 80%;">Name</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($names == null):?>
                                <tr>
                                    <td colspan="2" class="text-center">Belum ada data</td>
                                </tr>
                                <?php else:?>
                                    <?php foreach($names as $index => $name):?>
                                        <tr>
                                            <td class="text-center"><?= $index + 1 ?></td>
                                            <td class="text-center"><?= $name ?></td>
                                        </tr>
                                    <?php endforeach?>
                                <?php endif?>
                            </tbody>
                        </table>
                        <form class="row g-3" id="form">
                            <div class="col-md-12">
                                <input type="text" class="form-control" id="inputNama" placeholder="Input Nama">
                            </div>
                            <div class="col-md-3">
                                <button type="submit" class="btn btn-success w-100" id="lpush">LPUSH</button>
                            </div>
                            <div class="col-md-3">
                                <button type="submit" class="btn btn-danger w-100"  id="lpop">LPOP</button>
                            </div>
                            <div class="col-md-3">
                                <button type="submit" class="btn btn-danger w-100" id="rpop">RPOP</button>
                            </div>
                            <div class="col-md-3">
                                <button type="submit" class="btn btn-success w-100" id="rpush">RPUSH</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        $(document).ready(function(){
            $("#lpush").click(function(event){
                event.preventDefault();
                const name = $("#inputNama").val();
                $.ajax({
                    method: 'POST',
                    data: {lpush: true, nama: name},
                    success: function(response){
                        console.log(response);
                        const resp = JSON.parse(response);
                        Swal.fire({
                            title: resp.status === 'success' ? 'Success' : 'Error',
                            icon: resp.status,
                            text: resp.message
                        }).then(()=>{
                            window.location.reload();
                        })
                    },
                    error: function(){
                        Swal.fire({
                            title: 'Error',
                            icon: 'error',
                            text: 'There was a problem with the AJAX request.'
                        });
                    }
                });
            });

            $("#lpop").click(function(event){
                event.preventDefault();
                $.ajax({
                    method: 'POST',
                    data: {lpop: true},
                    success: function(response){
                        console.log(response);
                        const resp = JSON.parse(response);
                        Swal.fire({
                            title: resp.status === 'success' ? 'Success' : 'Error',
                            icon: resp.status,
                            text: resp.message
                        }).then(()=>{
                            window.location.reload();
                        })
                    },
                    error: function(){
                        Swal.fire({
                            title: 'Error',
                            icon: 'error',
                            text: 'There was a problem with the AJAX request.'
                        });
                    }
                });
            });

            $("#rpop").click(function(event){
                event.preventDefault();
                $.ajax({
                    method: 'POST',
                    data: {rpop: true},
                    success: function(response){
                        console.log(response);
                        const resp = JSON.parse(response);
                        Swal.fire({
                            title: resp.status === 'success' ? 'Success' : 'Error',
                            icon: resp.status,
                            text: resp.message
                        }).then(()=>{
                            window.location.reload();
                        })
                    },
                    error: function(){
                        Swal.fire({
                            title: 'Error',
                            icon: 'error',
                            text: 'There was a problem with the AJAX request.'
                        });
                    }
                });
            });

            $("#rpush").click(function(event){
                event.preventDefault();
                const name = $("#inputNama").val();
                $.ajax({
                    method: 'POST',
                    data: {rpush: true, nama: name},
                    success: function(response){
                        console.log(response);
                        const resp = JSON.parse(response);
                        Swal.fire({
                            title: resp.status === 'success' ? 'Success' : 'Error',
                            icon: resp.status,
                            text: resp.message
                        }).then(()=>{
                            window.location.reload();
                        })
                    },
                    error: function(){
                        Swal.fire({
                            title: 'Error',
                            icon: 'error',
                            text: 'There was a problem with the AJAX request.'
                        });
                    }
                });
            });
        })
    </script>
</body>
</html>
