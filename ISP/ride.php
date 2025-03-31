<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
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

// Check if form data is received
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    echo "<h3>Debugging Output:</h3>";
    var_dump($_POST);
    var_dump($_SESSION);

    // Ensure all required fields are received
    if (!isset($_POST['name'], $_POST['pickup'], $_POST['dropoff'], $_POST['ride_date'])) {
        die("<p style='color: red;'>Error: Missing form data!</p>");
    }

    // Escape user input to prevent SQL injection
    $name = $conn->real_escape_string($_POST['name']);
    $pickup = $conn->real_escape_string($_POST['pickup']);
    $dropoff = $conn->real_escape_string($_POST['dropoff']);
    $ride_date = $conn->real_escape_string($_POST['ride_date']);

    // Validate ride date
    $today = date('Y-m-d');
    if ($ride_date < $today) {
        die("<p style='color: red;'>Error: Ride date must be today or in the future.</p>");
    }

    // Check if the user is logged in as a commuter
    if (isset($_SESSION['user_id']) && isset($_SESSION['accountType']) && $_SESSION['accountType'] === 'commuter') {
        $user_id = $_SESSION['user_id'];

        // Insert ride into the bookings table
        $sql = "INSERT INTO bookings (user_id, name, driver_id, pickup, dropoff, status, ride_date) 
                VALUES (?, ?, NULL, ?, ?, 'Scheduled', ?)";
        
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            die("<p style='color: red;'>Prepare failed: " . $conn->error . "</p>");
        }

        $stmt->bind_param('issss', $user_id, $name, $pickup, $dropoff, $ride_date);

        if ($stmt->execute()) {
            echo "<p style='color: green;'>Debug: New booking ID = " . $conn->insert_id . "</p>";
            echo '<script>alert("Ride booked successfully!"); window.location.href = "commuter_dashboard.php";</script>';
        } else {
            die("<p style='color: red;'>Execute failed: " . $stmt->error . "</p>");
        }

        $stmt->close();
    } else {
        die("<p style='color: red;'>Error: You must be logged in as a commuter to book a ride.</p>");
    }
}

$conn->close();
?>
