<?php
$newPassword = 'newpassword123'; // Replace with your desired password
$hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
echo $hashedPassword;
?>
