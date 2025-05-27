<?php
function sendFCMNotification($deviceToken, $title, $body, $data = []) {
    $serverKey = 'AIzaSyABAWlgQNKKd9Rr8ZHnpMgKrcMDQ_VkYTU';

    $notification = [
        'title' => $title,
        'body' => $body,
        'sound' => 'default'
    ];

    $payload = [
        'to' => $deviceToken,
        'notification' => $notification,
        'data' => $data
    ];

    $headers = [
        'Authorization: key=' . $serverKey,
        'Content-Type: application/json'
    ];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'https://fcm.googleapis.com/fcm/send');
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

    $result = curl_exec($ch);

    if ($result === FALSE) {
        error_log('Curl failed: ' . curl_error($ch));
    }

    curl_close($ch);

    return $result;
}

// Database connection using PDO
$host = 'localhost';
$dbname = 'driveease';
$user = 'root';
$pass = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Fetch all valid device tokens
    $stmt = $pdo->query("SELECT device_token FROM users WHERE device_token IS NOT NULL AND device_token != ''");

    $title = 'Hello from DriveEase';
    $body = 'This is a test notification.';
    $dataPayload = ['extraInfo' => 'some data here'];

    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $deviceToken = $row['device_token'];
        $response = sendFCMNotification($deviceToken, $title, $body, $dataPayload);
        echo "Notification sent to token: $deviceToken\n";
        echo "Response: $response\n";
    }

} catch (PDOException $e) {
    echo 'Database error: ' . $e->getMessage();
}
?>
