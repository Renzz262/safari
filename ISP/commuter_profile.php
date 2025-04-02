<?php
session_start();
include "connect.php";

// Ensure user is logged in
if (!isset($_SESSION['accountType']) || $_SESSION['accountType'] !== 'commuter') {
    echo "<script>alert('Access denied. Please log in as a commuter.'); window.location.href='login.html';</script>";
    exit();
}

$user_id = $_SESSION['user_id'];

// Fetch user details
$sql = "SELECT firstName, lastName, email, profile_picture FROM users WHERE id = ?";
$stmt = $conn->prepare($sql);
if ($stmt) {
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $stmt->close();
} else {
    die("SQL Error: " . $conn->error);
}

$profile_picture = isset($user['profile_picture']) && $user['profile_picture'] ? 'uploads/' . $user['profile_picture'] : 'default_profile.png';

// Handle profile picture upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['profile_picture'])) {
    $target_dir = "uploads/"; // Ensure upload directory exists
    if (!is_dir($target_dir)) {
        mkdir($target_dir, 0777, true); // Create the directory if it doesn't exist
    }

    $filename = preg_replace("/[^a-zA-Z0-9\._-]/", "_", basename($_FILES["profile_picture"]["name"])); 
    $target_file = $target_dir . $filename;

    $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
    $allowed_types = ['jpg', 'jpeg', 'png', 'gif'];

    if (in_array($imageFileType, $allowed_types)) {
        if (move_uploaded_file($_FILES["profile_picture"]["tmp_name"], $target_file)) {
            // Save the sanitized filename in the database
            $update_sql = "UPDATE users SET profile_picture = ? WHERE id = ?";
            $stmt = $conn->prepare($update_sql);
            if ($stmt) {
                $stmt->bind_param("si", $filename, $user_id);
                $stmt->execute();
                $stmt->close();
                echo "<script>alert('Profile picture updated successfully!'); window.location.href='commuter_profile.php';</script>";
            } else {
                die("SQL Error: " . $conn->error);
            }
        } else {
            echo "<script>alert('Error uploading file. Please try again.');</script>";
        }
    } else {
        echo "<script>alert('Invalid file format. Please upload JPG, JPEG, PNG, or GIF.');</script>";
    }
}

// Handle profile picture removal
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['remove_picture'])) {
    // Fetch current profile picture filename from the database
    $fetch_sql = "SELECT profile_picture FROM users WHERE id = ?";
    $stmt = $conn->prepare($fetch_sql);
    if ($stmt) {
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $stmt->bind_result($profile_picture);
        $stmt->fetch();
        $stmt->close();

        if ($profile_picture) {
            $file_path = "uploads/" . $profile_picture;
            if (file_exists($file_path)) {
                unlink($file_path); // Delete the file from the server
            }
        }

        // Update database to remove the profile picture reference
        $update_sql = "UPDATE users SET profile_picture = NULL WHERE id = ?";
        $stmt = $conn->prepare($update_sql);
        if ($stmt) {
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $stmt->close();
            echo "<script>alert('Profile picture removed successfully!'); window.location.href='commuter_profile.php';</script>";
        } else {
            die("SQL Error: " . $conn->error);
        }
    } else {
        die("SQL Error: " . $conn->error);
    }
}


?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Commuter Profile</title>
        <link rel="icon" href="https://media.istockphoto.com/id/2070968418/vector/lettering-va-brand-symbol-design.jpg?s=612x612&w=0&k=20&c=5-HWZ5Bf2DDVdcUT1fK51F6TxixVZhAYaBZLJOSug8c=">

    <link rel="stylesheet" type="text/css" href="style.css">
    <style>
        .profile-container {
            text-align: center;
            margin-top: 50px;
        }
        .profile-picture {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid #0044cc;
        }
        .button {
            background-color: #0044cc;
            color: white;
            padding: 10px;
            border: none;
            cursor: pointer;
            margin: 10px;
        }
    </style>
</head>
<body>
    <header>
        <div class="logo">
            <span class="navText">SafariConnect</span>
        </div>
        <nav>
            <ul>
                <li class="navBar"><a href="commuter_dashboard.php" >Home</a></li>
                <li class="navBar"><a href="./ride.html">Book Ride</a></li>
                <li class="navBar"><a href="contact.php">Contact Us</a></li>
                <li class="navBar"><a href="pending_drivers.php">Join Our Network</a></li>
                 <li class="navBar"><a href="commuter_profile.php"> Profile</a></li>
                <li class="navBar"><a href="logout.php">Logout</a></li>
            </ul>
        </nav>
    </header>

    <br>
    <br>
    <br>
    <div class="profile-container">
        <h2>Your Profile</h2>
        <img src="<?= htmlspecialchars($profile_picture) ?>" alt="Profile Picture" class="profile-picture">
        <p><strong>Name:</strong> <?= htmlspecialchars($user['firstName'] . ' ' . $user['lastName']) ?></p>
        <p><strong>Email:</strong> <?= htmlspecialchars($user['email']) ?></p>
        
        <form method="POST" enctype="multipart/form-data">
            <input type="file" name="profile_picture" required>
            <button type="submit" class="button">Upload Profile Picture</button>
        </form>

        <form method="POST">
            <button type="submit" name="remove_picture" class="button" style="background-color: red;">Remove Profile Picture</button>
        </form>
    </div>
</body>
</html>
