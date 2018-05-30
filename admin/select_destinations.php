<?php
require_once '../koneksi.php';
$sql = "SELECT * FROM dest";
$query = $db->query($sql);
if ($query) {
    $result = $query->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($result);
}
