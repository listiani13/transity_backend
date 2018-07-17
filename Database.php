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
    public function getAvailableDestination($start_time, $availability_time, $id_data = null)
    {
        $end_time = date('H:i:s', strtotime($start_time) + ($availability_time * 3600));
        if ($id_data != null) {
            $json_jarak = json_decode($this->getJarak($id_data, 'AWAL')['json_jarak'], true);
        } else {
            $json_jarak = json_decode(file_get_contents("json_jarak.json"), true);
        }
        $selected_dest = [];
        $available_destination = [];
        $distance_array = $json_jarak["c1"];

        if ($availability_time <= 6) {
            foreach ($distance_array as $key => $value) {
                if ($value <= 32.5) {
                    array_push($available_destination, str_replace("c", "", $key));
                }
            }
            $available_destination = "'" . join("','", $available_destination) . "'";

            $sql = "SELECT dest_id FROM dest WHERE dest_id IN($available_destination)  AND ((opening_time<=CAST('$start_time' AS time) AND closing_time >= CAST('$end_time' AS time)) OR (open_24h = 'Y')) ";

            $query = $this->db->query($sql);
            $res = $query->fetchAll(PDO::FETCH_ASSOC);
            foreach ($res as $line) {
                array_push($selected_dest, $line['dest_id']);
            }

        } else {
            $selected_dest = $this->selectObjekWisataAll($start_time, $end_time);
        }
        return $selected_dest;
    }
    public function selectObjekWisataAll($start_time, $end_time)
    {
        $sql = "SELECT dest_id FROM dest WHERE ((
                opening_time<=CAST('$start_time' AS time)
                AND (closing_time > CAST('$end_time' AS time) AND opening_time > closing_time)
            ) OR (open_24h = 'Y'))";
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

$db = new Database();
$db->getAvailableDestination('13:00', 12);
