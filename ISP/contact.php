<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['accountType'])) {
    echo "<script>alert('Access denied. Please log in.'); window.location.href='login.html';</script>";
    exit();
}

// Fetch commuter's name and email from the session
$commuter_name = isset($_SESSION['firstName']) ? $_SESSION['firstName'] . " " . $_SESSION['lastName'] : "";
$commuter_email = isset($_SESSION['email']) ? $_SESSION['email'] : "";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Us - SafariConnect</title>
    <link rel="stylesheet" type="text/css" href="./style.css">
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
                <li class="navBar"><a href="commuter_profile.php"> Profile</a></li>
                <li class="navBar"><a href="logout.php">Logout</a></li>
            </ul>
        </nav>
    </header>

    <div id="bodyDiv">
        <h1>Contact Us</h1>
        <form id="contactForm">
            <label for="firstName">First Name:</label>
            <input type="text" name="firstname" id="firstName" required>

            <label for="lastName">Last Name:</label>
            <input type="text" name="lastname" id="lastName" required>

            <label for="email">Your Email:</label>
            <input type="email" name="email" id="email" required>

            <label for="message">Message:</label>
            <textarea name="message" id="message" rows="5" required></textarea>

            <button type="submit">Send Message</button>
        </form>
    </div>

    <script>
        document.getElementById("contactForm").addEventListener("submit", function(event) {
            event.preventDefault(); // Prevent actual form submission
            
            // Show success message
            alert("Message sent successfully!");

            // Reset the form fields
            document.getElementById("contactForm").reset();
        });
    </script>
</body>
</html>

