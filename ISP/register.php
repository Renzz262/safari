<?php
session_start();
include 'connect.php'; // ✅ Ensure database connection is included

if (isset($_POST['signUp'])) {
    $firstName = trim($_POST['fName']);
    $lastName = trim($_POST['lName']);
    $email = trim($_POST['email']);

    if (!empty($_POST['password'])) {
        $password = $_POST['password'];
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    } else {
        die("Error: Password field is empty.");
    }

    $checkEmail = "SELECT * FROM users WHERE email = ?";
    $stmt = $conn->prepare($checkEmail);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        echo "<script>alert('Email Address Already Exists!'); window.location.href='register.html';</script>";
    } else {
        $insertQuery = "INSERT INTO users (firstName, lastName, email, password) VALUES (?, ?, ?, ?)";
        $stmt = $conn->prepare($insertQuery);
        $stmt->bind_param("ssss", $firstName, $lastName, $email, $hashedPassword);

        if ($stmt->execute()) {
            echo "<script>alert('Account created successfully! Please log in.'); window.location.href='login.html';</script>";
            exit();
        } else {
            echo "Error: " . $conn->error;
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

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['bookRide'])) {
    // Ensure user is logged in
    if (!isset($_SESSION['user_id'])) {
        die("Access denied. Please log in.");
    }

    $name = mysqli_real_escape_string($conn, trim($_POST['name']));
    $pickup = mysqli_real_escape_string($conn, trim($_POST['pickup']));
    $drop = mysqli_real_escape_string($conn, trim($_POST['drop']));

    // Check if values are empty
    if (empty($name) || empty($pickup) || empty($drop)) {
        die("Error: All fields are required.");
    }

    // ✅ Insert ride booking (FIXED QUERY)
    $sql = "INSERT INTO bookings (name, pickup, dropoff) VALUES (?, ?, ?)";
    
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        die("SQL Error: " . $conn->error);
    }

    $stmt->bind_param("sss", $name, $pickup, $drop);

    if ($stmt->execute()) {
       echo '<script>
    alert("Ride booked successfully!");
    window.location.href = "receipt.php?ride_id=' . $stmt->insert_id . '";
</script>';

    } else {
        echo "Database Error: " . $stmt->error;
    }
}
?>
    
