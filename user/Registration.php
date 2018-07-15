<?php
require_once '../koneksi.php';
$data = json_decode(file_get_contents('php://input'), true);

$username = $data['username'];
$password = $data['password'];
$role = 'USER';

$sql = "INSERT INTO `users`(`username`, `password`, `role`) VALUES ('$username', '$password', '$role')";
$query = $db->query($sql);

if (!$query) {
    http_response_code(500);
    die();
} else {
    http_response_code(200);
    echo json_encode(['status' => 'OK']);
}
