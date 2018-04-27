<?php
class Database
{
    public function __construct()
    {
        // Cara pake disini??
        $servername = "localhost";
        $username = "root";
        $password = "";
        $this->db = new PDO("mysql:host=$servername;dbname=ga_test", $username, $password);
    }

    public function selectData($id)
    {
        $sql = "SELECT * FROM dest WHERE dest_id = $id LIMIT 1";
        $query = $this->db->query($sql);
        return $query;
    }
    public function selectAllDestinations()
    {
        $result = $this->db->query("SELECT dest_id, lat, lng FROM dest WHERE dest_id != '1' ORDER BY dest_id")->fetchAll(PDO::FETCH_ASSOC);
        return $result;
    }
    public function selectObjekWisataAreaA()
    {
        $sql = "SELECT dest_id FROM dest WHERE dest_area = 'A'";
        $query = $this->db->query($sql);
        $res = $query->fetchAll(PDO::FETCH_ASSOC);
        $objek = [];
        foreach ($res as $line) {
            array_push($objek, $line["dest_id"]);
        }
        return $objek;
        // return $this->array_flatten($res);
    }
    public function selectObjekWisataAll()
    {
        $utils = new Utils();
        $sql = "SELECT dest_id FROM dest";
        $query = $this->db->query($sql);
        $res = $query->fetchAll(\PDO::FETCH_ASSOC);
        $objek = [];
        foreach ($res as $line) {
            array_push($objek, $line["dest_id"]);
        }
        return $objek;
    }
    public function getJarak($id_data, $tipe_data_jarak)
    {
        return $this->db->query("SELECT json_jarak FROM jarak WHERE id='$id_data' AND `type`='$tipe_data_jarak'")->fetch(PDO::FETCH_ASSOC);
    }
    public function insertOriginHistory($id, $lat, $lang)
    {

        // $servername = "localhost";
        // $username = "root";
        // $password = "";
        // $db->db = new PDO("mysql:host=$servername;dbname=ga_test", $username, $password);
        $sql = "INSERT INTO list_origin (id, lat, lang) VALUES ('$id', '$lat', '$lang')";
        $this->db->exec($sql);
    }
    public function insertJarak($id, $tipe, $json_data)
    {
        try {
            $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $sql = "INSERT INTO jarak (id, `type`, json_jarak) VALUES ('$id', '$tipe', '$json_data')";
            $this->db->exec($sql);
        } catch (PDOException $e) {
            echo "Error: " . $sql . "<br>" . $e;
        }
    }
}
