<?php
header("Content-Type: application/json");
include '../include/db.php'; // make sure this returns a PDO $conn object

$last_check = isset($_GET['last_check']) ? $_GET['last_check'] : null;

if (!$last_check) {
    echo json_encode([
        "status" => false,
        "message" => "Missing 'last_check' parameter"
    ]);
    exit;
}

$response = ["status" => true, "new_quiz" => false, "new_flashcard" => false];

try {
    // Check new quizzes
    $quizStmt = $conn->prepare("SELECT COUNT(*) FROM quizzes WHERE created_at > ?");
    $quizStmt->execute([$last_check]);
    $quizCount = $quizStmt->fetchColumn();
    $response['new_quiz'] = $quizCount > 0;

    // Check new flashcards
    $flashStmt = $conn->prepare("SELECT COUNT(*) FROM flashcards WHERE created_at > ?");
    $flashStmt->execute([$last_check]);
    $flashCount = $flashStmt->fetchColumn();
    $response['new_flashcard'] = $flashCount > 0;

    echo json_encode($response);
} catch (PDOException $e) {
    echo json_encode([
        "status" => false,
        "message" => "Database error: " . $e->getMessage()
    ]);
}
?>
