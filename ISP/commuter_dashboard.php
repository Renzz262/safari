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

// Simulated distance calculation function
function calculateDistance($location1, $location2) {
    $distanceTable = [
        'Downtown Nairobi' => ['Downtown Nairobi' => 0, 'Westlands' => 5, 'Kilimani' => 4, 'Ngong Road' => 6, 'Lavington' => 7, 'Karen' => 15, 'Runda' => 12, 'Parklands' => 6],
        'Westlands' => ['Downtown Nairobi' => 5, 'Westlands' => 0, 'Kilimani' => 3, 'Ngong Road' => 5, 'Lavington' => 4, 'Karen' => 12, 'Runda' => 8, 'Parklands' => 2],
        'Kilimani' => ['Downtown Nairobi' => 4, 'Westlands' => 3, 'Kilimani' => 0, 'Ngong Road' => 2, 'Lavington' => 3, 'Karen' => 10, 'Runda' => 10, 'Parklands' => 4],
        'Ngong Road' => ['Downtown Nairobi' => 6, 'Westlands' => 5, 'Kilimani' => 2, 'Ngong Road' => 0, 'Lavington' => 3, 'Karen' => 8, 'Runda' => 12, 'Parklands' => 6],
        'Lavington' => ['Downtown Nairobi' => 7, 'Westlands' => 4, 'Kilimani' => 3, 'Ngong Road' => 3, 'Lavington' => 0, 'Karen' => 7, 'Runda' => 10, 'Parklands' => 5],
        'Karen' => ['Downtown Nairobi' => 15, 'Westlands' => 12, 'Kilimani' => 10, 'Ngong Road' => 8, 'Lavington' => 7, 'Karen' => 0, 'Runda' => 18, 'Parklands' => 14],
        'Runda' => ['Downtown Nairobi' => 12, 'Westlands' => 8, 'Kilimani' => 10, 'Ngong Road' => 12, 'Lavington' => 10, 'Karen' => 18, 'Runda' => 0, 'Parklands' => 6],
        'Parklands' => ['Downtown Nairobi' => 6, 'Westlands' => 2, 'Kilimani' => 4, 'Ngong Road' => 6, 'Lavington' => 5, 'Karen' => 14, 'Runda' => 6, 'Parklands' => 0],
    ];

    if (!isset($distanceTable[$location1]) || !isset($distanceTable[$location1][$location2])) {
        return 9999; // Return a large distance if unknown
    }

    return $distanceTable[$location1][$location2];
}

// Handle AJAX request to fetch commuter's rides
if (isset($_GET['fetch']) && $_GET['fetch'] === "rides") {
    $sql = "SELECT 
                b.id AS ride_id,
                u.id AS commuter_id,
                CONCAT(u.firstName, ' ', u.lastName) AS commuter_name,
                CONCAT(d.firstName, ' ', d.lastName) AS driver_name,
                b.pickup,
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
        echo '<div class="rides-container">';
        echo '<h2 style="color: white;">Previous Rides</h2>';

        echo '<div class="rides-form">';
        echo '<table border="1">
            <thead>
                <tr>
                    <th>Commuter ID</th>
                    <th>Name</th>
                    <th>Ride ID</th>
                    <th>Driver Name</th>
                    <th>Pickup Location</th>
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
        echo '</div>'; // Close rides-form
        echo '</div>'; // Close rides-container
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
        echo '<div class="rides-container">';
        echo '<h2 style="color: white;">Upcoming Rides</h2>';

        echo '<div class="rides-form">';
        echo '<table border="1">
            <thead>
                <tr>
                    <th>Commuter ID</th>
                    <th>Name</th>
                    <th>Ride ID</th>
                    <th>Driver Name</th>
                    <th>Pickup Location</th>
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
                    <td>' . htmlspecialchars($row['ride_date']) . '</td>
                    <td>' . htmlspecialchars($row['status']) . '</td>
                </tr>';
        }

        echo '</tbody></table>';
        echo '</div>'; // Close rides-form
        echo '</div>'; // Close rides-container
    } else {
        echo '<div class="rides-container">';
        echo '<p>No upcoming rides available. Use the "Book Ride" link in the navigation to schedule a ride.</p>';
        echo '</div>';
    }
    exit();
}

// Handle AJAX request to fetch today's available rides
if (isset($_GET['fetch']) && $_GET['fetch'] === "todays_rides") {
    $pickup_location = isset($_GET['pickup_location']) ? mysqli_real_escape_string($conn, $_GET['pickup_location']) : '';
    $time_of_day = isset($_GET['time_of_day']) ? mysqli_real_escape_string($conn, $_GET['time_of_day']) : '';

    if (empty($pickup_location) || empty($time_of_day)) {
        echo '<div class="rides-container">';
        echo '<p>Please provide a pickup location and time of day.</p>';
        echo '</div>';
        exit();
    }

    $sql = "SELECT 
                ds.schedule_id,
                ds.driver_id,
                CONCAT(u.firstName, ' ', u.lastName) AS driver_name,
                u.location AS driver_location,
                v.vehicle_id,
                v.license_plate,
                ds.date,
                ds.time_of_day,
                ds.status
            FROM 
                driver_schedules ds
            JOIN 
                users u ON ds.driver_id = u.id
            JOIN 
                vehicles v ON ds.vehicle_id = v.vehicle_id
            WHERE 
                ds.date = '2025-04-03'
                AND ds.status = 'Available'
                AND ds.time_of_day = ?";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $time_of_day);
    $stmt->execute();
    $result = $stmt->get_result();

    $schedules = [];
    while ($row = $result->fetch_assoc()) {
        $distance = calculateDistance($row['driver_location'], $pickup_location);
        $row['distance'] = $distance;
        $schedules[] = $row;
    }

    usort($schedules, function($a, $b) {
        return $a['distance'] <=> $b['distance'];
    });

    if (count($schedules) > 0) {
        echo '<div class="rides-container">';
        echo '<h2 style="color: white;">Today\'s Available Rides (Sorted by Distance)</h2>';

        echo '<div class="rides-form">';
        echo '<table border="1">
            <thead>
                <tr>
                    <th>Schedule ID</th>
                    <th>Driver Name</th>
                    <th>License Plate</th>
                    <th>Distance (km)</th>
                    <th>Date</th>
                    <th>Time of Day</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>';

        foreach ($schedules as $row) {
            echo '<tr>
                    <td>' . htmlspecialchars($row['schedule_id']) . '</td>
                    <td>' . htmlspecialchars($row['driver_name']) . '</td>
                    <td>' . htmlspecialchars($row['license_plate']) . '</td>
                    <td>' . htmlspecialchars($row['distance']) . '</td>
                    <td>' . htmlspecialchars($row['date']) . '</td>
                    <td>' . htmlspecialchars($row['time_of_day']) . '</td>
                    <td>' . htmlspecialchars($row['status']) . '</td>
                </tr>';
        }

        echo '</tbody></table>';
        echo '</div>'; // Close rides-form
        echo '</div>'; // Close rides-container
    } else {
        echo '<div class="rides-container">';
        echo '<p>No available rides for your selected time of day. Use the "Book Ride" link in the navigation to schedule a ride.</p>';
        echo '</div>';
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
    <style>
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        th {
            background-color: #f2f2f2;
        }
        tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        tr:hover {
            background-color: #f1f1f1;
        }
        .pickup-form {
            margin: 20px 0;
            padding: 15px;
            background-color: #f9f9f9;
            border-radius: 5px;
        }
        .pickup-form label {
            margin-right: 10px;
            font-weight: bold;
        }
        .pickup-form input, .pickup-form select {
            margin-right: 10px;
            padding: 5px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
    </style>
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
                <li class="navBar"><a href="pending_drivers.php">Join Our Network</a></li>
                <li class="navBar"><a href="commuter_profile.php">Profile</a></li>
                <li class="navBar"><a href="logout.php">Logout</a></li>
            </ul>
        </nav>
    </header>

    <div id="bodyDiv">
        <h1 id="bodyHeader">Commuter Dashboard</h1>
        <h2 id="welcomeMessage">Welcome, <?= htmlspecialchars($commuter_name); ?></h2>

        <!-- Form for pickup location and time of day -->
        <div class="pickup-form">
            <h3>Find a Ride</h3>
            <form id="rideFilterForm">
                <label for="pickup_location">Your Pickup Location:</label>
                <input type="text" id="pickup_location" name="pickup_location" placeholder="e.g., Downtown Nairobi" required>
                <label for="time_of_day">Preferred Time of Day:</label>
                <select id="time_of_day" name="time_of_day" required>
                    <option value="Morning">Morning</option>
                    <option value="Evening">Evening</option>
                </select>
                <button type="button" id="viewTodaysRides" class="action-btn">View Today's Rides</button>
            </form>
        </div>

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

            // Fetch today's available rides with pickup location and time of day
            $("#viewTodaysRides").click(function () {
                let pickupLocation = $("#pickup_location").val();
                let timeOfDay = $("#time_of_day").val();
                
                if (!pickupLocation || !timeOfDay) {
                    alert("Please enter your pickup location and select a time of day.");
                    return;
                }

                let url = "commuter_dashboard.php?fetch=todays_rides&pickup_location=" + encodeURIComponent(pickupLocation) + "&time_of_day=" + encodeURIComponent(timeOfDay);
                $.ajax({
                    url: url,
                    type: "GET",
                    success: function (data) {
                        $("#tableContainer").html(data);
                    },
                    error: function (xhr, status, error) {
                        alert("Error fetching today's rides: " + error);
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

    <footer>
        <p>© 2025 SafariConnect LTD |</p>
    </footer>
</body>
</html>
