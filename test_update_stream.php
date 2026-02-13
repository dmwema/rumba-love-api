<?php

// Test rapide de l'endpoint update-stream
$url = 'http://localhost:8000/api/admin/event/update-stream';

// Créer un token JWT de test (même logique que TestJwtAuthenticator)
$header = json_encode(['alg' => 'HS256', 'typ' => 'JWT']);
$payload = json_encode([
    'username' => 'fils@cinefilm.cd',
    'roles' => ['ROLE_ADMIN'],
    'exp' => time() + 3600
]);

$headerEncoded = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($header));
$payloadEncoded = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($payload));

$secret = 'test_secret_key_for_development_only';
$signature = hash_hmac('sha256', $headerEncoded . "." . $payloadEncoded, $secret);
$signatureEncoded = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($signature));

$token = $headerEncoded . "." . $payloadEncoded . "." . $signatureEncoded;

$data = json_encode([
    'streamUrl' => 'https://secure-stream-platform.com/live/test-stream'
]);

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Authorization: Bearer ' . $token
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Code: $httpCode\n";
echo "Response: $response\n";
echo "Token used: " . substr($token, 0, 50) . "...\n";