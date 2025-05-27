
<?php
$conn = new mysqli("localhost", "root", "", "driveease");

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $token = $_POST['token'];
    $password = password_hash($_POST['password'],PASSWORD_DEFAULT);

    $stmt = $conn->prepare("UPDATE users SET password=?, reset_token=NULL, token_expire=NULL WHERE reset_token=? AND token_expire > NOW()");
    $stmt->bind_param("ss", $password, $token);
    $stmt->execute();

    if ($stmt->affected_rows > 0) {
        echo "Password has been updated!";
    } else {
        echo "Invalid or expired token.";
    }
}
?>
