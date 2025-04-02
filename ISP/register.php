<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include "connect.php"; // Ensure database connection

// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (!$conn) {
    die("Database connection failed: " . mysqli_connect_error());
}

// === HANDLE USER REGISTRATION ===
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['signUp'])) {
    $fName = trim($_POST['fName']);
    $lName = trim($_POST['lName']);
    $email = trim($_POST['email']);
    $accountType = trim($_POST['accountType']); // Keep original casing
    $password = trim($_POST['password']);

    if (empty($fName) || empty($lName) || empty($accountType) || empty($email) || empty($password)) {
        die("<script>alert('Error: All fields are required.'); window.location.href='register.html';</script>");
    }

    $hashedPassword = password_hash($password, PASSWORD_BCRYPT);

    $checkEmail = "SELECT id FROM users WHERE email = ?";
    $stmt = $conn->prepare($checkEmail);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        echo "<script>alert('Error: Email already registered. Please log in.'); window.location.href='login.html';</script>";
        exit();
    }

    $sql = "INSERT INTO users (firstName, lastName, email, password, accountType) VALUES (?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssss", $fName, $lName, $email, $hashedPassword, $accountType);

    if ($stmt->execute()) {
        echo "<script>alert('User registered successfully! Redirecting to login...'); window.location.href='login.html';</script>";
        exit();
    } else {
        die("<script>alert('Error: User registration failed. " . $stmt->error . "'); window.location.href='register.html';</script>");
    }
}

// === HANDLE LOGIN ===
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['signIn'])) {
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);

    if (empty($email) || empty($password)) {
        die("<script>alert('Error: Email and password are required.'); window.location.href='login.html';</script>");
    }

    $sql = "SELECT id, password, accountType FROM users WHERE email = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $hashedPasswordFromDB = $row['password'];

        if (password_verify($password, $hashedPasswordFromDB)) {
            $_SESSION['user_id'] = $row['id'];
            $_SESSION['email'] = $email;
            $_SESSION['accountType'] = $row['accountType']; // Keep original casing

            if ($_SESSION['accountType'] === 'Administrator') {  
                header("Location: admin_dashboard.php");
            } elseif ($_SESSION['accountType'] === 'Driver') {
                header("Location: driver_dashboard.php");
            } elseif ($_SESSION['accountType'] === 'Commuter') {
                header("Location: commuter_dashboard.php");
            } else {
                echo "<script>alert('Error: Unknown account type.'); window.location.href='login.html';</script>";
                exit();
            }
        } else {
            echo "<script>alert('Error: Incorrect Email or Password!'); window.location.href='login.html';</script>";
            exit();
        }
    } else {
        echo "<script>alert('Error: No user found with this email. Please sign up first.'); window.location.href='register.html';</script>";
        exit();
    }
}

// === HANDLE RIDE BOOKING ===
include "connect.php"; // Ensure database connection

// ✅ Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    die(json_encode(["error" => "User not logged in."]));
}

// ✅ Capture form data
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['bookRide'])) {
    $user_id = $_SESSION['user_id']; // Logged-in user ID
    $name = trim($_POST['name']);
    $pickup = trim($_POST['pickup']);
    $dropoff = trim($_POST['dropoff']);

    // ✅ Debugging: Log received data
    error_log(print_r($_POST, true));

    // ✅ Validate required fields
    if (empty($name) || empty($pickup) || empty($dropoff)) {
        die(json_encode(["error" => "All fields are required."]));
    }

    // ✅ Find an available driver
    $driver_sql = "SELECT id FROM users WHERE accountType = 'driver' ORDER BY RAND() LIMIT 1";
    $driver_result = $conn->query($driver_sql);
    $driver_id = null;

    if ($driver_result->num_rows > 0) {
        $driver_row = $driver_result->fetch_assoc();
        $driver_id = $driver_row['id'];
    } else {
        die(json_encode(["error" => "No available drivers at the moment."]));
    }

    // ✅ Insert the booking with assigned driver
    $sql = "INSERT INTO bookings (user_id, name, pickup, dropoff, driver_id, status, ride_date) 
            VALUES (?, ?, ?, ?, ?, 'Scheduled', NOW())";
    
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        die(json_encode(["error" => "SQL Error: " . $conn->error]));
    }

    $stmt->bind_param("isssi", $user_id, $name, $pickup, $dropoff, $driver_id);

    if ($stmt->execute()) {
        $ride_id = $stmt->insert_id;
        echo json_encode(["success" => "Your Booking was successful!", "ride_id" => $ride_id]);

        // ✅ Redirect user to download the receipt
        echo "<script>window.location.href='receipt.php?ride_id=$ride_id';</script>";
        exit();
    } else {
        echo json_encode(["error" => "Booking failed: " . $stmt->error]);
    }

    // ✅ Close connections
    $stmt->close();
    $conn->close();
}


?>
