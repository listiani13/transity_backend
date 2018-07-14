<?php
spl_autoload_register(function ($class_name) {
    include $class_name . '.php';
});
class Individu extends Utils
{
    public function __construct($time, $cities_amount, $objek_wisata, $digit, $id_data = null)
    {
        // define('API_KEY', 'AIzaSyBTE9O-ina1ZgUJgu9P4kN66etZyjErqYw');
        $this->time = $time;
        $this->chrom_length = $cities_amount;
        $this->objek_wisata = $objek_wisata;
        $this->digit = $digit;
        // $this->time = 4;
        // $this->chrom_length = 2;
        // $this->objek_wisata = [1, 2, 3, 4, 5, 6, 7, 8, 9];
        // $this->digit = 4;
        $this->id_origin = 1;
        $this->velocity = 40;
        $this->id_data = $id_data;
        $this->waktu_kunjung = 60;
        $servername = "localhost";
        $username = "root";
        $password = "";
        $this->db = new PDO("mysql:host=$servername;dbname=ga_test", $username, $password);

        // Ini harusnya dipindah ke Generasi
        // $lat = "-8.634864";
        // $lang = "115.192476";
        // $this->insertDistance($this->id_data, $lat, $lang);
    }

    public function generateChrom()
    {
        static $recursion_depth = 0;
        $chrom_binary = [];
        $chrom_int = [];
        $chrom_details = [];
        $time = $this->dectobin($this->time, $this->digit);
        $origin_binary = $this->dectobin($this->id_origin, $this->digit);
        $chrom_binary = array_merge($chrom_binary, $time, $origin_binary);
        array_push($chrom_int, $this->id_origin);
        for ($i = 0; $i < $this->chrom_length; $i++) {
            $index_randomized_city = array_rand($this->objek_wisata);
            $chosen_destination = $this->objek_wisata[$index_randomized_city];
            $randomized_city = $this->checkIfSame($chrom_int, $chosen_destination, $this->objek_wisata, $this->chrom_length);
            array_push($chrom_int, $randomized_city);
            $chrom_binary = array_merge($chrom_binary, $this->dectobin($randomized_city, $this->digit));
        }
        array_push($chrom_int, $this->id_origin);
        $chrom_binary = array_merge($chrom_binary, $origin_binary);
        $fitness = $this->generateFitnessFunction($chrom_int);
        if ($fitness !== false) {
            # Jika lulus verifikasi fitness
            array_push($chrom_binary, $fitness);
        } else {
            $recursion_depth++;
            if ($recursion_depth < 7000) {
                $chrom_binary = $this->generateChrom();
            } else {
                $recursion_depth = 0;
                return false;
            }
        }
        return $chrom_binary;
    }
    public function generateFitnessFunction($cities)
    {
        $total_distance = $this->getDistance($cities, $this->id_data);
        $total_minutes = ($total_distance / $this->velocity) * 60;
        $minutes_allowed = ($this->time * 60) - ($this->waktu_kunjung * $this->chrom_length);
        if ($total_minutes > $minutes_allowed) {
            return false;
        } else {
            $fitness = sprintf("%.10f", 1 / $total_minutes);
            return $fitness;
        }
    }
    public function checkIfSame($arr, $dest, $selection, $cities_visited)
    {
        // echo "selection<br>";
        // var_dump($selection);
        if (sizeof($selection) < $cities_visited) {
            echo json_encode(["status" => "error", "error" => "Sorry! No route is available for this time."]);
            die();
        }
        if (array_search($dest, $arr) !== false) {
            $new_index = array_rand($selection);
            $new_dest = $this->checkIfSame($arr, $selection[$new_index], $selection, $cities_visited);
            return $new_dest;
            // die();
        } else {
            return $dest;
        }
    }
}

// $individu = new Individu();
// // $individu->generateChrom();
// $lat = "-8.634864";
// $lang = "115.192476";
// $id = 'D001';
// echo json_encode($individu->generateChrom());
// // echo var_dump($individu->array_flat_try());
// // var_dump($individu->generateChrom());
