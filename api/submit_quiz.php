<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');

require_once('../include/db.php'); // $conn should be your PDO instance

try {
    $data = json_decode(file_get_contents("php://input"), true);

    if (
        !isset($data['user_id']) || 
        !isset($data['quiz_id']) || 
        !isset($data['answers']) || 
        !is_array($data['answers'])
    ) {
        echo json_encode([
            'success' => false,
            'message' => 'Missing or invalid parameters.'
        ]);
        exit;
    }

    $user_id = intval($data['user_id']);
    $quiz_id = intval($data['quiz_id']);
    $answers = $data['answers'];

    $score = 0;
    $results = [];

    foreach ($answers as $answer) {
        $question_id = intval($answer['question_id']);
        $selected_option = strtoupper(trim($answer['selected_option']));

        $stmt = $conn->prepare("SELECT correct_option FROM questions WHERE id = ?");
        $stmt->execute([$question_id]);
        $correct = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($correct) {
            $is_correct = ($correct['correct_option'] === $selected_option);
            if ($is_correct) {
                $score++;
            }

            $results[] = [
                'question_id' => $question_id,
                'selected_option' => $selected_option,
                'correct_option' => $correct['correct_option'],
                'is_correct' => $is_correct
            ];
        }
    }

    $total_questions = count($results);

    // Save to quiz_results table
    $insert = $conn->prepare("INSERT INTO quiz_results (user_id, quiz_id, score, total_questions, date_taken) VALUES (?, ?, ?, ?, NOW())");
    $insert->execute([$user_id, $quiz_id, $score, $total_questions]);

    echo json_encode([
        'success' => true,
        'message' => 'Quiz submitted and saved successfully',
        'score' => $score,
        'total' => $total_questions,
        'results' => $results
    ]);

} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
