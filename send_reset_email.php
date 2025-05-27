<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php'; // PHPMailer via Composer

// Database connection
$conn = new mysqli("localhost", "root", "", "driveease");

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email']);

    $token = bin2hex(random_bytes(50));
    $expire = date("Y-m-d H:i:s", strtotime("+1 hour"));

    // Check if email exists first
    $check = $conn->prepare("SELECT * FROM users WHERE email = ?");
    $check->bind_param("s", $email);
    $check->execute();
    $result = $check->get_result();

    if ($result->num_rows > 0) {
        // Email exists, update token
        $stmt = $conn->prepare("UPDATE users SET reset_token=?, token_expire=? WHERE email=?");
        $stmt->bind_param("sss", $token, $expire, $email);
        $stmt->execute();

        if ($stmt->affected_rows > 0) {
            $resetLink = "http://localhost/driveease/reset_password.php?token=$token";

            // PHPMailer setup
            $mail = new PHPMailer(true);

            try {
                $mail->isSMTP();  
                $mail->Host = 'smtp.gmail.com';  
                $mail->SMTPAuth = true;  
                $mail->SMTPSecure = 'tls'; // Change to 'tls' for port 587
                $mail->Port = 587; // Use 587 for TLS
                $mail->Username = 'laguatanjustine780@gmail.com'; // Replace with your email
                $mail->Password = 'hvisklspgauwfxsl'; // Use the app password here (16-character)

                $mail->setFrom('laguatanjustine780@gmail.com', 'Mail');
                $mail->addAddress($email);
                $mail->Subject = 'Password Reset Request';
                $mail->Body = "Click this link  to reset your password: $resetLink";

                $mail->send();
                echo "✅ Reset link sent to your email.";
            } catch (Exception $e) {
                echo "❌ Mailer Error: {$mail->ErrorInfo}";
            }
        } else {
            echo "⚠️ Failed to update token.";
        }
    } else {
        echo "❌ Email not found.";
    }
}
?>