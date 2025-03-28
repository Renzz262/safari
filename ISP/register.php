<?php
// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
session_regenerate_id(true); // Prevent session fixation attacks

include 'connect.php';

// Ensure database connection
if (!$conn) {
    die("Database connection failed: " . mysqli_connect_error());
}

// === HANDLE REGISTRATION ===
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['signUp'])) {
    $fName = trim($_POST['fName']);
    $lName = trim($_POST['lName']);
    $email = trim($_POST['email']);
    $accountType = strtolower(trim($_POST['accountType'])); // Ensure lowercase storage
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
        die("Error: User registration failed - " . $stmt->error);
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
            $_SESSION['accountType'] = strtolower($row['accountType']);

            if ($_SESSION['accountType'] === 'driver') {
                header("Location: driver_dashboard.php");
            } else {
                header("Location: home.html");
            }
            exit();
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
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['bookRide'])) {
    if (!isset($_SESSION['user_id'])) {
        die("<script>alert('Access denied. Please log in.'); window.location.href='login.html';</script>");
    }

    $name = trim($_POST['name']);
    $pickup = trim($_POST['pickup']);
    $drop = trim($_POST['drop']);

    if (empty($name) || empty($pickup) || empty($drop)) {
        die("<script>alert('Error: All fields are required.'); window.location.href='home.html';</script>");
    }

    // Assign a random available driver
    $sql = "SELECT id FROM users WHERE LOWER(accountType) = 'driver' ORDER BY RAND() LIMIT 1";
    $driver_result = $conn->query($sql);
    $driver_id = ($driver_result->num_rows > 0) ? $driver_result->fetch_assoc()['id'] : NULL;

    if (!$driver_id) {
        die("<script>alert('No drivers available at the moment.'); window.location.href='home.html';</script>");
    }

    // Insert booking with assigned driver
    $insertBooking = "INSERT INTO bookings (name, pickup, dropoff, driver_id) VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($insertBooking);
    $stmt->bind_param("sssi", $name, $pickup, $drop, $driver_id);

    if ($stmt->execute()) {
        $ride_id = $stmt->insert_id;
        header("Location: receipt.php?ride_id=" . urlencode($ride_id));
        exit();
    } else {
        die("<script>alert('Database Insertion Failed: " . $stmt->error . "'); window.location.href='home.html';</script>");
    }
}
?>
