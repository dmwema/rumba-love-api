<?php

// Test de l'endpoint /api/event
$ch = curl_init('http://localhost:8000/api/event');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$response = curl_exec($ch);
$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo 'HTTP Code: ' . $code . PHP_EOL;
echo 'Response: ' . $response . PHP_EOL;