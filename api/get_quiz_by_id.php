<?php
header("Content-Type: application/json");
require_once '../include/db.php';

if (!isset($pdo)) {
    echo json_encode(["success" => false, "message" => "PDO connection not found in db.php"]);
    exit;
}

$quizId = isset($_GET['quiz_id']) ? intval($_GET['quiz_id']) : 0;

if ($quizId <= 0) {
    echo json_encode(["success" => false, "message" => "Invalid quiz ID"]);
    exit;
}

try {
    $stmt = $pdo->prepare("
        SELECT 
            quizzes.id AS quiz_id,
            quizzes.title,
            quizzes.category_id,
            quizzes.last_updated,
            categories.name AS category_name,
            q.id AS question_id,
            q.question_text,
            q.option_a,
            q.option_b,
            q.option_c,
            q.option_d,
            q.correct_option
        FROM quizzes
        LEFT JOIN categories ON quizzes.category_id = categories.id
        LEFT JOIN quiz_questions qq ON quizzes.id = qq.quiz_id
        LEFT JOIN questions q ON qq.question_id = q.id
        WHERE quizzes.id = ?
        ORDER BY q.id ASC
    ");
    $stmt->execute([$quizId]);

    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $quizzes = [];
    foreach ($result as $row) {
        $quiz_id = $row['quiz_id'];
        if (!isset($quizzes[$quiz_id])) {
            $quizzes[$quiz_id] = [
                "quiz_id" => $quiz_id,
                "title" => $row['title'],
                "category_id" => $row['category_id'],
                "category_name" => $row['category_name'],
                "last_updated" => $row['last_updated'],
                "questions" => []
            ];
        }

        if (!empty($row['question_id'])) {
            $quizzes[$quiz_id]["questions"][] = [
                "question_id" => $row['question_id'],
                "question_text" => $row['question_text'],
                "option_a" => $row['option_a'],
                "option_b" => $row['option_b'],
                "option_c" => $row['option_c'],
                "option_d" => $row['option_d'],
                "correct_option" => $row['correct_option']
            ];
        }
    }

    echo json_encode(["success" => true, "quizzes" => array_values($quizzes)]);

} catch (PDOException $e) {
    echo json_encode(["success" => false, "message" => "Database error: " . $e->getMessage()]);
}
