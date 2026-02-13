<?php

// Test de l'authentification admin
$data = json_encode([
    'username' => 'fils@cinefilm.cd',
    'password' => 'p@ssword123654'
]);

$context = stream_context_create([
    'http' => [
        'method' => 'POST',
        'header' => 'Content-Type: application/json',
        'content' => $data
    ]
]);

$result = file_get_contents('http://localhost:8080/auth/admin', false, $context);

echo "Response: " . $result . PHP_EOL;

if ($result) {
    $response = json_decode($result, true);
    if (isset($response['token'])) {
        echo "SUCCESS: Token received!" . PHP_EOL;
        echo "Token: " . substr($response['token'], 0, 50) . "..." . PHP_EOL;

        // Tester un endpoint protégé
        echo PHP_EOL . "Testing protected endpoint..." . PHP_EOL;
        $protectedContext = stream_context_create([
            'http' => [
                'method' => 'GET',
                'header' => 'Authorization: Bearer ' . $response['token']
            ]
        ]);

        $protectedResult = file_get_contents('http://localhost:8080/api/admin/users', false, $protectedContext);
        if ($protectedResult) {
            echo "SUCCESS: Protected endpoint accessible!" . PHP_EOL;
            $protectedResponse = json_decode($protectedResult, true);
            if (isset($protectedResponse['error'])) {
                echo "But got error: " . $protectedResponse['error'] . PHP_EOL;
            } else {
                echo "Response received from protected endpoint" . PHP_EOL;
            }
        } else {
            echo "ERROR: Cannot access protected endpoint" . PHP_EOL;
        }
    } else {
        echo "ERROR: No token in response" . PHP_EOL;
        print_r($response);
    }
} else {
    echo "ERROR: No response from server" . PHP_EOL;
}