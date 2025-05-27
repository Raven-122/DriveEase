<?php
header('Content-Type: application/json');
include '../include/db.php'; // Assumes you have DB connection here

$response = ['success' => false, 'message' => '', 'data' => []];

if (!isset($_GET['category_id'])) {
    $response['message'] = 'category_id is required';
    echo json_encode($response);
    exit;
}

$category_id = intval($_GET['category_id']);

try {
    $stmt = $conn->prepare("SELECT id, question_text, option_a, option_b, option_c, option_d, correct_option FROM questions WHERE category_id = ?");
    $stmt->execute([$category_id]);

    $questions = [];

    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $questions[] = [
            'id' => $row['id'],
            'question_text' => $row['question_text'],
            'options' => [
                ['id' => 1, 'label' => 'A', 'text' => $row['option_a']],
                ['id' => 2, 'label' => 'B', 'text' => $row['option_b']],
                ['id' => 3, 'label' => 'C', 'text' => $row['option_c']],
                ['id' => 4, 'label' => 'D', 'text' => $row['option_d']],
            ],
            'correct_option' => $row['correct_option'] // Optional: remove this when sending to users
        ];
    }

    $response['success'] = true;
    $response['data'] = $questions;

} catch (PDOException $e) {
    $response['message'] = "Database Error: " . $e->getMessage();
}

echo json_encode($response);
?>
