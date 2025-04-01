<?php
session_start();

include "connect.php"; // Ensure database connection

// Ensure user is logged in
if (!isset($_SESSION['accountType']) || $_SESSION['accountType'] !== 'commuter') {
    echo "<script>alert('Access denied. Please log in as a commuter.'); window.location.href='login.html';</script>";
    exit();
}
// Fetch commuter's first name from the session
$commuter_name = isset($_SESSION['firstName']) ? $_SESSION['firstName'] : "Commuter";

$commuter_id = $_SESSION['user_id']; // Default to logged-in commuter

// If an admin is viewing, get the commuter's ID from URL
if ($_SESSION['accountType'] === 'administrator' && isset($_GET['id'])) {
    $commuter_id = intval($_GET['id']); // Ensure it's a valid integer
}

// Handle ride rating submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ride_id']) && isset($_POST['rating'])) {
    $ride_id = intval($_POST['ride_id']);
    $rating = intval($_POST['rating']);
    $commuter_id = $_SESSION['user_id'];

    // Insert rating into the database
    $sql = "INSERT INTO ride_ratings (ride_id, commuter_id, rating) VALUES (?, ?, ?)
            ON DUPLICATE KEY UPDATE rating = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iiii", $ride_id, $commuter_id, $rating, $rating);
    $stmt->execute();

    echo "<script>alert('Rating submitted successfully!'); window.location.href='commuter_dashboard.php';</script>";
    exit();
}

// Handle AJAX request to fetch commuter's rides
if (isset($_GET['fetch']) && $_GET['fetch'] === "rides") {
    $sql = "SELECT 
                b.id AS ride_id,
                u.id AS commuter_id,
                CONCAT(u.firstName, ' ', u.lastName) AS commuter_name,
                CONCAT(d.firstName, ' ', d.lastName) AS driver_name,
                b.pickup,
                b.dropoff,
                b.status
            FROM bookings b
            JOIN users u ON b.user_id = u.id
            LEFT JOIN users d ON b.driver_id = d.id
            WHERE u.id = ?";

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        die("SQL Prepare Error: " . $conn->error);
    }

    $stmt->bind_param("i", $commuter_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        echo '<table border="1">
            <thead>
                <tr>
                    <th>Commuter ID</th>
                    <th>Name</th>
                    <th>Ride ID</th>
                    <th>Driver Name</th>
                    <th>Pickup Location</th>
                    <th>Drop-off Location</th>
                    <th>Ride Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>';

        while ($row = $result->fetch_assoc()) {
            echo '<tr>
                    <td>' . htmlspecialchars($row['commuter_id']) . '</td>
                    <td>' . htmlspecialchars($row['commuter_name']) . '</td>
                    <td>' . htmlspecialchars($row['ride_id']) . '</td>
                    <td>' . htmlspecialchars($row['driver_name'] ?? 'Not Assigned') . '</td>
                    <td>' . htmlspecialchars($row['pickup']) . '</td>
                    <td>' . htmlspecialchars($row['dropoff']) . '</td>
                    <td>' . htmlspecialchars($row['status']) . '</td>
                    <td>';
            if ($row['status'] === 'Completed') {
                echo '<button class="action-btn rate-ride" data-ride-id="' . $row['ride_id'] . '">Rate Ride</button>';
            } else {
                echo 'N/A';
            }
            echo '</td>
                </tr>';
        }

        echo '</tbody></table>';
    } else {
        echo '<p>No rides found for this commuter.</p>';
    }
    exit();
}

// Handle AJAX request to fetch upcoming rides
if (isset($_GET['fetch']) && $_GET['fetch'] === "upcoming_rides") {
    $sql = "SELECT 
                b.id AS ride_id,
                u.id AS commuter_id,
                CONCAT(u.firstName, ' ', u.lastName) AS commuter_name,
                CONCAT(d.firstName, ' ', d.lastName) AS driver_name,
                b.pickup,
                b.dropoff,
                b.status,
                b.ride_date
            FROM bookings b
            JOIN users u ON b.user_id = u.id
            LEFT JOIN users d ON b.driver_id = d.id
            WHERE u.id = ? AND b.status IN ('Scheduled', 'Confirmed') AND b.ride_date >= CURDATE()";

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        die("SQL Prepare Error: " . $conn->error);
    }

    $stmt->bind_param("i", $commuter_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        echo '<table border="1">
            <thead>
                <tr>
                    <th>Commuter ID</th>
                    <th>Name</th>
                    <th>Ride ID</th>
                    <th>Driver Name</th>
                    <th>Pickup Location</th>
                    <th>Drop-off Location</th>
                    <th>Ride Date</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>';

        while ($row = $result->fetch_assoc()) {
            echo '<tr>
                    <td>' . htmlspecialchars($row['commuter_id']) . '</td>
                    <td>' . htmlspecialchars($row['commuter_name']) . '</td>
                    <td>' . htmlspecialchars($row['ride_id']) . '</td>
                    <td>' . htmlspecialchars($row['driver_name'] ?? 'Not Assigned') . '</td>
                    <td>' . htmlspecialchars($row['pickup']) . '</td>
                    <td>' . htmlspecialchars($row['dropoff']) . '</td>
                    <td>' . htmlspecialchars($row['ride_date']) . '</td>
                    <td>' . htmlspecialchars($row['status']) . '</td>
                </tr>';
        }

        echo '</tbody></table>';
    } else {
        echo '<p>No rides available.</p>';
        echo '<button onclick="window.location.href=\'ride.html\'" style="background: #0044cc; color: white; padding: 10px 20px; border: none; border-radius: 5px; font-size: 16px; cursor: pointer;">Book Ride Now</button>';
    }
    exit();
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SafariConnect • Commuter Dashboard</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" type="text/css" href="./style.css">
    <link rel="icon" href="https://media.istockphoto.com/id/2070968418/vector/lettering-va-brand-symbol-design.jpg?s=612x612&w=0&k=20&c=5-HWZ5Bf2DDVdcUT1fK51F6TxixVZhAYaBZLJOSug8c=">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body>
    <header>
        <div class="logo">
            <span class="navText">SafariConnect</span>
        </div>
        <nav>
            <ul>
                <li class="navBar"><a href="commuter_dashboard.php" >Home</a></li>
                <li class="navBar"><a href="./ride.html">Book Ride</a></li>
                <li class="navBar"><a href="contact.php">Contact Us</a></li>
                <li class="navBar"><a href="pending_drivers.php">Join Our Network</a></li>
                <li class="navBar"><a href="logout.php">Logout</a></li>
            </ul>
        </nav>
    </header>

    <div id="bodyDiv">
        <h1 id="bodyHeader">
            <h2 id="welcomeMessage">Welcome, <?= htmlspecialchars($commuter_name); ?>!</h2>

            <?php
                if ($_SESSION['accountType'] === 'administrator') {
                    echo "Passenger Dashboard (Admin View)";
                } else {
                    echo "Commuter Dashboard";
                }
            ?>
        </h1>
        <button id="viewRides" class="action-btn">View Rides</button>
        <button id="viewUpcomingRides" class="action-btn">View Upcoming Rides</button>

        <div class="tableDiv" id="tableContainer">
            <!-- Table data will be dynamically inserted here -->
        </div>
    </div>

    <script>
        $(document).ready(function () {
            // Fetch previous rides
            $("#viewRides").click(function () {
                let url = "commuter_dashboard.php?fetch=rides";
                <?php if ($_SESSION['accountType'] === 'administrator' && isset($_GET['id'])) { ?>
                    url += "&id=<?= intval($_GET['id']) ?>";
                <?php } ?>
                $.ajax({
                    url: url,
                    type: "GET",
                    success: function (data) {
                        $("#tableContainer").html(data);
                    }
                });
            });

            // Fetch upcoming rides
            $("#viewUpcomingRides").click(function () {
                let url = "commuter_dashboard.php?fetch=upcoming_rides";
                <?php if ($_SESSION['accountType'] === 'administrator' && isset($_GET['id'])) { ?>
                    url += "&id=<?= intval($_GET['id']) ?>";
                <?php } ?>
                $.ajax({
                    url: url,
                    type: "GET",
                    success: function (data) {
                        $("#tableContainer").html(data);
                    }
                });
            });

            // Handle ride rating
            $(document).on('click', '.rate-ride', function () {
                let rideId = $(this).data('ride-id');
                let rating = prompt("Rate this ride (1-5):");
                if (rating && rating >= 1 && rating <= 5) {
                    $.ajax({
                        url: "commuter_dashboard.php",
                        type: "POST",
                        data: { ride_id: rideId, rating: rating },
                        success: function () {
                            alert("Rating submitted successfully!");
                            $("#viewRides").click(); // Refresh the table
                        }
                    });
                } else {
                    alert("Please enter a valid rating between 1 and 5.");
                }
            });
        });
    </script>
</body>
</html>
