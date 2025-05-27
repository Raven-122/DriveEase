<?php
header('Content-Type: application/json');

// Database config
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

// Log POST data for debugging (check PHP error log)
file_put_contents('php://stderr', print_r($_POST, true));

$user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : null;
$quiz_id = isset($_POST['quiz_id']) ? intval($_POST['quiz_id']) : null;
$score = isset($_POST['score']) ? intval($_POST['score']) : null;

if ($user_id === null || $quiz_id === null || $score === null) {
    echo json_encode([
        "success" => false,
        "message" => "Missing required parameters."
    ]);
    exit;
}

try {
    $stmt = $pdo->prepare("INSERT INTO quiz_results (user_id, quiz_id, score, created_at) VALUES (:user_id, :quiz_id, :score, NOW())");
    $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $stmt->bindParam(':quiz_id', $quiz_id, PDO::PARAM_INT);
    $stmt->bindParam(':score', $score, PDO::PARAM_INT);

    $executed = $stmt->execute();

    if ($executed) {
        echo json_encode(['success' => true, 'message' => 'Quiz result saved successfully.']);
    } else {
        $errorInfo = $stmt->errorInfo();
        echo json_encode(['success' => false, 'message' => 'Failed to execute statement: ' . $errorInfo[2]]);
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'PDO Exception: ' . $e->getMessage()]);
}
