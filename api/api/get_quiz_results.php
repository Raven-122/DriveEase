<?php
header('Content-Type: application/json');

// Database credentials - change to your actual credentials
$host = 'localhost';
$db   = 'driveease';
$user = 'root';
$pass = '';
$charset = 'utf8mb4';

// PDO DSN
$dsn = "mysql:host=$host;dbname=$db;charset=$charset";

// Options for PDO
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);

    // Check required parameter user_id
    if (!isset($_GET['user_id'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Missing user_id']);
        exit();
    }

    $user_id = intval($_GET['user_id']);

    // Optional quiz_id filter
    $sql = "SELECT id, user_id, quiz_id, score, total_questions, date_taken FROM quiz_results WHERE user_id = :user_id";
    $params = ['user_id' => $user_id];

    if (isset($_GET['quiz_id'])) {
        $quiz_id = intval($_GET['quiz_id']);
        $sql .= " AND quiz_id = :quiz_id";
        $params['quiz_id'] = $quiz_id;
    }

    $sql .= " ORDER BY date_taken DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    $results = $stmt->fetchAll();

    echo json_encode([
        'success' => true,
        'results' => $results
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
    exit();
}
