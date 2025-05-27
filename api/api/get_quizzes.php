<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");

$host = "localhost";
$db_name = "driveease";
$username = "root";
$password = "";

try {
    $conn = new PDO("mysql:host=$host;dbname=$db_name;charset=utf8mb4", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // JOIN with categories table to get category name
    $stmt = $conn->prepare("
    SELECT 
        q.id, 
        q.title, 
        q.icon, 
        q.category_id, 
        c.name AS category_name, 
        q.published, 
        q.status, 
        q.created_at, 
        q.last_updated 
    FROM quizzes q
    JOIN categories c ON q.category_id = c.id
");


    $stmt->execute();
    $quizzes = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        "success" => true,
        "quizzes" => $quizzes
    ]);
} catch (PDOException $e) {
    echo json_encode([
        "success" => false,
        "message" => "Database Error: " . $e->getMessage()
    ]);
}
?>