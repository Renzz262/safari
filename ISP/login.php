<?php
session_start();
session_regenerate_id(true); // Prevent session fixation attacks

include 'connect.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);

    if (empty($email) || empty($password)) {
        die("<script>alert('Error: Email and password are required.'); window.location.href='login.html';</script>");
    }

    $sql = "SELECT id, password, accountType FROM users WHERE email = ?";
    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        die("<script>alert('SQL Error: " . $conn->error . "'); window.location.href='login.html';</script>");
    }

    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $row = $result->fetch_assoc();

        $hashedPasswordFromDB = $row['password'];

        // ✅ Verify password
        if (password_verify($password, $hashedPasswordFromDB)) {
            $_SESSION['user_id'] = $row['id'];
            $_SESSION['accountType'] = strtolower($row['accountType']); // Store in lowercase

            // Redirect based on account type
            if ($_SESSION['accountType'] === 'driver') {
                header("Location: driver_dashboard.php");
                exit();
            } else {
                header("Location: home.html");
                exit();
            }
        } else {
            echo "<script>alert('Error: Invalid password.'); window.location.href='login.html';</script>";
            exit();
        }
    } else {
        echo "<script>alert('Error: No user found with this email. Please check if you registered correctly.'); window.location.href='register.html';</script>";
        exit();
    }
}
?>
