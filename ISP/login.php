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

    // Check if this is a password reset request
    if (isset($_POST['reset_password'])) {
        $resetEmail = mysqli_real_escape_string($conn, $email);

        $sql = "SELECT id FROM users WHERE email = ?";
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            die("<script>alert('SQL Error: " . $conn->error . "'); window.location.href='login.html';</script>");
        }
        $stmt->bind_param("s", $resetEmail);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $user_id = $row['id'];

            // Generate a new password meeting the criteria
            $newPassword = generateSecurePassword(12); // 12 characters, adjustable
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);

            // Update the password in the database
            $sql = "UPDATE users SET password = ? WHERE id = ?";
            $stmt = $conn->prepare($sql);
            if (!$stmt) {
                die("<script>alert('SQL Error: " . $conn->error . "'); window.location.href='login.html';</script>");
            }
            $stmt->bind_param("si", $hashedPassword, $user_id);
            if (!$stmt->execute()) {
                die("<script>alert('Error updating password: " . $stmt->error . "'); window.location.href='login.html';</script>");
            }

            // Display the new password (for testing; use email in production)
            echo "<script>alert('Your new password is: " . $newPassword . ". Please log in with this password and change it.'); window.location.href='login.html';</script>";
            exit();
        } else {
            echo "<script>alert('Email not found.'); window.location.href='login.html';</script>";
            exit();
        }
    }

    // Normal login process
    $sql = "SELECT id, firstName, lastName, password, accountType FROM users WHERE email = ?";
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
            $_SESSION['accountType'] = strtolower($row['accountType']); // Store in lowercase for consistency
            $_SESSION['firstName'] = $row['firstName']; // ✅ Store first name
            $_SESSION['lastName'] = $row['lastName']; // ✅ Store last name

            // 🔹 Redirect based on user role
            switch ($_SESSION['accountType']) {
                case 'administrator':
                    header("Location: admin_dashboard.php");
                    break;
                case 'driver':
                    header("Location: driver_dashboard.php"); // ✅ Redirect drivers properly
                    break;
                case 'commuter':
                    header("Location: commuter_dashboard.php");
                    break;
                default:
                    echo "<script>alert('Error: Unknown account type.'); window.location.href='login.html';</script>";
                    exit();
            }
            exit();
        } else {
            echo "<script>alert('Error: Invalid password.'); window.location.href='login.html';</script>";
            exit();
        }
    } else {
        echo "<script>alert('Error: No user found with this email. Please check if you registered correctly.'); window.location.href='register.html';</script>";
        exit();
    }
}

// Function to generate a secure password meeting the criteria
function generateSecurePassword($length = 12) {
    if ($length < 8) {
        $length = 8; // Minimum length
    }

    // Define character sets
    $uppercase = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $lowercase = 'abcdefghijklmnopqrstuvwxyz';
    $numbers = '0123456789';
    $specialChars = '!@#$%^&*()_+-=[]{}|;:,.<>?';

    // Ensure at least one character from each set
    $password = [
        $uppercase[rand(0, strlen($uppercase) - 1)], // One uppercase
        $lowercase[rand(0, strlen($lowercase) - 1)], // One lowercase
        $numbers[rand(0, strlen($numbers) - 1)],     // One number
        $specialChars[rand(0, strlen($specialChars) - 1)] // One special char
    ];

    // Fill the remaining length with random characters from all sets
    $allChars = $uppercase . $lowercase . $numbers . $specialChars;
    for ($i = 4; $i < $length; $i++) {
        $password[] = $allChars[rand(0, strlen($allChars) - 1)];
    }

    // Shuffle the password array and join into a string
    shuffle($password);
    return implode('', $password);
}
?>
