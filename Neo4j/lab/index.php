<?php
    require_once 'vendor/autoload.php';

    use Laudis\Neo4j\ClientBuilder;

    $client = ClientBuilder::create()
        ->withDriver('default', 'bolt://neo4j:12345678@localhost:7687')
        ->build();

    $queryGetCompany = "MATCH (s:Supplier) RETURN s.companyName as companyName";
    $company = $client->run($queryGetCompany);

    if (isset($_POST['filter'])) {
        $filter = $_POST['filter'];
        $query = "MATCH (s1:Supplier)-[:SUPPLIES]->(p1:Product)-[:PART_OF]->(c:Category)<-[:PART_OF]-(p2:Product)<-[:SUPPLIES]-(s2:Supplier)
        WHERE s1.companyName = '$filter' AND s1 <> s2
        RETURN s2.companyName as Competitor, count(s2) as NoProducts
        ORDER BY NoProducts DESC";
        $params = ['companyName' => $filter];
        $result = $client->run($query, $params);
        $data = [];
        foreach ($result as $res) {
            $data[] = [$res->get('Competitor'), $res->get('NoProducts')];
        }
        if ($data == []) {
            echo json_encode(['data' => 'No Data!']);
        } else {
            echo json_encode(['data' => $data]);
        }
        exit;
    }
    
?>

<!DOCTYPE html>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Neo4j Lab</title>
    <!-- jQuery -->
    <script type="text/javascript" charset="utf8" src="https://code.jquery.com/jquery-3.5.1.js"></script>
    <!-- DataTables CSS -->
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.12.1/css/jquery.dataTables.css">
    <!-- DataTables -->
    <script type="text/javascript" charset="utf8" src="https://cdn.datatables.net/1.12.1/js/jquery.dataTables.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/choices.js/public/assets/styles/choices.min.css">
    <script src="https://cdn.jsdelivr.net/npm/choices.js/public/assets/scripts/choices.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        .choices__inner{
            background-color: white !important;
            color: black !important;
        }

        option{
            color: black !important;
        }
    </style>

</head>
<body>
    <div class="container mt-5">
        <div class="row mb-4">
            <div class="col-3">
                <label for="selectCompany" class="form-label">Filter Company Name</label>
                <select class="form-select" id="selectCompany" style="color: black !important;">
                    <option value="" disabled selected>Select Company Name</option>
                    <?php
                        foreach($company as $comp){
                            echo '<option value="'.$comp->get('companyName').'">'.$comp->get('companyName').'</option>';
                        }
                    ?>
                </select>
            </div>
        </div>

        <table class="table table-striped table-bordered" id="dataTable">
            <thead>
                <tr>
                    <th>No.</th>
                    <th>Competitor</th>
                    <th>No Product</th>
                </tr>
            </thead>
            <tbody>
            </tbody>
        </table>

    </div>
    <script>
        $(document).ready(function(){
            const element = $("#selectCompany").get(0);
            const choices = new Choices(element, {
                searchEnabled: true,
                itemSelectText: '',
                shouldSort: false,
                removeItemButton: true,
            });

            var table = $('#dataTable').DataTable({  
                columns: [
                    { title: "No.", searchable: false, orderable: false },
                    { title: "Competitor" },
                    { title: "No Product" }
                ]
            });


            $("#selectCompany").change(function() {
                if ($(this).val()) {
                    var filterCompany = $(this).val();
                    $.ajax({
                        method: 'POST',
                        data: { filter: filterCompany },
                        success: function(response) {
                            var res = JSON.parse(response);
                            console.log(res);
                            table.clear();
                            if (res.data !== 'No Data!') {
                                res.data.forEach(function(item, index) {
                                    table.row.add([index + 1, item[0], item[1]]);
                                });
                            }
                            table.draw();
                        }
                    });
                }
            });
        })
    </script>
</body>
</html>
