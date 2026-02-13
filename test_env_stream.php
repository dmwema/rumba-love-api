<?php

// Test de l'accès au stream via variable d'environnement
echo "Testing Stream Access with Environment Variable\n";
echo "================================================\n\n";

// Simuler un token JWT pour les tests (même logique que TestJwtAuthenticator)
$header = json_encode(['alg' => 'HS256', 'typ' => 'JWT']);
$payload = json_encode([
    'username' => 'test_user',
    'roles' => ['ROLE_LIVE_ACCESS'],
    'exp' => time() + 3600
]);

$headerEncoded = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($header));
$payloadEncoded = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($payload));

$secret = 'test_secret_key_for_development_only';
$signature = hash_hmac('sha256', $headerEncoded . "." . $payloadEncoded, $secret);
$signatureEncoded = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($signature));

$token = $headerEncoded . "." . $payloadEncoded . "." . $signatureEncoded;

// Test de l'endpoint /api/live/watch
$url = 'http://localhost:8000/api/live/watch';

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer ' . $token
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "Testing GET /api/live/watch\n";
echo "HTTP Code: $httpCode\n";
echo "Response: $response\n\n";

// Test de la configuration admin
$url2 = 'http://localhost:8000/api/admin/event/update-stream';

$ch2 = curl_init($url2);
curl_setopt($ch2, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch2, CURLOPT_CUSTOMREQUEST, 'PUT');
curl_setopt($ch2, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer ' . $token,
    'Content-Type: application/json'
]);

$response2 = curl_exec($ch2);
$httpCode2 = curl_getinfo($ch2, CURLINFO_HTTP_CODE);
curl_close($ch2);

echo "Testing PUT /api/admin/event/update-stream (obsolete)\n";
echo "HTTP Code: $httpCode2\n";
echo "Response: $response2\n\n";

echo "Note: Configure STREAM_URL in .env.local for the stream to work properly\n";
echo "Example: STREAM_URL=https://your-stream-platform.com/live/concert\n";