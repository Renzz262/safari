<?php
include "connect.php";
session_start();

if (!isset($_SESSION['accountType']) || $_SESSION['accountType'] !== 'administrator') {
    echo "Access denied.";
    exit();
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $application_id = $_POST['application_id'];
    $action = $_POST['action'];

    if ($action === "accept") {
        $sql = "UPDATE driver_applications SET status = 'Accepted' WHERE id = ?";
    } elseif ($action === "decline") {
        $sql = "UPDATE driver_applications SET status = 'Declined' WHERE id = ?";
    } else {
        echo "Invalid action.";
        exit();
    }

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $application_id);

    if ($stmt->execute()) {
        echo "Application updated successfully.";
    } else {
        echo "Error: " . $conn->error;
    }
    $stmt->close();
    $conn->close();
}
?>
