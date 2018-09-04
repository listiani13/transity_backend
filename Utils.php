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
    public function verifikasi($num, $json_jarak)
    {
        $total_jarak = 0;
        if (!isset($json_jarak["c$num"])) {
            return false;
        }
        return true;
    }
    public function getDistance($cities, $id_data = false, $json_jarak)
    {
        $total_jarak = 0;
        $ukuran = sizeof($cities);
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
                        // Jarak Awal
                        $jarak = $json_jarak_awal["c$origin"]["c$dest"];
                    } else if ($i === ($ukuran - 2)) {
                        // Jarak Akhir
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
    public function getDistanceEach($cities, $id_data = false, $json_jarak)
    {
        $total_jarak = 0;
        $ukuran = sizeof($cities);
        $data_perjalanan = [];
        if (!$id_data) {
            for ($i = 0; $i < $ukuran; $i++) {
                if ($i != ($ukuran - 1)) {
                    $a = $i + 1;
                    $origin = $cities[$i];
                    $dest = $cities[$a];
                    array_push($data_perjalanan, $json_jarak["c$origin"]["c$dest"]);
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
                    array_push($data_perjalanan, (float) $jarak);
                }
            }
        }
        return $data_perjalanan;
    }

    public function insertDistance($id, $lat, $lang)
    {
        // TODO: sebelum push ke git hub delete ini dulu, dan cari cara supaya ini bisa ditaroh di file env
        $API_KEY = "AIzaSyBTE9O-ina1ZgUJgu9P4kN66etZyjErqYw";
        // $API_KEY = "AIzaSyCaU7m2QP8JQF0Rj1NJcNRVP1nyLYSxVkk";
        // $API_KEY = "AIzaSyBdrx8x273DDhR1bbTzwZ7AjpfGW36_x-8";
        // $API_KEY = "AIzaSyByHOU2umJ6YMe4tiEYHcwX-qzg1PsADVo";
        // $API_KEY = "AIzaSyB_7NvBzfMDI34tGPnzKZOGwgRxgXm2ZFc";
        // $API_KEY = "AIzaSyCqxZ82BAwgW97NCoAwWnpe48e_vwNOfLU";
        // $API_KEY = "AIzaSyDZZUvznbJ4w3Nng5nijzsrln5EtAHZ1lI";
        // $API_KEY = "AIzaSyDuOjMjbRZLNxJKxRAAUwj7ZEvE-pQtEBQ";
        // $API_KEY = "AIzaSyB6agDsuEVTrmXUt8QqP6Ux4IcQazTsiSc";

        $allDest = $this->selectAllDestinations();
        // TODO: Ini ga penting
        $allDestAmount = sizeOf($allDest);

        // ORIGIN GET DISTANCE
        $jarak_awal = getDistance($allDest, $lat, $lang, $API_KEY, 'AWAL');
        $jarak_akhir = getDistance($allDest, $lat, $lang, $API_KEY, 'AKHIR');
        try {
            $this->insertJarak($id, 'AWAL', $jarak_awal);
            $this->insertJarak($id, 'AKHIR', $jarak_akhir);
            $this->insertOriginHistory($id, $lat, $lang);

        } catch (Exception $e) {
            echo json_encode(["success" => "error", "error" => $e->getMessage()]);
            die();
        }

    }
}
function getDistance($allDest, $lat, $lang, $API_KEY, $type)
{
    $destinations = [];
    $destinations_id = [];
    $arr_dest = [];
    $destination_id_collections = [];
    $index_arr_dest = 0;
    for ($i = 0; $i < sizeOf($allDest); $i++) {
        $lat_lng = [(float) $allDest[$i]['lat'], (float) $allDest[$i]['lng']];
        array_push($destinations_id, $allDest[$i]['dest_id']);
        array_push($destinations, "enc:" . Polyline::encode($lat_lng) . ":|");
        if (($i % 59 === 0 && $i !== 0) || $i === sizeOf($allDest) - 1) {
            array_push($arr_dest, implode("", $destinations));
            array_push($destination_id_collections, $destinations_id);
            $destinations = [];
            $destinations_id = [];
        }
    }

    $index = 0;
    $arr_data = [];
    $arr_isi = [];
    $encoded_destination_counter = 0;
    foreach ($arr_dest as $encoded_destination) {

        if ($type === 'AWAL') {
            $url = "https://maps.googleapis.com/maps/api/distancematrix/json?&origins=" . $lat . "," . $lang . "&destinations=" . $encoded_destination . "&key=" . $API_KEY;
            $response = file_get_contents($url);
            $result = json_decode($response, true);
            if ($result["status"] === 'OK') {
                for ($i = 0; $i < sizeOf($destination_id_collections[$encoded_destination_counter]); $i++) {
                    $dest_id = $destination_id_collections[$encoded_destination_counter][$i];
                    $arr_isi["c$dest_id"] = ($result["rows"][0]["elements"][$i]["distance"]["value"]) / 1000;
                }

                array_push($arr_data, ["c1" => $arr_isi]);
                // $json_data = json_encode($arr_data);
                $arr_isi = [];
            } else {
                echo json_encode(["status" => "fail", "error" => "Get Distance from Current Location failed.", "debug" => "status not oK akhir" . json_encode($result)]);
                http_response_code(500);
                die();
            }

        } elseif ($type === 'AKHIR') {
            $revert_url = "https://maps.googleapis.com/maps/api/distancematrix/json?&origins=" . $encoded_destination . "&destinations=" . $lat . "," . $lang . "&key=" . $API_KEY;
            $response = file_get_contents($revert_url);

            $result = json_decode($response, true);
            if ($result["status"] === 'OK') {
                for ($i = 0; $i < sizeOf($destination_id_collections[$encoded_destination_counter]); $i++) {
                    $dest_id = $destination_id_collections[$encoded_destination_counter][$i];
                    $arr_isi["c$dest_id"] = ["c1" => ($result["rows"][$i]["elements"][0]["distance"]["value"]) / 1000];
                }

                array_push($arr_data, $arr_isi);
                $arr_isi = [];
            } else {
                echo json_encode(["status" => "fail", "error" => "Get Distance from Current Location failed.", "debug" => "status not oK akhir" . $result["status"]]);
                http_response_code(500);
                die();
            }

        }
        $encoded_destination_counter = $encoded_destination_counter + 1;
    }

    if ($type === 'AWAL') {
        $temp = [];
        $jarak_awal_dest;
        for ($i = 0; $i < sizeOf($arr_data); $i++) {
            $datum = $arr_data[$i]["c1"];
            $jarak_awal_dest = array_merge($temp, $datum);
            $temp = $arr_data[$i]["c1"];
        }
        $json_data = json_encode(["c1" => $jarak_awal_dest]);
    } elseif ($type === 'AKHIR') {
        $temp = [];
        $jarak_akhir_dest;
        foreach ($arr_data as $line) {
            $datum = $line;
            $jarak_akhir_dest = array_merge($temp, $datum);
            $temp = $line;
        }
        $json_data = json_encode($jarak_akhir_dest);
    }
    return $json_data;
}

// $uti = new Utils();
// $lat = "-8.634864";
// $lang = "115.192476";
// $id = "D003";
// $uti->insertDistance($id, $lat, $lang);
