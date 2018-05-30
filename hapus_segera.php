<?php
$lat = "-8.634864";
$lang = "115.192476";
$API_KEY = "AIzaSyBTE9O-ina1ZgUJgu9P4kN66etZyjErqYw";

$url = "https://maps.googleapis.com/maps/api/geocode/json?latlng=" . $lat . "," . $lang . "&key=" . $API_KEY;
$response = file_get_contents($url);
$result = json_decode($response, true);
echo $result["results"][0]["formatted_address"];
var_dump($result);
