<?php
require_once '../koneksi.php';
$data = json_decode(file_get_contents('php://input'), true);
$dest_id = $data['dest_id'];
$dest_name = $data['dest_name'];
$lat = $data['lat'];
$lng = $data['lng'];
$opening_time = $data['opening_time'];
$closing_time = $data['closing_time'];
$open_24h = '';
if ($opening_time === '00:00' && $closing_time === '00:00') {
    $open_24h = 'Y';
}
$sql = "UPDATE `dest` SET `dest_name`='$dest_name',`lat`='$lat',`lng`='$lng', `opening_time`='$opening_time', `closing_time`='$closing_time', open_24h = '$open_24h' WHERE dest_id = $dest_id";

$query = $db->query($sql);
if ($query) {
    echo json_encode(["status" => "OK"]);
} else {
    echo json_encode(["status" => "Failed"]);
}
