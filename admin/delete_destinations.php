<?php
require_once '../koneksi.php';
$data = json_decode(file_get_contents('php://input'), true);
$dest_id = $data['dest_id'];
$sql = "DELETE FROM `dest` WHERE dest_id = $dest_id";
$path_file = '../json_jarak.json';
$json_jarak = json_decode(file_get_contents($path_file, 'r'), true);
$query = $db->query($sql);
if ($query) {
    foreach ($json_jarak as $key => $value) {
        if (isset($json_jarak[$key]["c$dest_id"])) {
            unset($json_jarak[$key]["c$dest_id"]);
        }
        if ($key === "c$dest_id") {
            unset($json_jarak[$key]);
        }
    }
    $data = json_encode($json_jarak);
    $handle = fopen($path_file, 'w') or die('Cannot open file:  ' . $path_file);
    fwrite($handle, $data);
    fclose($handle);
    echo json_encode(["status" => "OK"]);
} else {
    echo json_encode(["status" => "Failed"]);
}
