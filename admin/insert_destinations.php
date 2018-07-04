<?php
require_once '../koneksi.php';
$data = json_decode(file_get_contents('php://input'), true);

$dest_name = $data['dest_name'];
$lat = $data['lat'];
$lng = $data['lng'];
$opening_time = $data['opening_time'];
$closing_time = $data['closing_time'];

$API_KEY = "AIzaSyBTE9O-ina1ZgUJgu9P4kN66etZyjErqYw";
$path_file = '../json_jarak.json';

$sql = "INSERT INTO `dest`(`dest_name`, `lat`, `lng`, `opening_time`, `closing_time`) VALUES ('$dest_name', '$lat', '$lng','$opening_time','$closing_time')";
$query = $db->query($sql);
if ($query) {
    $sql_get_id = "SELECT MAX(dest_id) AS dest_id FROM dest";
    $max_dest_id = $db->query($sql_get_id)->fetchAll(PDO::FETCH_ASSOC)[0]['dest_id'];
    $json_jarak = file_get_contents($path_file, 'r');
    $jarak = json_decode($json_jarak, true);

    $allDest = $db->query("SELECT dest_id, lat, lng FROM dest ORDER BY dest_id")->fetchAll(PDO::FETCH_ASSOC);

    $destinations = '';
    foreach ($allDest as $line) {
        $destinations .= $line['lat'] . "," . $line['lng'] . "|";
    }
    $url = "https://maps.googleapis.com/maps/api/distancematrix/json?&origins=" . $lat . "," . $lng . "&destinations=" . $destinations . "&key=" . $API_KEY;
    $response = file_get_contents($url);
    $result = json_decode($response, true);

    $revert_url = "https://maps.googleapis.com/maps/api/distancematrix/json?&origins=" . $destinations . "&destinations=" . $lat . "," . $lng . "&key=" . $API_KEY;
    $revert_response = file_get_contents($revert_url);
    $revert_result = json_decode($revert_response, true);
    if ($revert_result["status"] === 'OK' && $result["status"] === 'OK') {
        $i = 0;
        // untuk dari seluruh destinasi ke cX
        $destIDCollection = [];
        foreach ($jarak as $key => $value) {
            $jarak[$key]["c$max_dest_id"] = number_format($revert_result["rows"][$i]["elements"][0]["distance"]["value"] / 1000, 2, '.', '');
            array_push($destIDCollection, $key);
            $i++;
        }

        // Untuk dari cX ke seluruh destinasi
        for ($i = 0; $i < sizeOf($destIDCollection); $i++) {
            $jarak["c$max_dest_id"][$destIDCollection[$i]] = number_format(($result["rows"][0]["elements"][$i]["distance"]["value"]) / 1000, 2, '.', '');
            // echo $jarak["c12"]["c$num"] . "<br>";
        }
        $data = json_encode($jarak);
        $handle = fopen($path_file, 'w') or die('Cannot open file:  ' . $path_file);
        fwrite($handle, $data);
        fclose($handle);
        echo json_encode(["status" => "OK"]);
    }
    // die('Error Occured');
} else {
    echo json_encode(["status" => "Failed"]);
}

// TODO: Make validator for incoming data
