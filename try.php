<?php
$plain_password = '123'; // Replace with your password
$hashed_password = password_hash($plain_password, PASSWORD_BCRYPT);

echo "Hashed Password: " . $hashed_password;
?>
    