<?php
session_start();
include "connect.php";

// Ensure driver is logged in
if (!isset($_SESSION['accountType']) || $_SESSION['accountType'] !== 'driver') {
    echo "<script>alert('Access denied. Please log in as a driver.'); window.location.href='login.html';</script>";
    exit();
}

$driver_id = $_SESSION['user_id'];

// Fetch driver details
$sql = "SELECT firstName, lastName, email, profile_picture FROM users WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $driver_id);
$stmt->execute();
$result = $stmt->get_result();
$driver = $result->fetch_assoc();

$profile_picture = $driver['profile_picture'] ? 'uploads/' . $driver['profile_picture'] : 'default_profile.png';

// Handle profile picture upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['profile_picture'])) {
    $target_dir = "uploads/";
    $target_file = $target_dir . basename($_FILES["profile_picture"]["name"]);
    $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
    $allowed_types = ['jpg', 'jpeg', 'png', 'gif'];

    if (in_array($imageFileType, $allowed_types)) {
        if (move_uploaded_file($_FILES["profile_picture"]["tmp_name"], $target_file)) {
            $update_sql = "UPDATE users SET profile_picture = ? WHERE id = ?";
            $stmt = $conn->prepare($update_sql);
            $stmt->bind_param("si", $_FILES['profile_picture']['name'], $driver_id);
            $stmt->execute();
            echo "<script>alert('Profile picture updated successfully!'); window.location.href='driver_profile.php';</script>";
        }
    } else {
        echo "<script>alert('Invalid file format. Please upload JPG, JPEG, PNG, or GIF.');</script>";
    }
}

// Handle profile picture removal
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['remove_picture'])) {
    $update_sql = "UPDATE users SET profile_picture = NULL WHERE id = ?";
    $stmt = $conn->prepare($update_sql);
    $stmt->bind_param("i", $driver_id);
    $stmt->execute();
    echo "<script>alert('Profile picture removed successfully!'); window.location.href='driver_profile.php';</script>";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Driver Profile</title>
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
                <li class="navBar"><a href="driver_dashboard.php">Home</a></li>
                <li class="navBar"><a href="driver_rides_history.php">View Ride History</button>
                <li class="navBar"><a href="driver_profile.php"> Profile</a></li>
                <li class="navBar"><a href="logout.php">Logout</a></li>
            </ul>
        </nav>
    </header>
    <br>
    <br>
    <br>
    <div class="profile-container">
        <h2>Driver Profile</h2>
        <img src="<?= $profile_picture ?>" alt="Profile Picture" class="profile-picture">
        <p><strong>Name:</strong> <?= htmlspecialchars($driver['firstName'] . ' ' . $driver['lastName']) ?></p>
        <p><strong>Email:</strong> <?= htmlspecialchars($driver['email']) ?></p>
        
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
