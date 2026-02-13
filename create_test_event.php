<?php

// Script pour créer un événement de test directement
// Cela contourne le problème CLI et utilise le même environnement que le serveur web

$url = 'http://localhost:8000/api/admin/create-test-event';
$data = json_encode([
    'title' => 'Concert Live - Test Event',
    'description' => 'Événement de test pour vérifier les endpoints',
    'price' => 10.00,
    'streamUrl' => 'https://test-stream.com/live',
    'liveDate' => '2026-02-15T20:00:00+00:00'
]);

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json'
]);

$response = curl_exec($ch);
$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo 'HTTP Code: ' . $code . PHP_EOL;
echo 'Response: ' . $response . PHP_EOL;