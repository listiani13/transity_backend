<?php
require_once '../koneksi.php';
$data = json_decode(file_get_contents('php://input'), true);
$routeList = json_encode($data['routeList']);
$username = $data['username'];
$sql = "UPDATE users SET routeList=$routeList WHERE username='$username'";
$query = $db->query($sql);
if ($query) {
    echo json_encode(["status" => "OK"]);
} else {
    echo json_encode(["status" => "Failed"]);
}
