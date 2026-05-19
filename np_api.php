<?php
// Файл np_api.php
require 'config.php'; // Потрібен тільки якщо ви використовуєте сесії тут

header('Content-Type: application/json');

define('NP_API_KEY', '71b569766acd6e4c060daefac0284749'); // <--- ВСТАВТЕ СВІЙ КЛЮЧ

function np_request($model, $method, $params) {
    $data = json_encode([
        "apiKey" => NP_API_KEY,
        "modelName" => $model,
        "calledMethod" => $method,
        "methodProperties" => $params
    ]);

    $ch = curl_init('https://api.novaposhta.ua/v2.0/json/');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    $response = curl_exec($ch);
    curl_close($ch);

    return $response;
}

if (isset($_GET['action'])) {
    if ($_GET['action'] == 'getCities' && !empty($_GET['q'])) {
        echo np_request('Address', 'getCities', [
            'FindByString' => $_GET['q'],
            'Limit' => 10
        ]);
    } 
    elseif ($_GET['action'] == 'getWarehouses' && !empty($_GET['cityRef'])) {
        echo np_request('Address', 'getWarehouses', [
            'CityRef' => $_GET['cityRef']
        ]);
    }
    exit;
}