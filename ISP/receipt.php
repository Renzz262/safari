<?php
include 'connect.php'; // Ensure DB connection

if (!isset($_GET['ride_id'])) {
    die("No ride selected.");
}

$ride_id = intval($_GET['ride_id']); // Ensure ride_id is numeric

$sql = "SELECT id, name, pickup, dropoff FROM bookings WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $ride_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $ride = $result->fetch_assoc();
    ?>
    <h2>Ride Receipt</h2>
    <p><strong>Ride ID:</strong> <?php echo htmlspecialchars($ride['id']); ?></p>
    <p><strong>Name:</strong> <?php echo htmlspecialchars($ride['name']); ?></p>
    <p><strong>Pickup Location:</strong> <?php echo htmlspecialchars($ride['pickup']); ?></p>
    <p><strong>Dropoff Location:</strong> <?php echo htmlspecialchars($ride['dropoff']); ?></p>
    <p><strong>Status:</strong> Confirmed</p>
    <?php
} else {
    echo "Ride not found.";
}

$conn->close();
?> 
