<?php
require_once './koneksi.php';
$data = json_decode(file_get_contents('php://input'), true);

$sql = "SELECT * FROM users WHERE username = '" . $data['username'] . "' AND `password` = '" . $data['password'] . "'";
$query = $db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
if (sizeof($query) > 0) {
    echo json_encode(["status" => "OK", "routeList" => $query[0]['routeList']]);
} else {
    echo json_encode(["status" => "Failed"]);
}
