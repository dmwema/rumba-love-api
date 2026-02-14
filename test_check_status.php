<?php

echo "=== TEST DE LA ROUTE /api/payments/check-status ===\n\n";

// Fonction helper pour faire des requêtes cURL
function makeRequest($url, $data) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    curl_close($ch);
    return $response;
}

// Test 1: Paiement avec numéro de test (devrait réussir automatiquement)
echo "Test 1: Paiement avec numéro de test (243888888888)\n";
$initData1 = [
    'email' => 'status-test@example.com',
    'fullName' => 'Status Test',
     'phone' => '243888888888',
    'paymentMethod' => 'mobile'
];
$initResponse1 = makeRequest('http://localhost:8000/api/payments/initiate', $initData1);
echo "Initiation: " . $initResponse1 . "\n";

$initResult1 = json_decode($initResponse1, true);
if (isset($initResult1['paymentId'])) {
    $paymentId1 = $initResult1['paymentId'];
    echo "Vérification du statut pour paymentId: $paymentId1\n";
    $statusResponse1 = makeRequest('http://localhost:8000/api/payments/check-status', ['paymentId' => $paymentId1]);
    echo "Statut: " . $statusResponse1 . "\n\n";
}

// Test 2: Paiement avec numéro normal (devrait être en attente)
echo "Test 2: Paiement avec numéro normal\n";
$initData2 = [
    'email' => 'status-test2@example.com',
    'fullName' => 'Status Test 2',
    'phone' => '243814063056',
    'paymentMethod' => 'mobile'
];
$initResponse2 = makeRequest('http://localhost:8000/api/payments/initiate', $initData2);
echo "Initiation: " . $initResponse2 . "\n";

$initResult2 = json_decode($initResponse2, true);
if (isset($initResult2['paymentId'])) {
    $paymentId2 = $initResult2['paymentId'];
    echo "Vérification du statut pour paymentId: $paymentId2\n";
    $statusResponse2 = makeRequest('http://localhost:8000/api/payments/check-status', ['paymentId' => $paymentId2]);
    echo "Statut: " . $statusResponse2 . "\n\n";
}

// Test 3: Paiement inexistant
echo "Test 3: Paiement inexistant (ID 99999)\n";
$statusResponse3 = makeRequest('http://localhost:8000/api/payments/check-status', ['paymentId' => 99999]);
echo "Statut: " . $statusResponse3 . "\n\n";

echo "=== TESTS TERMINÉS ===\n";