<?php
include 'Individu.php';
class Generasi
{
    public function __construct($pops, $time, $start_time, $cities_visited)
    {
        define('MUTATION_RATE', 0.1);
        define('CROSSOVER_RATE', 0.5);
        $API_KEY = "AIzaSyBTE9O-ina1ZgUJgu9P4kN66etZyjErqYw";
        $this->digit = 7;
        $this->time = $time;
        $this->id_data = null;
        $this->origin_name = "Ngurah Rai";

        $this->database = new Database();
        $this->utils = new Utils();

        if (isset($_GET['lang']) && isset($_GET['lat'])) {
            $this->id_data = uniqid();
            $lat = $_GET['lat'];
            $lang = $_GET['lang'];
            // DEBUGGING PURPOSE, PLEASE DELETE THIS SOON
            // $lat = "-8.634864";
            // $lang = "115.192476";
            $url = "https://maps.googleapis.com/maps/api/geocode/json?latlng=" . $lat . "," . $lang . "&key=" . $API_KEY;
            $response = file_get_contents($url);
            $result = json_decode($response, true);
            $this->origin_name = $result["results"][0]["formatted_address"];
            $this->utils->insertDistance($this->id_data, $lat, $lang);
        }
        $this->objek_wisata = $this->database->getAvailableDestination($start_time, $time, $this->id_data);
        $json_jarak = file_get_contents("json_jarak.json");
        $this->json_jarak = json_decode($json_jarak, true);
        $this->individu = new Individu($time, $cities_visited, $this->objek_wisata, $this->digit, $this->id_data, $this->json_jarak);

        // TODO: Belum termasuk jam
        define('BATAS_AWAL', $this->digit * 2);
        $this->utils = new Utils();
        $this->population = $pops;
        $this->cities_visited = $cities_visited;

        $this->velocity = 40;

    }
    public function getDestinationDetail($id)
    {
        $name = $this->database->selectData($id)->fetch();
        $name_url = str_replace(" ", "+", $name);
        return $name_url;
    }
    public function runGAAll($counter)
    {
        $first_pop = '';
        for ($i = 0; $i < $counter; $i++) {
            try {
                $first_pop = $this->runGA($first_pop);
            } catch (Exception $e) {
                // echo $e->getMessage() . "<br>";
                // break;
                http_response_code(404);
                die();
            }
        }
        // Convert hasil
        if ($first_pop != null || $first_pop != '') {
            $arr_sel_pop = array_slice($first_pop, 0, -1);
            $b = sizeof($arr_sel_pop);
            $chrom_int = [];
            $chrom_name = [];
            $chrom_position = [];
            $destination_description = [];
            $i = 0;
            $utils = new Utils();
            $dec = 0;
            while ($i < $b) {
                $binary_array = array_slice($first_pop, $i, $this->digit);
                // To get last index of array
                if ($i === sizeof($first_pop) - $this->digit - 1 || $i === $this->digit) {
                    $name = $this->origin_name;
                    $dec = 1;
                    if (isset($_GET['lat']) && isset($_GET['lang'])) {
                        $latitude = $_GET['lat'];
                        $longitude = $_GET['lang'];
                    } else {
                        $latitude = '-8.746717';
                        $longitude = '115.166786';
                    }
                    array_push($chrom_position, ["latitude" => $latitude, "longitude" => $longitude]);
                    array_push($destination_description, "Starting Point");
                } else if ($i !== 0) {
                    $dec = $this->bintodec($binary_array);
                    $destination_detail = $this->getDestinationDetail($dec);
                    $name = $destination_detail['dest_name'];
                    $latitude = $destination_detail['lat'];
                    $longitude = $destination_detail['lng'];
                    array_push($chrom_position, ["latitude" => $latitude, "longitude" => $longitude]);
                    array_push($destination_description, $destination_detail['description']);

                }
                if ($i !== 0) {
                    array_push($chrom_int, $dec);
                    array_push($chrom_name, str_replace('+', ' ', $name));
                }
                $i += $this->digit;
            }
            $data_jarak = $utils->getDistanceEach($chrom_int, $this->id_data, $this->json_jarak);

            $json_final = ["availability_time" => $this->time, "destination" => $chrom_name, "position" => $chrom_position, "destination_description" => $destination_description, "travel_distance" => $data_jarak, "total_travel_minutes" => 1 / end($first_pop)];
            echo json_encode($json_final);
        }

    }

    public function runGA($first_pop)
    {
        // Inisiasi Kromosom
        $utils = new Utils();
        try {
            // echo "<br>first_pop: " . json_encode($first_pop) . "<br>";
            $population = $this->generatePops($first_pop);
            $fitness_collection = [];
            foreach ($population as $line) {
                // echo "<pre> populasi" . json_encode($line) . "</pre>";
                array_push($fitness_collection, end($line));
            }

            $population = $this->crossover($population);
            $population = $this->mutation($population);
            $selected_pops = $population[$this->selection($population)];
            // echo "<pre>";
            // var_dump($population[0]);
            // echo json_encode($population[0]);
            // echo "</pre>";

            return $selected_pops;
        } catch (Exception $e) {
            # instead maybe better to use this
            http_response_code(404);
            die();
            // throw new Exception('404');
        }

    }

    public function generatePops($first_pop)
    {
        $database = new Database();
        $kromosom = new Individu($this->time, $this->cities_visited, $this->objek_wisata, $this->digit, $this->id_data, $this->json_jarak);
        $population = [];
        if ($first_pop !== '' && $first_pop != null) {
            $population[0] = $first_pop;
            $pops_counter = $this->population - 1;
        } else {
            $pops_counter = $this->population;
        }

        for ($i = 0; $i < $pops_counter; $i++) {
            $kromosom_baru = $kromosom->generateChrom();
            if ($kromosom_baru !== false) {
                array_push($population, $kromosom_baru);
            }
        }
        return $population;
    }

    /* GA Operators*/
    public function crossover($population)
    {
        $available_to_xo = CROSSOVER_RATE * $this->population;
        // DEBUG
        for ($i = 0; $i < $available_to_xo; $i += 2) {
            // $rand_index_1 = mt_rand(1,($this->population-1));
            $rand_index_1 = $this->selection($population);
            $rand_index_2 = $this->selection($population);
            // $rand_index_2 = mt_rand(1,($this->population-1));
            while ($rand_index_1 === $rand_index_2) {
                $rand_index_2 = mt_rand(1, ($this->population - 1));
            }
            $offsprings = $this->crossoverPM($population[$rand_index_1], $population[$rand_index_2]);
        }
        return $population;
    }

    public function crossoverPM($chrom_parent1, $chrom_parent2)
    {
        $length_chrom1 = sizeof($chrom_parent1);
        ## -1 fitness, -1 index yang length kan dia dimulai dari 0 bukan 1, sedangkan sizeof itu ngitungnya dr 1

        $end = $length_chrom1 - $this->digit - 2;
        $r1 = mt_rand(BATAS_AWAL, $end);
        $r2 = mt_rand(BATAS_AWAL, $end);

        while ($r1 == $r2) {
            $r2 = mt_rand(BATAS_AWAL, $end);
        }
        if ($r2 < $r1) {
            $temp = $r1;
            $r1 = $r2;
            $r2 = $temp;
        }
        // echo "batas_awal =".BATAS_AWAL." end = $end r1 = $r1 r2 = $r2 <br>";
        $length_selected = $r2 - $r1;
        $chrom1_slice = array_slice($chrom_parent1, $r1, $length_selected);
        $chrom2_slice = array_slice($chrom_parent2, $r1, $length_selected);
        // echo "r1 = $r1 r2 = $r2 | Chrom Slice 1:".$this->my_print_r2($chrom1_slice)."<br> Chrom Slice 2:".$this->my_print_r2($chrom2_slice)."<br>";
        // DONE 2: replace chrom1 slice ke chrom2 slice dan sebaliknya.
        $index_slice = 0;
        $i = $r1;
        $lengthplusone = $length_selected + 1;
        while ($i < $r2) {
            $chrom_parent1[$i] = $chrom2_slice[$index_slice];
            $chrom_parent2[$i] = $chrom1_slice[$index_slice];
            $index_slice++;
            $i++;
        }
        $offsprings = [$chrom_parent1, $chrom_parent2];
        $z = 0;
        foreach ($offsprings as $line) {
            $verifikasi = $this->verifikasiBin($line);
            if ($verifikasi === false) {
                // echo "Lolos verifikasi parent ke - $z! Alhamdulilah ya ukhti<br>";
                $krom = $this->generateNewFitness($line);
                // echo "<br>New Chrom Parent + Fitness:".$this->my_print_r2($krom)."<br>";
            } else {
                // echo "Tidak Lolos verifikasi parent ke - $z! Tapi ndapapa ada yang baru<br>";
                $krom = $verifikasi;
                $krom = $this->generateNewFitness($krom);
                // echo "<br>New Chrom Parent + Fitness:".$this->my_print_r2($krom)."<br>";
            }
            $offsprings[$z] = $krom;
            $z++;
        }
        return $offsprings;
    }
    public function mutation($population)
    {
        $available_to_mutate = MUTATION_RATE * $this->population;
        for ($i = 0; $i < $available_to_mutate; $i++) {
            // DEBUG
            // $random_pops_index = array_rand($population);
            // echo "Populasi ke-$random_pops_index before : <br>";
            // echo $this->my_print_r2($population[$random_pops_index]);
            // echo "Population that has been mutated : <br>";
            $random_pops_index = $this->selection($population);
            $pops_mutated = $this->mutationSwap($population[$random_pops_index]);
            // DONE : itung fitness setelah dia berubah
            $verifikasi = $this->verifikasiBin($pops_mutated);
            if (!$verifikasi) {
                // echo "Lolos verifikasi hasil mutasinya! Alhamdulilah ya ukhti<br><br>";
                $pops_mutated = $this->generateNewFitness($pops_mutated);
            } else {
                // echo "Tidak Lolos verifikasi hasil mutasinya!  Tapi ndapapa ada yang baru<br>";
                // echo "Individu baru: <br>";
                // var_dump($verifikasi);
                $pops_mutated = $this->generateNewFitness($verifikasi);
            }

            // Timpa populasi lama dengan yang dimutasi
            $population[$random_pops_index] = $pops_mutated;
        }

        return $population;
    }

    public function selectionRW($fitness)
    {
        $utils = new Utils();
        // DEBUG
        // echo "Fitness Collection:";
        // var_dump($fitness);
        $total_fitness = 0;
        foreach ($fitness as $line) {
            $total_fitness += $line;
        }
        // echo "<br>Total Fitness: $total_fitness";
        $random_float_num = $this->random_float(0, $total_fitness);
        // echo "<br>Random Float Num: $random_float_num";
        $partial_sum = 0;
        $i = 0;
        foreach ($fitness as $line) {
            $partial_sum += $line;
            // echo "<br>partial sum: $partial_sum <br>";
            if ($partial_sum >= $random_float_num) {
                // echo "index selected for this generation by roulette wheel= $i<br>";
                return $i;
            }
            $i++;
        }
    }

    public function selection(&$population)
    {
        $fitness_collection = [];
        foreach ($population as $line) {
            array_push($fitness_collection, end($line));
        }
        $selected_index = $this->selectionRW($fitness_collection);
        return $selected_index;
    }

    /* GA Operators */

    public function mutationSwap($chrom)
    {
        $length = sizeof($chrom);
        // time + origin
        $start = $this->digit * 2;
        $end = $length - $this->digit - 2;
        // -5 = -1 (karena array indexnya mulai dari 0) -1 karena diambil fitness-4 (karena 4 index terakhir isinya ngurah rai yg ga bs diganggu gugat)
        // echo "start $start end $end ";
        $r1 = mt_rand($start, $end);
        $r2 = mt_rand($start, $end);
        while ($r1 == $r2) {
            $r2 = mt_rand($start, $end);
        }
        // echo "Tuker $r1 dengan $r2<br>";
        $temp = $chrom[$r1];
        $chrom[$r1] = $chrom[$r2];
        $chrom[$r2] = $temp;
        return $chrom;
    }

//====================== UTILS =============================\\
    public function generateNewFitness($chromosom)
    {
        // echo "sebelum <br>";
        // echo $this->my_print_r2($chromosom);
        $last_index = sizeof($chromosom) - 1;
        $utils = new Utils();
        $binary = "";
        $waypoints = "";
        $chrom_int = [];
        $i = $this->digit;
        $a = sizeof($chromosom) - 1;
        $b = sizeof($chromosom);
        while ($i < $b) {
            if ($i != $a) {
                // echo "i = $i";
                $binary_array = array_slice($chromosom, $i, $this->digit);
                // var_dump($binary_array);
                $dec = $this->individu->bintodec($binary_array);
                array_push($chrom_int, $dec);
            }
            $i += $this->digit;
        }
        $distance = $this->individu->getDistance($chrom_int, $this->id_data, $this->json_jarak);
        $total_distance = sprintf("%.1f", $distance);
        $total_minutes = ($total_distance / $this->velocity) * 60;
        $fitness = sprintf("%.10f", 1 / $total_minutes);
        $chromosom[$last_index] = $fitness;
        return $chromosom;
    }
    public function verifikasiBin($chromosom)
    {
        // echo "Dilakukan proses verifikasi pada kromosom berikut ini<br>------------------<br>";
        // echo $this->my_print_r2($chromosom);
        $i = 0;
        $a = sizeof($chromosom) - 1;
        $b = sizeof($chromosom);
        $batas_akhir = $a - $this->digit;
        $ar_int = [];
        $failed_index_counter = 0;

        while ($i < $b) {
            // cek dimana $i bukan di posisi array yg fitness dan bukan di posisi origin dan destinasi akhir
            if ($i != $a && $i >= BATAS_AWAL) {
                // mulai ngecek
                $binary_array = array_slice($chromosom, $i, $this->digit);
                $dec = $this->individu->bintodec($binary_array);
                // check apakah dia ga di index = 0 dan ga batas akhir
                if ($i != 0 && $i !== $batas_akhir && $i >= BATAS_AWAL) {
                    // jika dia = 1 atau dia = 0
                    if ($dec == 1 || $dec == 0) {
                        // echo "<br>Kota ada yang ga valid 0 dan 1 <br>";
                        $failed_index = $failed_index_counter;
                        break;
                    }
                    // check ada kota yang sama
                    $same_dest_index = $this->individu->checkIfCitySame($ar_int, $dec);
                    if ($same_dest_index !== false) {
                        // echo "<br>Kota ke - $same_dest_index double <br>";
                        $failed_index = $same_dest_index;
                        // echo $this->my_print_r2($ar_int);
                        break;
                    }
                    array_push($ar_int, $dec);
                    if (!$this->individu->verifikasi($dec, $this->json_jarak) && $dec != 0) {
                        $failed_index = $failed_index_counter;
                        // echo "<br>Tidak ditemukan objek wisata ke- $failed_index_counter - ($dec)<br>";
                        // echo $this->my_print_r2($chromosom);
                        break;
                    }
                    $failed_index_counter++;
                }
            }
            $i += $this->digit;
        }
        $new_chromosom = $chromosom;

        // kota pertama digit 5 : ganti dari 10-14 $failed_index = 0
        // kota kedua digit 4: ganti dari 12-15 1
        // 5(digit)*(2+0 (failed index))

        // fixing broken chromosome
        if (isset($failed_index)) {
            // echo "Ditemukan failed binary pada destinasi ke-($failed_index), melakukan penggantian sparepart<br>------------------";
            // // kalo tanpa jam
            // // BARU SAMPE SINI
            $failed_index = $this->digit * ($failed_index + 2);
            $failed_index_end = $failed_index + $this->digit;
            // echo "1) Generate destinasi baru<br>";
            // $last_index_objek = sizeof($this->objek_wisata)-1;
            // $index_randomized_city = mt_rand(0,$last_index_objek);
            $index_randomized_city = array_rand($this->objek_wisata);
            $city_check = $this->notZeroOrOne($this->objek_wisata[$index_randomized_city]);
            $randomized_city = $this->checkIfSame($ar_int, $city_check, $this->objek_wisata, $this->cities_visited);
            // echo "- Destinasi Baru = $randomized_city<br>";
            $new_city_binary = $this->individu->dectobin($randomized_city, $this->digit);
            $new_city_binary_index = 0;
            // echo "Binary Destinasi<br>";
            // var_dump($new_city_binary);
            // echo "<br>2) Ganti binary tik tok<br>";
            while ($failed_index < $failed_index_end) {
                $chromosom[$failed_index] = $new_city_binary[$new_city_binary_index];
                $failed_index++;
                $new_city_binary_index++;
            }
            // echo $this->my_print_r2($chromosom);
            // echo "<br>3) Yayy! Brand new kromosom, hopefully sudah benar yak!<br>";
            // check again before passing it
            $newChromosom = $this->verifikasiBin($chromosom);
            if ($newChromosom) {
                $chromosom = $newChromosom;
            }
            // echo $this->my_print_r2($chromosom);
            return $chromosom;
        }
        // var_dump($chromosom);
        # Apabila benar / lolos verifikasi
        return false;
    }
    public function notZeroOrOne($city)
    {
        if ($city == 0 || $city == 1) {
            // echo "CAUGHT $city <br>";
            $index_randomized_city = array_rand($this->objek_wisata);
            $newCity = $this->objek_wisata[$index_randomized_city];
            $newCity = $this->notZeroOrOne($newCity);
        } else {
            $newCity = $city;
        }
        return $newCity;
    }
    public function random_float($min, $max)
    {
        return ($min + lcg_value() * (abs($max - $min)));
    }
    public function my_print_r2($x)
    {
        return json_encode($x) . "<br>";
    }
    public function bintodec($arr_bin)
    {
        $bin = implode("", $arr_bin);
        $dec = bindec($bin);
        return $dec;
    }
    public function checkIfSame($arr, $dest, $selection, $cities_visited)
    {
        if (sizeof($selection) < $cities_visited) {
            echo json_encode(["status" => "error", "error" => "Sorry! No route is available for this time."]);
            die();
        }
        if (array_search($dest, $arr) !== false) {
            $l_index = sizeof($selection) - 1;
            $new_index = mt_rand(0, $l_index);
            $new_dest = $this->checkIfSame($arr, $selection[$new_index], $selection, $this->cities_visited);
            return $new_dest;
        } else {
            return $dest;
        }
    }
}

$pops = 10;
// $time = 5;
// $cities_visited = 2;
// example: http://localhost/transity_backend/Generasi.php?availTime=4&numDest=1
$time = $_GET['availTime'];
$cities_visited = $_GET['numDest'];
$start_time = $_GET['startTime'];
$gen = new Generasi($pops, $time, $start_time, $cities_visited);
$gen->runGAAll(5);
