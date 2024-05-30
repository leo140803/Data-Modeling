<?php
require 'vendor/autoload.php';

$client = new MongoDB\Client("mongodb://localhost:27017");
$resto = $client->latihan->restaurants;

if(isset($_POST["filter"])) {
    $filter = $_POST['filter'];
    $query = [];

    // Handle borough filter
    if (!empty($filter['borough']) && $filter['borough'] !== 'all') {
        $query['borough'] = ['$in' => $filter['borough']];
    }

    // Handle cuisine filter
    if (!empty($filter['cuisine'])) {
        $query['cuisine'] = ['$regex' => $filter['cuisine'], '$options' => 'i'];
    }

    // Handle grade score filter
    if (!empty($filter['grade'])) {
        $query['grades.0.score'] = ['$lt' => intval($filter['grade'])];
    }

    $cursor = $resto->find($query);
    $data = [];

    foreach ($cursor as $restaurant) {
        $data[] = [
            'restaurant_id' => $restaurant['restaurant_id'],
            'name' => $restaurant['name'],
            'address' => $restaurant['address']['street'] ?? 'N/A',
            'borough' => $restaurant['borough'],
            'cuisine' => $restaurant['cuisine'],
            'last_grades' => $restaurant['grades'][0]['score'] . ' (' . $restaurant['grades'][0]['grade'] . ')'
        ];
    }

    echo json_encode($data);
    exit;
}
?>

<!DOCTYPE html>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Restaurant Grades</title>
    <!-- jQuery -->
    <script type="text/javascript" charset="utf8" src="https://code.jquery.com/jquery-3.5.1.js"></script>
    <!-- DataTables CSS -->
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.12.1/css/jquery.dataTables.css">
    <!-- DataTables -->
    <script type="text/javascript" charset="utf8" src="https://cdn.datatables.net/1.12.1/js/jquery.dataTables.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/choices.js/public/assets/styles/choices.min.css">
    <script src="https://cdn.jsdelivr.net/npm/choices.js/public/assets/scripts/choices.min.js"></script>
</head>
<body>
    <div class="container mt-5">
        <div class="row mb-3">
            <div class="col">
                <label for="borough" class="form-label">Filter Borough</label>
                <select class="form-select" id="borough" multiple>
                    <?php foreach($resto->distinct("borough") as $borough): ?>
                        <option value="<?php echo htmlspecialchars($borough); ?>"><?php echo htmlspecialchars($borough); ?></option>
                    <?php endforeach; ?>
                </select>


            </div>
            <div class="col">
                <label for="cuisine" class="form-label">Filter Cuisine</label>
                <input class="form-control" placeholder="Ex. Bakery" id="cuisine" type="text">
            </div>
            <div class="col">
                <label for="grade" class="form-label">Filter Grade's score (less than)</label>
                <input class="form-control" placeholder="Input numeric only" type="number" id="grade">
            </div>
        </div>

        <table class="table table-striped table-bordered" id="dataTable">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Address</th>
                    <th>Borough</th>
                    <th>Cuisine</th>
                    <th>Last Grades</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($cursor as $restaurant): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($restaurant->restaurant_id); ?></td>
                        <td><?php echo htmlspecialchars($restaurant->name); ?></td>
                        <td><?php echo htmlspecialchars($restaurant->address->street ?? 'N/A'); ?></td>
                        <td><?php echo htmlspecialchars($restaurant->borough); ?></td>
                        <td><?php echo htmlspecialchars($restaurant->cuisine); ?></td>
                        <td><?php echo htmlspecialchars($restaurant->grades[0]->score. ' (' . $restaurant->grades[0]->grade . ")" ); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

    </div>
    <script>
        $(document).ready(function(){
            var element = document.getElementById('borough');
            var choices = new Choices(element, {
                removeItemButton: true, // Allows for removal of items
                searchEnabled: true, // Enable searching within the select
                placeholderValue: 'Select boroughs',
                shouldSort: false // Optional: turn off sorting if you prefer
            });

            $('#dataTable').DataTable({
                "pagingType": "simple_numbers",
                "order": [[0, "asc"]],
                "lengthMenu": [10, 25, 50, 100],
                "searching": false, 
            });

            $("#borough").change(function(event){
                var borough = $("#borough").val();
                var cuisine= $("#cuisine").val();
                var grade= $("#grade").val();
                $.ajax({
                    method: "POST",
                    data: {
                        filter: [borough, cuisine, grade]
                    },
                    success: function(response){
                        // var res= JSON.parse(response);
                        console.log(response);
                    }
                })
            })
        });
    </script>
</body>
</html>
