<?php
session_start();
include 'connect.php';

// Check if the driver is logged in
if (!isset($_SESSION['user_id']) || strtolower($_SESSION['accountType']) !== 'driver') {
    die("<script>alert('Access denied. Please log in as a driver.'); window.location.href='login.html';</script>");
}

$driver_id = $_SESSION['user_id'];

// Check if we have necessary data
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && isset($_POST['booking_id'])) {
    $booking_id = $_POST['booking_id'];
    $action = $_POST['action'];
    
    // For accepted bookings
    if ($action === 'accept') {
        // Check if your table has a status field - if not, you might need to create it
        // You can add a SQL query here to alter the table if needed
        
        // For now, we'll assume the status field exists or doesn't matter
        $update_sql = "UPDATE bookings SET status = 'Accepted' WHERE id = ? AND driver_id = ?";
        $update_stmt = $conn->prepare($update_sql);
        
        if ($update_stmt) {
            $update_stmt->bind_param("ii", $booking_id, $driver_id);
            $update_stmt->execute();
            $update_stmt->close();
            echo "<script>alert('Booking accepted successfully!'); window.location.href='driver_dashboard.php';</script>";
        } else {
            echo "<script>alert('Error accepting booking: " . $conn->error . "'); window.location.href='driver_dashboard.php';</script>";
        }
    } 
    // For declined bookings
    elseif ($action === 'decline') {
        $update_sql = "UPDATE bookings SET status = 'Declined', driver_id = NULL WHERE id = ? AND driver_id = ?";
        $update_stmt = $conn->prepare($update_sql);
        
        if ($update_stmt) {
            $update_stmt->bind_param("ii", $booking_id, $driver_id);
            $update_stmt->execute();
            $update_stmt->close();
            echo "<script>alert('Booking declined.'); window.location.href='driver_dashboard.php';</script>";
        } else {
            echo "<script>alert('Error declining booking: " . $conn->error . "'); window.location.href='driver_dashboard.php';</script>";
        }
    }
} else {
    // No valid action found, redirect back to dashboard
    header('Location: driver_dashboard.php');
    exit;
}

// Close database connection
$conn->close();
?>
