<?php
session_start();
include "connect.php"; // Ensure database connection

// Ensure user is logged in
if (!isset($_SESSION['accountType']) || $_SESSION['accountType'] !== 'commuter') {
    echo "<script>alert('Access denied. Please log in as a commuter.'); window.location.href='login.html';</script>";
    exit();
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['full_name']);
    $age = intval($_POST['age']);
    $email = trim($_POST['email']);
    $password = password_hash(trim($_POST['password']), PASSWORD_DEFAULT);
    $resume = trim($_POST['resume']);
    $user_id = $_SESSION['user_id'];

    // Insert into database
    $sql = "INSERT INTO driver_applications (user_id, full_name, age, email, password, resume, status) 
            VALUES (?, ?, ?, ?, ?, ?, 'Pending')";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("isssss", $user_id, $full_name, $age, $email, $password, $resume);
    
    if ($stmt->execute()) {
        echo "<script>alert('Your resume has been received. We\'ll get back to you within 7 days.'); window.location.href='commuter_dashboard.php';</script>";
    } else {
        error_log("SQL Error: " . $stmt->error);
        echo "<script>alert('Error submitting form. Please try again later.');</script>";
    }
}


?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Join Our Network</title>
    <link rel="stylesheet" type="text/css" href="style.css">
    <link rel="icon" href="https://media.istockphoto.com/id/2070968418/vector/lettering-va-brand-symbol-design.jpg?s=612x612&w=0&k=20&c=5-HWZ5Bf2DDVdcUT1fK51F6TxixVZhAYaBZLJOSug8c=">
</head>
<body>
    <header>
        <div class="logo">
            <span class="navText">SafariConnect</span>
        </div>
        <nav>
            <ul>
                <li class="navBar"><a href="commuter_dashboard.php">Home</a></li>
                <li class="navBar"><a href="./ride.html">Book Ride</a></li>
                <li class="navBar"><a href="contact.php">Contact Us</a></li>
                <li class="navBar"><a href="pending_drivers.php">Join Our Network</a></li>
                <li class="navBar"><a href="logout.php">Logout</a></li>
            </ul>
        </nav>
    </header>

    <br><br><br>
    <h2>Interested in a driver role? Fill the form below</h2>
    <form method="POST">
        <label>Full Name:</label>
        <input type="text" name="full_name" required><br>
        
        <label>Age:</label>
        <input type="number" name="age" required><br>
        
        <label>Email:</label>
        <input type="email" name="email" required><br>
        
        <label>Password:</label>
        <input type="password" name="password" required><br>
        
        <label>Resume / CV:</label>
        <textarea name="resume" required></textarea><br>
        
        <button type="submit">Submit Application</button>
    </form>
</body>
</html>
