<?php
session_start();
include 'connect.php';

// ✅ Check if the driver is logged in
if (!isset($_SESSION['user_id']) || strtolower($_SESSION['accountType']) !== 'driver') {
    die("<script>alert('Access denied. Please log in as a driver.'); window.location.href='login.html';</script>");
}

$driver_id = $_SESSION['user_id']; // ✅ Get logged-in driver ID
echo "Debug: Logged-in Driver ID: " . $driver_id . "<br>";

// ✅ SQL query to fetch bookings for the driver
$sql = "SELECT id, name, pickup, dropoff FROM bookings WHERE driver_id = ?";
$stmt = $conn->prepare($sql);

if (!$stmt) {
    die("SQL Error: " . $conn->error);
}

// ✅ Bind driver_id to the query
$stmt->bind_param("i", $driver_id);
$stmt->execute();
$result = $stmt->get_result();

echo "<h2>My Assigned Bookings</h2>";

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        // ✅ Ensure `id` exists
        $bookingId = isset($row['id']) ? $row['id'] : 'N/A';
        echo "<p>Booking ID: " . $bookingId . " | Passenger: " . $row['name'] . " | Pickup: " . $row['pickup'] . " | Dropoff: " . $row['dropoff'] . "</p>";
    }
} else {
    echo "<p>No bookings assigned yet.</p>";
}

// ✅ Close statement & database connection
$stmt->close();
$conn->close();

?>
    <link rel="stylesheet" type="text/css" href="./style.css">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SafariConnect • Book Ride</title>
    <link rel="stylesheet" type="text/css" href="./style.css">
    <link rel="icon" href="https://media.istockphoto.com/id/2070968418/vector/lettering-va-brand-symbol-design.jpg?s=612x612&w=0&k=20&c=5-HWZ5Bf2DDVdcUT1fK51F6TxixVZhAYaBZLJOSug8c=">
</head>
