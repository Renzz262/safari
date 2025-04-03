<?php
session_start();
include "connect.php";

// Ensure only admins can access
if (!isset($_SESSION['accountType']) || $_SESSION['accountType'] !== 'administrator') {
    echo "<script>alert('Access denied. Only administrators can view this page.'); window.location.href='login.html';</script>";
    exit();
}

$admin_id = $_SESSION['user_id'];

// Fetch admin details
$sql = "SELECT firstName, lastName, email, profile_picture FROM users WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $admin_id);
$stmt->execute();
$result = $stmt->get_result();
$admin = $result->fetch_assoc();
$stmt->close();

// Handle profile picture upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['profile_picture'])) {
    $target_dir = "uploads/";
    $filename = preg_replace("/[^a-zA-Z0-9._-]/", "_", basename($_FILES["profile_picture"]["name"]));
    $target_file = $target_dir . $filename;
    
    $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
    $allowed_types = ['jpg', 'jpeg', 'png', 'gif'];
    
    if (in_array($imageFileType, $allowed_types) && move_uploaded_file($_FILES['profile_picture']['tmp_name'], $target_file)) {
        $update_sql = "UPDATE users SET profile_picture = ? WHERE id = ?";
        $stmt = $conn->prepare($update_sql);
        $stmt->bind_param("si", $filename, $admin_id);
        $stmt->execute();
        $stmt->close();
        echo "<script>alert('Profile picture updated!'); window.location.href='admin_profile.php';</script>";
    } else {
        echo "<script>alert('Invalid file format or upload error.');</script>";
    }
}

// Handle profile picture removal
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['remove_picture'])) {
    if ($admin['profile_picture']) {
        $file_path = "uploads/" . $admin['profile_picture'];
        if (file_exists($file_path)) {
            unlink($file_path);
        }
    }
    $update_sql = "UPDATE users SET profile_picture = NULL WHERE id = ?";
    $stmt = $conn->prepare($update_sql);
    $stmt->bind_param("i", $admin_id);
    $stmt->execute();
    $stmt->close();
    echo "<script>alert('Profile picture removed!'); window.location.href='admin_profile.php';</script>";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Profile</title>
    <link rel="icon" href="https://media.istockphoto.com/id/2070968418/vector/lettering-va-brand-symbol-design.jpg?s=612x612&w=0&k=20&c=5-HWZ5Bf2DDVdcUT1fK51F6TxixVZhAYaBZLJOSug8c=">
    <link rel="stylesheet" type="text/css" href="style.css">
</head>
<body>
    <header>
        <div class="logo">
            <span class="navText">SafariConnect</span>
        </div>
        <nav>
            <ul>
                <li class="navBar"><a href="admin_dashboard.php">Home</a></li>
                <li class="navBar"><a href="manage_users.php">Manage Users</a></li>
                <li class="navBar"><a href="driver_applications.php">View Driver Applications</a></li>
                <li class="navBar"><a href="admin_profile.php">Profile</a></li>
                <li class="navBar"><a href="login.html">Logout</a></li>
            </ul>
        </nav>
    </header>

    <div id="bodyDiv">
        <h1 id="bodyHeader">Admin Profile</h1>
        <div class="profile-form-container">
            <div class="profile-form">
                <img src="uploads/<?= htmlspecialchars($admin['profile_picture']) ? $admin['profile_picture'] : 'default_profile.png' ?>" alt="Profile Picture" class="profile-picture">
                <p><strong>Name:</strong> <?= htmlspecialchars($admin['firstName'] . ' ' . $admin['lastName']) ?></p>
                <p><strong>Email:</strong> <?= htmlspecialchars($admin['email']) ?></p>

                <form method="POST" enctype="multipart/form-data">
                    <input type="file" name="profile_picture" required>
                    <button type="submit" class="button">Upload Profile Picture</button>
                </form>

                <form method="POST">
                    <button type="submit" name="remove_picture" class="button red">Remove Profile Picture</button>
                </form>

                <a href="admin_dashboard.php" class="button">Back to Dashboard</a>
            </div>
        </div>
    </div>

    <footer>
        <p>© 2025 SafariConnect LTD |</p>
    </footer>
</body>
</html>
