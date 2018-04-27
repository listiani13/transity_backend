<?php
spl_autoload_register(function ($class_name) {
    include $class_name . '.php';
});

class Utils extends Database
{
    public function dectobin($dec, $digit)
    {
        $bin = sprintf("%0" . $digit . "d", decbin($dec));
        $arr_bin = str_split($bin);
        return $arr_bin;
    }
    public function bintodec($arr_bin)
    {
        $bin = implode("", $arr_bin);
        $dec = bindec($bin);
        return $dec;
    }
    public function checkIfCitySame($arr, $dest)
    {
        $founded_index = array_search($dest, $arr);
        if ($founded_index !== false) {
            return $founded_index;
        }
        return false;
    }
    public function verifikasi($num)
    {
        $json_jarak = file_get_contents("json_jarak.json");
        $json_jarak = json_decode($json_jarak, true);
        $total_jarak = 0;
        if (!isset($json_jarak["c$num"])) {
            return false;
        }
        return true;
    }
    public function getDistance($cities, $id_data = false)
    {
        $total_jarak = 0;
        $ukuran = sizeof($cities);
        $json_jarak = file_get_contents("json_jarak.json");
        $json_jarak = json_decode($json_jarak, true);
        if (!$id_data) {
            for ($i = 0; $i < $ukuran; $i++) {
                if ($i != ($ukuran - 1)) {
                    $a = $i + 1;
                    $origin = $cities[$i];
                    $dest = $cities[$a];
                    $total_jarak += $json_jarak["c$origin"]["c$dest"];
                }
            }
        } else {
            $result_awal = $this->getJarak($id_data, 'AWAL');
            $result_akhir = $this->getJarak($id_data, 'AKHIR');
            // from DB
            $json_jarak_awal = json_decode($result_awal["json_jarak"], true);
            $json_jarak_akhir = json_decode($result_akhir["json_jarak"], true);
            for ($i = 0; $i < $ukuran; $i++) {
                $a = $i + 1;
                if ($i !== ($ukuran - 1)) {
                    $origin = $cities[$i];
                    $dest = $cities[$a];
                    if ($i === 0) {
                        $jarak = $json_jarak_awal["c$origin"]["c$dest"];
                    } else if ($i === ($ukuran - 2)) {
                        $jarak = $json_jarak_akhir["c$origin"]["c$dest"];
                    } else {
                        $jarak = $json_jarak["c$origin"]["c$dest"];
                    }
                    $total_jarak += $jarak;
                }
            }
        }
        return $total_jarak;
    }

    public function insertDistance($id, $lat, $lang)
    {
        // TODO: sebelum push ke git hub delete ini dulu, dan cari cara supaya ini bisa ditaroh di file env
        $API_KEY = "AIzaSyBTE9O-ina1ZgUJgu9P4kN66etZyjErqYw";
        $allDest = $this->selectAllDestinations();
        $allDestAmount = sizeOf($allDest);
        $destinations = '';
        foreach ($allDest as $line) {
            $destinations .= $line['lat'] . "," . $line['lng'] . "|";
        }
        $url = "https://maps.googleapis.com/maps/api/distancematrix/json?&origins=" . $lat . "," . $lang . "&destinations=" . $destinations . "&key=" . $API_KEY;
        $revert_url = "https://maps.googleapis.com/maps/api/distancematrix/json?&origins=" . $destinations . "&destinations=" . $lat . "," . $lang . "&key=" . $API_KEY;
        $response = file_get_contents($url);
        $revert_response = file_get_contents($revert_url);
        $result = json_decode($response, true);
        $revert_result = json_decode($revert_response, true);

        $arr_data = [];
        $arr_isi = [];
        $arr_revert_data = [];
        $arr_revert_isi = [];
        // INSERT Ke list_origin utk tahu history request distance jsonnya.
        $this->insertOriginHistory($id, $lat, $lang);
        if ($result["status"] === 'OK') {
            for ($i = 0; $i < $allDestAmount; $i++) {
                $num = $i + 2;
                $arr_isi["c$num"] = ($result["rows"][0]["elements"][$i]["distance"]["value"]) / 1000;
            }
            $arr_data["c1"] = $arr_isi;
            $json_data = json_encode($arr_data);
            $this->insertJarak($id, 'AWAL', $json_data);
        }
        if ($revert_result["status"] === 'OK') {
            for ($i = 0; $i < $allDestAmount; $i++) {
                $num = $i + 2;
                $arr_revert_data["c$num"] = ["c1" => ($revert_result["rows"][$i]["elements"][0]["distance"]["value"]) / 1000];
            }
            $json_data = json_encode($arr_revert_data);
            $this->insertJarak($id, 'AKHIR', $json_data);
        }

    }
}

// $uti = new Utils();
// $lat = "-8.634864";
// $lang = "115.192476";
// $id = "D003";
// $uti->insertDistance($id, $lat, $lang);
