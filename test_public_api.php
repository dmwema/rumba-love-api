<?php

echo "Testing Public API - No Authentication Required\n";
echo "===============================================\n\n";

// Test 1: Register user
echo "1. Testing User Registration:\n";
$data = json_encode([
    'fullName' => 'Test User ' . rand(1000, 9999),
    'phone' => '243999' . rand(100000, 999999)
]);

$result = makeRequest('POST', 'http://localhost:8000/api/register', $data);
echo "Result: " . ($result['success'] ? 'SUCCESS' : 'FAILED') . "\n";
echo "Response: " . substr($result['response'], 0, 200) . "...\n\n";

// Test 2: Get users list
echo "2. Testing Get Users:\n";
$result = makeRequest('GET', 'http://localhost:8000/api/users');
echo "Result: " . ($result['success'] ? 'SUCCESS' : 'FAILED') . "\n";
echo "Response: " . substr($result['response'], 0, 200) . "...\n\n";

// Test 3: Stream access
echo "3. Testing Stream Access:\n";
$result = makeRequest('GET', 'http://localhost:8000/api/live/watch');
echo "Result: " . ($result['success'] ? 'SUCCESS' : 'FAILED') . "\n";
echo "Response: " . substr($result['response'], 0, 200) . "...\n\n";

// Test 4: Get payments
echo "4. Testing Get Payments:\n";
$result = makeRequest('GET', 'http://localhost:8000/api/payments');
echo "Result: " . ($result['success'] ? 'SUCCESS' : 'FAILED') . "\n";
echo "Response: " . substr($result['response'], 0, 200) . "...\n\n";

echo "All tests completed!\n";

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