<?php

// Test de l'endpoint /api/payments/initiate avec FlexPay
echo "Testing Payment Initiate with FlexPay Integration\n";
echo "=================================================\n\n";

// Test 1: Paiement mobile
echo "1. Testing Mobile Payment:\n";
$data = json_encode([
    'email' => 'test' . rand(1000, 9999) . '@example.com',
    'fullName' => 'Test User Mobile',
    'phone' => '243814063056',
    'paymentMethod' => 'mobile'
]);

$result = makeRequest('POST', 'http://localhost:8000/api/payments/initiate', $data);
echo "Result: " . ($result['success'] ? 'SUCCESS' : 'FAILED') . "\n";
echo "Response: " . substr($result['response'], 0, 300) . "...\n\n";

// Test 2: Paiement carte
echo "2. Testing Card Payment:\n";
$data = json_encode([
    'email' => 'test' . rand(1000, 9999) . '@example.com',
    'fullName' => 'Test User Card',
    'phone' => '243814063056',
    'paymentMethod' => 'card'
]);

$result = makeRequest('POST', 'http://localhost:8000/api/payments/initiate', $data);
echo "Result: " . ($result['success'] ? 'SUCCESS' : 'FAILED') . "\n";
echo "Response: " . substr($result['response'], 0, 300) . "...\n\n";

// Test 3: Données invalides
echo "3. Testing Invalid Data:\n";
$data = json_encode([
    'email' => 'invalid-email',
    'fullName' => 'Test User',
    'phone' => '243814063056',
    'paymentMethod' => 'invalid'
]);

$result = makeRequest('POST', 'http://localhost:8000/api/payments/initiate', $data);
echo "Result: " . ($result['success'] ? 'SUCCESS' : 'FAILED') . "\n";
echo "Response: " . substr($result['response'], 0, 200) . "...\n\n";

echo "Note: This test calls the real FlexPay API. Use test phone numbers for development.\n";

function makeRequest($method, $url, $data = null) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);

    if ($data) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    }

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    return [
        'success' => $httpCode >= 200 && $httpCode < 300,
        'response' => $response,
        'code' => $httpCode
    ];
}