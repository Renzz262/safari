<?php
include "connect.php";  // Ensure database connection

if (isset($_GET['action']) && isset($_GET['id'])) {
    $action = $_GET['action'];
    $application_id = $_GET['id'];

    if ($action === 'approve') {
        $status = 'Approved';
    } elseif ($action === 'decline') {
        $status = 'Declined';
    } else {
        // Invalid action
        exit();
    }

    // Update the status of the application in the database
    $sql = "UPDATE driver_applications SET status = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("si", $status, $application_id);

    if ($stmt->execute()) {
        echo "<script>alert('Application $status successfully.'); window.location.href='admin_dashboard.php?view_driver_applications=true';</script>";
    } else {
        echo "<script>alert('Error updating application status.'); window.location.href='admin_dashboard.php?view_driver_applications=true';</script>";
    }
}
