<?php
session_start();
include "connect.php";  // Ensure database connection

// Ensure the driver is logged in
if (!isset($_SESSION['accountType']) || $_SESSION['accountType'] !== 'driver') {
    echo "<script>alert('Access denied. Please log in as a driver.'); window.location.href='login.html';</script>";
    exit();
}
// Fetch driver details from session
$driver_name = isset($_SESSION['firstName']) ? $_SESSION['firstName'] : "Driver"; 

$driver_id = $_SESSION['user_id']; // Default to logged-in driver

// Handle AJAX request to fetch the driver's upcoming rides
if (isset($_GET['fetch']) && $_GET['fetch'] === "upcoming_rides") {
    $sql = "SELECT 
                b.id AS ride_id,
                u.id AS commuter_id,
                CONCAT(u.firstName, ' ', u.lastName) AS commuter_name,
                b.pickup,
                b.dropoff,
                b.status,
                b.ride_date
            FROM bookings b
            JOIN users u ON b.user_id = u.id
            WHERE b.driver_id = ? AND b.status IN ('Scheduled', 'Confirmed', 'Accepted') AND b.ride_date >= CURDATE()";

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        die("SQL Prepare Error: " . $conn->error);
    }

    $stmt->bind_param("i", $driver_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        echo '<table border="1">
            <thead>
                <tr>
                    <th>Commuter Name</th>
                    <th>Ride ID</th>
                    <th>Pickup Location</th>
                    <th>Drop-off Location</th>
                    <th>Ride Date</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>';

        while ($row = $result->fetch_assoc()) {
            echo '<tr>
                    <td>' . htmlspecialchars($row['commuter_name']) . '</td>
                    <td>' . htmlspecialchars($row['ride_id']) . '</td>
                    <td>' . htmlspecialchars($row['pickup']) . '</td>
                    <td>' . htmlspecialchars($row['dropoff']) . '</td>
                    <td>' . htmlspecialchars($row['ride_date']) . '</td>
                    <td>' . htmlspecialchars($row['status']) . '</td>';
            
            // If the booking is scheduled and not yet accepted, show accept/decline buttons
            if ($row['status'] == 'Scheduled') {
                echo "<td><button class='action-btn accept-btn' data-ride-id='" . $row['ride_id'] . "'>Accept</button>";
                echo "<button class='action-btn decline-btn' data-ride-id='" . $row['ride_id'] . "'>Decline</button></td>";
            } else {
                echo "<td>No action available</td>";
            }
            echo "</tr>";
        }

        echo '</tbody></table>';
    } else {
        echo '<p>No upcoming rides found for this driver.</p>';
    }

    $stmt->close();
    exit();
}

// Handle Accept/Decline actions
if (isset($_POST['action']) && isset($_POST['ride_id'])) {
    $ride_id = $_POST['ride_id'];
    $action = $_POST['action'];

    if ($action == 'accept') {
        $status = 'Accepted';
    } elseif ($action == 'decline') {
        $status = 'Declined';
    }

    // Update the status of the booking
    $sql = "UPDATE bookings SET status = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        die("SQL Prepare Error: " . $conn->error);
    }

    $stmt->bind_param("si", $status, $ride_id);
    
    if ($stmt->execute()) {
        echo "Booking status updated to: " . $status;
    } else {
        echo "Error updating booking: " . $stmt->error;
    }

    $stmt->close();
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SafariConnect • Driver Dashboard</title>
        <link rel="icon" href="https://media.istockphoto.com/id/2070968418/vector/lettering-va-brand-symbol-design.jpg?s=612x612&w=0&k=20&c=5-HWZ5Bf2DDVdcUT1fK51F6TxixVZhAYaBZLJOSug8c=">

    <link rel="stylesheet" type="text/css" href="./style.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body>
    <header>
        <div class="logo">
            <span class="navText">SafariConnect</span>
        </div>
        <nav>
            <ul>
                <li class="navBar"><a href="driver_dashboard.php" class="activePage">Home</a></li>
                <li class="navBar"><a href="driver_rides_history.php">View Ride History</button>
                <li class="navBar"><a href="driver_profile.php"> Profile</a></li>
                <li class="navBar"><a href="logout.php">Logout</a></li>
            </ul>
        </nav>
    </header>

    <div id="bodyDiv">
        <h1 id="bodyHeader">Driver Dashboard</h1>
        <h2 id="welcomeMessage">Welcome, <?= htmlspecialchars($driver_name); ?>!</h2> <!-- Welcome message -->
        <button id="viewUpcomingRides" class="action-btn">View Upcoming Rides</button>

        <div class="tableDiv" id="tableContainer">
            <!-- Table data will be dynamically inserted here -->
        </div>
    </div>

    <script>
        $(document).ready(function () {
            // Fetch upcoming rides for the driver
            $("#viewUpcomingRides").click(function () {
                $.ajax({
                    url: "driver_dashboard.php?fetch=upcoming_rides",
                    type: "GET",
                    success: function (data) {
                        $("#tableContainer").html(data);
                    }
                });
            });

            // Handle Accept/Decline button clicks
            $(document).on('click', '.accept-btn', function() {
                var rideId = $(this).data('ride-id');
                $.ajax({
                    url: 'driver_dashboard.php',
                    type: 'POST',
                    data: { ride_id: rideId, action: 'accept' },
                    success: function(response) {
                        alert(response); // Show response message
                        // Re-fetch upcoming rides dynamically without reloading
                        $.ajax({
                            url: "driver_dashboard.php?fetch=upcoming_rides",
                            type: "GET",
                            success: function (data) {
                                $("#tableContainer").html(data);  // Update table data
                            }
                        });
                    }
                });
            });

            $(document).on('click', '.decline-btn', function() {
                var rideId = $(this).data('ride-id');
                $.ajax({
                    url: 'driver_dashboard.php',
                    type: 'POST',
                    data: { ride_id: rideId, action: 'decline' },
                    success: function(response) {
                        alert(response); // Show response message
                        // Re-fetch upcoming rides dynamically without reloading
                        $.ajax({
                            url: "driver_dashboard.php?fetch=upcoming_rides",
                            type: "GET",
                            success: function (data) {
                                $("#tableContainer").html(data);  // Update table data
                            }
                        });
                    }
                });
            });
        });
    </script>
</body>
</html>
