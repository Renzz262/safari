<?php
session_start();

// Database connection
$host = 'localhost';
$username = 'root';
$password = '';
$dbname = 'safari';

$conn = new mysqli($host, $username, $password, $dbname);

if ($conn->connect_error) {
    die('Connection failed: ' . $conn->connect_error);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $conn->real_escape_string($_POST['name']);
    $pickup = $conn->real_escape_string($_POST['pickup']);
    $drop = $conn->real_escape_string($_POST['drop']);

    // Check if the user is logged in
    if (isset($_SESSION['user_id']) && $_SESSION['accType'] === 'customer') {
        $user_id = $_SESSION['user_id'];

        // Insert ride into the database
        $sql = "INSERT INTO rides (user_id, name, pickup_location, dropoff_location, ride_status, created_at) VALUES (?, ?, ?, ?, 'pending', NOW())";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('isss', $user_id, $name, $pickup, $drop);

        if ($stmt->execute()) {
            echo '<script>alert("Ride booked successfully!"); window.location.href = "ride.html";</script>';
        } else {
            echo '<script>alert("Failed to book ride. Please try again."); window.location.href = "ride.html";</script>';
        }
        
        $stmt->close();
    } else {
        echo '<script>alert("You must be logged in as a customer to book a ride."); window.location.href = "login.html";</script>';
    }
}

$conn->close();
?>