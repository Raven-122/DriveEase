<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

include '../include/db.php';

$response = array();

// Check if it's a POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $response = [
        'success' => false,
        'message' => 'Only POST method is allowed'
    ];
    echo json_encode($response);
    exit();
}

// Get POST data
$data = json_decode(file_get_contents('php://input'), true);

try {
    // Start transaction
    $conn->beginTransaction();

    // Insert quiz
    $stmt = $conn->prepare("
        INSERT INTO quizzes (
            title, 
            category_id, 
            status
        ) VALUES (
            :title, 
            :category_id, 
            'Published'
        )
    ");

    $stmt->execute([
        ':title' => $data['title'],
        ':category_id' => $data['category_id']
    ]);

    $quiz_id = $conn->lastInsertId();

    // Insert quiz questions
    if (isset($data['questions']) && is_array($data['questions'])) {
        $stmt = $conn->prepare("
            INSERT INTO quiz_questions (
                quiz_id, 
                question_id
            ) VALUES (
                :quiz_id, 
                :question_id
            )
        ");

        foreach ($data['questions'] as $question_id) {
            $stmt->execute([
                ':quiz_id' => $quiz_id,
                ':question_id' => $question_id
            ]);
        }
    }

    // Commit transaction
    $conn->commit();

    $response = [
        'success' => true,
        'message' => 'Quiz added successfully',
        'data' => [
            'quiz_id' => $quiz_id
        ]
    ];

} catch (PDOException $e) {
    // Rollback transaction on error
    $conn->rollBack();
    
    $response = [
        'success' => false,
        'message' => 'Error adding quiz: ' . $e->getMessage()
    ];
}

echo json_encode($response);