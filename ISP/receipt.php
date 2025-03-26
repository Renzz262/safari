<?php
// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "Debug: receipt.php is loading!<br>";
echo "Debug: Full URL = " . $_SERVER['REQUEST_URI'] . "<br>";
echo "Debug: Query String = " . $_SERVER['QUERY_STRING'] . "<br>";
echo "Debug: \$_GET contents: ";
print_r($_GET);
echo "<br>";

// ✅ Check if ride_id is provided in the URL
if (!isset($_GET['ride_id']) || empty($_GET['ride_id'])) {
    die("No ride selected. Debug: ride_id missing from URL");
}

$ride_id = intval($_GET['ride_id']);
echo "Debug: ride_id received = " . $ride_id . "<br>";

// Database connection
include 'connect.php';

// ✅ Fetch ride details from database
$sql = "SELECT * FROM bookings WHERE id = ?";
$stmt = $conn->prepare($sql);

if (!$stmt) {
    die("SQL Error: " . $conn->error);
}

$stmt->bind_param("i", $ride_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $ride = $result->fetch_assoc();
    echo "<h2>Ride Receipt</h2>";
    echo "<p><strong>Name:</strong> " . htmlspecialchars($ride['name']) . "</p>";
    echo "<p><strong>Pickup Location:</strong> " . htmlspecialchars($ride['pickup']) . "</p>";
    echo "<p><strong>Drop-off Location:</strong> " . htmlspecialchars($ride['dropoff']) . "</p>";
} else {
    die("Error: No ride found in database for ID = " . $ride_id);
}
?>
