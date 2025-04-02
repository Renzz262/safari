<?php
session_start();
include "connect.php"; // Ensure database connection

// Ensure user is logged in and has admin privileges
if (!isset($_SESSION['accountType']) || $_SESSION['accountType'] !== 'administrator') {
    echo "<script>alert('Access denied. Only administrators can view this page.'); window.location.href='login.html';</script>";
    exit();
}

// Query to fetch pending driver applications
$sql = "SELECT * FROM driver_applications WHERE status = 'Pending'";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Driver Applications</title>
    <link rel="stylesheet" type="text/css" href="style.css">
    <link rel="icon" href="https://media.istockphoto.com/id/2070968418/vector/lettering-va-brand-symbol-design.jpg?s=612x612&w=0&k=20&c=5-HWZ5Bf2DDVdcUT1fK51F6TxixVZhAYaBZLJOSug8c=">

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
                 <li class="navBar"><a href="driver_applications.php">View Driver Applications</a></li>                        <li class="navBar"><a href="admin_profile.php"> Profile</a></li>


                <li class="navBar"><a href="./login.html">Logout</a></li>

            </ul>
        </nav>
    </header>

    <h2>Driver Applications</h2>

    <?php
    if ($result->num_rows > 0) {
        echo "<table border='1'>
                <thead>
                    <tr>
                        <th>Full Name</th>
                        <th>Age</th>
                        <th>Email</th>
                        <th>Resume</th>
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
                    <td>
                        <a href='approve_decline.php?action=approve&id=" . $row['id'] . "' class='approve-btn'>Approve</a> | 
                        <a href='approve_decline.php?action=decline&id=" . $row['id'] . "' class='decline-btn'>Decline</a>
                    </td>
                  </tr>";
        }

        echo "</tbody></table>";
    } else {
        echo "<p>No pending driver applications.</p>";
    }
    ?>
</body>
</html>
