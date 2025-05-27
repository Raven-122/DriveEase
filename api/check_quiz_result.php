<?php
header('Content-Type: application/json');

$host = 'localhost';
$dbname = 'driveease';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed: ' . $e->getMessage()]);
    exit;
}

$user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : null;
$quiz_id = isset($_GET['quiz_id']) ? intval($_GET['quiz_id']) : null;

if (!$user_id || !$quiz_id) {
    echo json_encode(['success' => false, 'message' => 'Missing parameters']);
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT score FROM quiz_results WHERE user_id = :user_id AND quiz_id = :quiz_id LIMIT 1");
    $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $stmt->bindParam(':quiz_id', $quiz_id, PDO::PARAM_INT);
    $stmt->execute();

    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($result) {
        echo json_encode([
            'success' => true,
            'completed' => true,
            'score' => intval($result['score'])
        ]);
    } else {
        echo json_encode([
            'success' => true,
            'completed' => false,
            'score' => null
        ]);
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
