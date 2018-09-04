<?php
require_once '../koneksi.php';
$data = json_decode(file_get_contents('php://input'), true);

$username = $data['username'];
$password = $data['password'];
$role = 'USER';

$sql = "INSERT INTO `users`(`username`, `password`, `role`) VALUES ('$username', '$password', '$role')";
$query = $db->query($sql);
if (!$query) {
    $error = $db->errorInfo();
    if ($error[0] === "23000") {
        echo json_encode(['status' => 'fail', 'error' => 'Username has been taken.']);
    }
    http_response_code(400);
    die();
} else {
    http_response_code(200);
    echo json_encode(['status' => 'OK']);
}
