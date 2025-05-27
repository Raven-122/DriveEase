<?php
header('Content-Type: application/json');

try {
    $conn = new PDO('mysql:host=localhost;dbname=driveease', 'root', '');
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Database connection failed: ' . $e->getMessage()
    ]);
    exit;
}

$input = json_decode(file_get_contents("php://input"), true);

// Validate required fields
if (
    !isset($input['username']) ||
    !isset($input['email']) ||
    !isset($input['password'])
) {
    echo json_encode([
        'success' => false,
        'message' => 'Username, email, and password are required'
    ]);
    exit;
}

$username = $input['username'];
$email = $input['email'];
$password = $input['password'];

// Check if user or email already exists
$query = "SELECT * FROM users WHERE username = :username OR email = :email";
$stmt = $conn->prepare($query);
$stmt->bindParam(':username', $username);
$stmt->bindParam(':email', $email);
$stmt->execute();

if ($stmt->rowCount() > 0) {
    echo json_encode([
        'success' => false,
        'message' => 'Username or email already exists'
    ]);
    exit;
}

// Hash the password
$hashedPassword = password_hash($password, PASSWORD_DEFAULT);

// Insert new user
$insertQuery = "INSERT INTO users (username, email, password) VALUES (:username, :email, :password)";
$insertStmt = $conn->prepare($insertQuery);
$insertStmt->bindParam(':username', $username);
$insertStmt->bindParam(':email', $email);
$insertStmt->bindParam(':password', $hashedPassword);

if ($insertStmt->execute()) {
    echo json_encode([
        'success' => true,
        'message' => 'User registered successfully'
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Failed to register user'
    ]);
}
?>
