<?php
header('Content-Type: application/json');
include '../include/db.php'; // your PDO connection

$response = array();

try {
    $baseImageUrl = "http://10.0.2.2/driveeasee/uploads/"; // change to your server IP or domain

    $query = "SELECT flashcards.id, flashcards.title, flashcards.description, flashcards.image_path, categories.name AS category_name 
            FROM flashcards 
            LEFT JOIN categories ON flashcards.category_id = categories.id
            WHERE categories.name = 'Violations and Penalties'";

    $stmt = $conn->prepare($query);
    $stmt->execute();

    $flashcards = [];

    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    // Get the image path from the database
    $imagePath = $row['image_path'];

    // If the image path starts with "uploads/", remove it to avoid doubling "uploads/uploads/"
    if (strpos($imagePath, 'uploads/') === 0) {
        $imagePath = substr($imagePath, strlen('uploads/'));
    }

    // Prepend your base URL (make sure it ends with a slash)
    $row['image'] = !empty($imagePath) ? $baseImageUrl . $imagePath : null;

    // Optionally remove the raw image_path from the output JSON
    unset($row['image_path']);

    $flashcards[] = $row;
}


    $response['success'] = true;
    $response['data'] = $flashcards;

} catch (PDOException $e) {
    $response['success'] = false;
    $response['message'] = "Database error: " . $e->getMessage();
}

echo json_encode($response);
?>
