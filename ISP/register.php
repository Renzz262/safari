<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
echo "Debug: register.php is loading!";
session_start();
include 'connect.php';
if (!$conn) {
    die("Database connection failed: " . mysqli_connect_error());
} else {
    echo "Debug: Database connected successfully!";
}

if (isset($_POST['signUp'])) {
    $firstName = trim($_POST['fName']);
    $lastName = trim($_POST['lName']);
    $email = trim($_POST['email']);
    $accountType = trim($_POST['accountType']);

    if (!empty($_POST['password'])) {
        $password = $_POST['password'];
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    } else {
        die("Error: Password field is empty.");
    }

    // ✅ Check if email already exists
    $checkEmail = "SELECT * FROM users WHERE email = ?";
    $stmt = $conn->prepare($checkEmail);
    
    if (!$stmt) {
        die("SQL Error (Email Check): " . $conn->error);
    }

    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        echo "<script>alert('Email Address Already Exists!'); window.location.href='register.html';</script>";
    } else {
        $insertQuery = "INSERT INTO users (firstName, lastName, email, password, accountType) VALUES (?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($insertQuery);

        if (!$stmt) {
            die("SQL Error (Insert): " . $conn->error);
        }

        $stmt->bind_param("sssss", $firstName, $lastName, $email, $hashedPassword, $accountType);

        if ($stmt->execute()) {
            echo "<script>alert('Account created successfully! Please log in.'); window.location.href='login.html';</script>";
            exit();
        } else {
            echo "Database Error: " . $stmt->error;
        }
    }
}


// === HANDLE LOGIN ===
if (isset($_POST['signIn'])) {
    $email = trim($_POST['email']);
    $password = trim($_POST['password']); 

    $sql = "SELECT id, password FROM users WHERE email = ?";
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
            echo "<script>alert('Login successful!'); window.location.href='home.html';</script>";
            exit();
        } else {
            echo "<script>alert('Incorrect Email or Password!');</script>";
        }
    } else {
        echo "<script>alert('User Not Found!');</script>";
    }
}

// === HANDLE RIDE BOOKING ===

// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "Debug: register.php is loading!<br>";

// ✅ Ensure session is started only once
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ✅ Check if database connection is successful
include 'connect.php';
if (!$conn) {
    die("Database connection failed: " . mysqli_connect_error());
} else {
    echo "Debug: Database connected successfully!<br>";
}

// ✅ Check if the form is submitted properly
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    echo "Debug: Form submitted via POST!<br>";

    // ✅ Ensure the submit button was clicked
    if (!isset($_POST['bookRide'])) {
        die("Error: Submit button 'bookRide' is missing.");
    }

    // ✅ Ensure user is logged in
    if (!isset($_SESSION['user_id'])) {
        die("Error: Access denied. Please log in.");
    }

    // ✅ Validate form data
    if (!isset($_POST['name'], $_POST['pickup'], $_POST['drop'])) {
        die("Error: Missing required form fields.");
    }

    $name = trim($_POST['name']);
    $pickup = trim($_POST['pickup']);
    $drop = trim($_POST['drop']);

    if (empty($name) || empty($pickup) || empty($drop)) {
        die("Error: All fields are required.");
    }

    echo "<p>Debug: Name = " . htmlspecialchars($name) . "</p>";
    echo "<p>Debug: Pickup = " . htmlspecialchars($pickup) . "</p>";
    echo "<p>Debug: Dropoff = " . htmlspecialchars($drop) . "</p>";

    // ✅ Insert into database
    $sql = "INSERT INTO bookings (name, pickup, dropoff) VALUES (?, ?, ?)";
    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        die("SQL Error (Prepare Failed): " . $conn->error);
    }

    $stmt->bind_param("sss", $name, $pickup, $drop);

    if ($stmt->execute()) {
        $ride_id = $stmt->insert_id;
        echo "<p>Debug: Ride ID Generated = " . $ride_id . "</p>";

        if (!$ride_id) {
            die("Error: Ride ID not generated. Check database structure.");
        }

        // ✅ Redirect to receipt.php with ride_id
        header("Location: receipt.php?ride_id=" . urlencode($ride_id));
        exit();
    } else {
        die("Database Insertion Failed: " . $stmt->error);
    }
} else {
    die("Error: Invalid request. Debug: Request method was " . $_SERVER["REQUEST_METHOD"]);
}
?>
