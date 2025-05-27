<?php
// Database connection
$conn = new mysqli("localhost", "root", "", "driveease");

if (isset($_GET['token'])) {
    $token = $_GET['token'];

    // Check if the token exists in the database
    $stmt = $conn->prepare("SELECT * FROM users WHERE reset_token=?");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        $tokenExpire = $user['token_expire'];
        
        // Check if the token is expired
        if (strtotime($tokenExpire) > time()) {
            // Token is valid, show password reset form
            if ($_SERVER["REQUEST_METHOD"] == "POST") {
                // Handle password update here
                $newPassword = password_hash($_POST['password'],PASSWORD_DEFAULT);  // Encrypt the new password

                // Update the password in the database
                $updateStmt = $conn->prepare("UPDATE users SET password=?, reset_token=NULL, token_expire=NULL WHERE reset_token=?");
                $updateStmt->bind_param("ss", $newPassword, $token);
                $updateStmt->execute();

                if ($updateStmt->affected_rows > 0) {
                    echo "✅ Your password has been reset successfully.";
                } else {
                    echo "❌ Failed to reset password.";
                }
            }

            echo '<form method="POST">
                    New Password: <input type="password" name="password" required>
                    <button type="submit">Reset Password</button>
                  </form>';
        } else {
            echo "❌ The reset token has expired.";
        }
    } else {
        echo "❌ Invalid token.";
    }
} else {
    echo "❌ No token provided.";
}
?>
