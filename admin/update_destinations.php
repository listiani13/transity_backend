<?php
require_once '../koneksi.php';
$data = json_decode(file_get_contents('php://input'), true);
$dest_id = $data['dest_id'];
$dest_name = $data['dest_name'];
$lat = $data['lat'];
$lng = $data['lng'];

$sql = "UPDATE `dest` SET `dest_name`='$dest_name',`lat`='$lat',`lng`='$lng' WHERE dest_id = $dest_id";

$query = $db->query($sql);
if ($query) {
    echo json_encode(["status" => "OK"]);
} else {
    echo json_encode(["status" => "Failed"]);
}
