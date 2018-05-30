<?php
require_once '../koneksi.php';
$data = json_decode(file_get_contents('php://input'), true);
$dest_id = $data['dest_id'];
$sql = "DELETE FROM `dest` WHERE dest_id = $dest_id";

$query = $db->query($sql);
if ($query) {
    echo json_encode(["status" => "OK"]);
} else {
    echo json_encode(["status" => "Failed"]);
}
