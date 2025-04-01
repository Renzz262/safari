<?php
session_start();
include "connect.php";

if (!isset($_SESSION['accountType']) || $_SESSION['accountType'] !== 'administrator') {
    echo "<script>alert('Access denied. Only administrators can view this page.'); window.location.href='login.html';</script>";
    exit();
}

// Fetch number of logged-in users
$loggedInUsersQuery = "SELECT COUNT(*) AS logged_in_count FROM users WHERE is_logged_in = 1";
$result = $conn->query($loggedInUsersQuery);
$loggedInUsers = $result->fetch_assoc()['logged_in_count'];

// Handle AJAX requests
if (isset($_GET['fetch'])) {
    $fetchType = $_GET['fetch'];

    if ($fetchType === "drivers") {
        $sql = "SELECT id, firstName, lastName, email FROM users WHERE accountType = 'driver'";
    } elseif ($fetchType === "passengers") {
        $sql = "SELECT id, firstName, lastName, email FROM users WHERE accountType = 'commuter'";
    } elseif ($fetchType === "users") {
        $sql = "SELECT id, firstName, lastName, email, accountType, is_logged_in FROM users";
    } elseif ($fetchType === "rides") {
        $sql = "SELECT 
                    b.id AS ride_id,
                    COALESCE(u.firstName, 'N/A') AS firstName,
                    COALESCE(u.lastName, 'N/A') AS lastName,
                    COALESCE(d.firstName, 'N/A') AS driverFirstName,
                    COALESCE(d.lastName, 'N/A') AS driverLastName,
                    b.pickup,
                    b.dropoff,
                    b.status
                FROM bookings b
                JOIN users u ON b.user_id = u.id
                LEFT JOIN users d ON b.driver_id = d.id";
    } elseif ($fetchType === "driver_dashboard") {
        $sql = "SELECT 
                    b.id AS ride_id,
                    COALESCE(u.firstName, 'N/A') AS passengerName,
                    b.pickup,
                    b.dropoff,
                    b.status
                FROM bookings b
                JOIN users u ON b.user_id = u.id
                WHERE b.driver_id IS NOT NULL";
    } else {
        exit();
    }

    $result = $conn->query($sql);
    if (!$result) {
        die("Query Failed: " . $conn->error);
    }

    if ($fetchType === "users") {
        echo "<h2>User Management</h2>";
        echo "<table border='1'>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>First Name</th>
                        <th>Last Name</th>
                        <th>Email</th>
                        <th>Account Type</th>
                        <th>Logged In</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>";

        while ($row = $result->fetch_assoc()) {
            echo "<tr>
                    <td>" . htmlspecialchars($row['id']) . "</td>
                    <td>" . htmlspecialchars($row['firstName']) . "</td>
                    <td>" . htmlspecialchars($row['lastName']) . "</td>
                    <td>" . htmlspecialchars($row['email']) . "</td>
                    <td>" . htmlspecialchars($row['accountType']) . "</td>
                    <td>" . ($row['is_logged_in'] ? 'Yes' : 'No') . "</td>
                    <td><button class='delete-user' data-id='" . $row['id'] . "'>Delete</button></td>
                  </tr>";
        }

        echo "</tbody></table>";
    } else {
        echo "<div class='booking-container'>";
        while ($row = $result->fetch_assoc()) {
            echo "<div class='booking-card'>";
            if ($fetchType === "rides") {
                echo "<strong>Passenger:</strong> " . htmlspecialchars($row['firstName'] . " " . $row['lastName']) . " | ";
                echo "<strong>Driver:</strong> " . htmlspecialchars($row['driverFirstName'] . " " . $row['driverLastName']) . " | ";
            } elseif ($fetchType === "driver_dashboard") {
                echo "<strong>Passenger:</strong> " . htmlspecialchars($row['passengerName']) . " | ";
            }
            echo "<strong>Pickup:</strong> " . htmlspecialchars($row['pickup']) . " | ";
            echo "<strong>Dropoff:</strong> " . htmlspecialchars($row['dropoff']) . " | ";
            echo "<strong>Status:</strong> " . htmlspecialchars($row['status']);
            echo "</div>";
        }
        echo "</div>";
    }
    exit();
}
$fetchType = isset($_GET['fetchType']) ? $_GET['fetchType'] : ''; // Default to an empty string if not se
if ($fetchType === "driver_applications") {
    // Query to fetch driver applications with status 'Pending'
    $sql = "SELECT * FROM driver_applications WHERE status = 'Pending'";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        echo "<table border='1'>
                <thead>
                    <tr>
                        <th>Full Name</th>
                        <th>Age</th>
                        <th>Email</th>
                        <th>Resume</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>";
        
        while ($row = $result->fetch_assoc()) {
            echo "<tr>
                    <td>" . htmlspecialchars($row['full_name']) . "</td>
                    <td>" . htmlspecialchars($row['age']) . "</td>
                    <td>" . htmlspecialchars($row['email']) . "</td>
                    <td><a href='view_resume.php?id=" . $row['id'] . "'>View Resume</a></td>
                    <td>" . htmlspecialchars($row['status']) . "</td>
                    <td>
                        <a href='approve_application.php?id=" . $row['id'] . "'>Approve</a> | 
                        <a href='reject_application.php?id=" . $row['id'] . "'>Reject</a>
                    </td>
                  </tr>";
        }

        echo "</tbody></table>";
    } else {
        echo "<p>No pending driver applications.</p>";
    }
}
// Fetch Pending Driver Applications
if (isset($_GET['view_driver_applications'])) {
    $sql = "SELECT * FROM driver_applications WHERE status = 'Pending'";
    $result = $conn->query($sql);

    echo "<h2>Driver Applications</h2>";
    echo "<table border='1'>
            <thead>
                <tr>
                    <th>Full Name</th>
                    <th>Age</th>
                    <th>Email</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>";

    while ($row = $result->fetch_assoc()) {
        echo "<tr>
                <td>" . htmlspecialchars($row['full_name']) . "</td>
                <td>" . htmlspecialchars($row['age']) . "</td>
                <td>" . htmlspecialchars($row['email']) . "</td>
                <td>" . htmlspecialchars($row['status']) . "</td>
                <td>
                    <a href='approve_decline.php?action=approve&id=" . $row['id'] . "'>Approve</a> |
                    <a href='approve_decline.php?action=decline&id=" . $row['id'] . "'>Decline</a>
                </td>
              </tr>";
    }

    echo "</tbody></table>";
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SafariConnect • Admin Dashboard</title>
    <link rel="stylesheet" type="text/css" href="./style.css">
        <link rel="icon" href="https://media.istockphoto.com/id/2070968418/vector/lettering-va-brand-symbol-design.jpg?s=612x612&w=0&k=20&c=5-HWZ5Bf2DDVdcUT1fK51F6TxixVZhAYaBZLJOSug8c=">

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
    .booking-container {
        display: flex;
        flex-direction: column;
        gap: 10px;
    }
    .booking-card {
        border: 2px solid #0080ff;
        border-radius: 8px;
        padding: 15px;
        background-color: #f8f9fa;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }
    /* Added border to tables */
    table {
        width: 100%;
        border-collapse: collapse;
        border: 2px solid #0080ff;
    }
    th, td {
        border: 1px solid #0080ff;
        padding: 8px;
        text-align: left;
    }
    th {
        background-color: #f0f0f0;
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
                <li class="navBar"><a href="admin_dashboard.php" id="homeLink">Home</a></li>
                <li class="navBar"><a href="#" id="manageUsers">Manage Users</a></li>      
                 <li class="navBar"><a href="driver_applications.php">View Driver Applications</a></li>


                <li class="navBar"><a href="./login.html">Logout</a></li>

            </ul>
        </nav>
    </header>

    <div id="bodyDiv">
        <h1 id="bodyHeader">Admin Dashboard</h1>
        <p>Number of Logged-in Users: <strong id="loggedInCount"><?php echo $loggedInUsers; ?></strong></p>
        <button id="viewDriverTable">View Driver Dashboard</button>
        <button id="viewPassengerTable">View Passenger Dashboard</button>
        
        <div class="tableDiv" id="tableContainer">
            <!-- Table data will be dynamically inserted here -->
        </div>
    </div>

    <script>
        function updateLoggedInCount() {
            $.ajax({
                url: "fetch_logged_in_count.php",
                type: "GET",
                success: function (data) {
                    $("#loggedInCount").text(data);
                }
            });
        }

        $(document).ready(function () {
            $("#homeLink").addClass("activePage");

            $("#viewDriverTable").click(function () {
                $(".navBar a").removeClass("activePage");
                $(this).addClass("activePage");

                $.ajax({
                    url: "admin_dashboard.php?fetch=driver_dashboard",
                    type: "GET",
                    success: function (data) {
                        $("#tableContainer").html("<h2>Driver Dashboard</h2>" + data);
                    }
                });
            });

            $("#viewPassengerTable").click(function () {
                $(".navBar a").removeClass("activePage");
                $(this).addClass("activePage");

                $.ajax({
                    url: "admin_dashboard.php?fetch=rides",
                    type: "GET",
                    success: function (rideData) {
                        $("#tableContainer").html("<h2>Passenger Rides</h2>" + rideData);
                    }
                });
            });

            $("#manageUsers").click(function () {
                $(".navBar a").removeClass("activePage");
                $(this).addClass("activePage");

                $.ajax({
                    url: "admin_dashboard.php?fetch=users",
                    type: "GET",
                    success: function (data) {
                        $("#bodyDiv").html(data);
                        addDeleteEvent();
                    }
                });
            });

            function addDeleteEvent() {
                $(".delete-user").click(function () {
                    let userId = $(this).data("id");

                    if (confirm("Are you sure you want to delete this user?")) {
                        $.ajax({
                            url: "delete_user.php",
                            type: "POST",
                            data: { id: userId },
                            success: function (response) {
                                alert(response);
                                $("#manageUsers").trigger("click");
                            }
                        });
                    }
                });
            }

            setInterval(updateLoggedInCount, 10000);
        });

    </script>

</body>
</html>
