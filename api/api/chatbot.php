<?php
header('Content-Type: application/json');

// Optional: include DB connection if needed
// include '../include/db.php';

$data = json_decode(file_get_contents("php://input"));
$prompt = $data->prompt ?? '';

if (empty($prompt)) {
    echo json_encode(["response" => "Prompt is empty."]);
    exit;
}

$apiKey = "YOUR_OPENAI_API_KEY"; // Replace with your actual key

$curl = curl_init();

curl_setopt_array($curl, [
    CURLOPT_URL => "https://api.openai.com/v1/chat/completions",
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => json_encode([
        "model" => "gpt-3.5-turbo",
        "messages" => [
            ["role" => "user", "content" => $prompt]
        ],
        "max_tokens" => 150,
    ]),
    CURLOPT_HTTPHEADER => [
        "Authorization: Bearer $apiKey",
        "Content-Type: application/json"
    ]
]);

$response = curl_exec($curl);

if (curl_errno($curl)) {
    echo json_encode(["response" => "cURL error: " . curl_error($curl)]);
    curl_close($curl);
    exit;
}

curl_close($curl);

$decoded = json_decode($response, true);

$reply = $decoded['choices'][0]['message']['content'] ?? 'No response from AI.';

echo json_encode(["response" => trim($reply)]);
?>
