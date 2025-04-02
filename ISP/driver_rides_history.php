<?php
session_start();
include "connect.php";  

if (!isset($_SESSION['accountType']) || $_SESSION['accountType'] !== 'driver') {
    echo "<script>alert('Access denied. Please log in as a driver.'); window.location.href='login.html';</script>";
    exit();
}

$driver_id = $_SESSION['user_id']; 

$sql = "SELECT 
            b.id AS ride_id,
            CONCAT(u.firstName, ' ', u.lastName) AS commuter_name,
            b.pickup,
            b.dropoff,
            b.ride_date,
            b.status
        FROM bookings b
        JOIN users u ON b.user_id = u.id
        WHERE b.driver_id = ? AND b.status IN ('Accepted', 'Declined', 'Completed')
        ORDER BY b.ride_date DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $driver_id);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SafariConnect • Ride History</title>
            <link rel="icon" href="https://media.istockphoto.com/id/2070968418/vector/lettering-va-brand-symbol-design.jpg?s=612x612&w=0&k=20&c=5-HWZ5Bf2DDVdcUT1fK51F6TxixVZhAYaBZLJOSug8c=">

    <link rel="stylesheet" type="text/css" href="./style.css">
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

    <div id="bodyDiv">
        <h1 id="bodyHeader">Ride History</h1>
        <table border="1">
            <thead>
                <tr>
                    <th>Commuter Name</th>
                    <th>Ride ID</th>
                    <th>Pickup Location</th>
                    <th>Drop-off Location</th>
                    <th>Ride Date</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $result->fetch_assoc()) { ?>
                    <tr>
                        <td><?= htmlspecialchars($row['commuter_name']) ?></td>
                        <td><?= htmlspecialchars($row['ride_id']) ?></td>
                        <td><?= htmlspecialchars($row['pickup']) ?></td>
                        <td><?= htmlspecialchars($row['dropoff']) ?></td>
                        <td><?= htmlspecialchars($row['ride_date']) ?></td>
                        <td><?= htmlspecialchars($row['status']) ?></td>
                    </tr>
                <?php } ?>
            </tbody>
        </table>
    </div>
</body>
</html>

<?php
$stmt->close();
?>
