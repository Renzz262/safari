<?php
ob_start(); // Prevents output before PDF generation
session_start();
require('fpdf/fpdf.php');
include 'connect.php';

// Ensure user is logged in as a commuter
if (!isset($_SESSION['accountType']) || $_SESSION['accountType'] !== 'commuter') {
    echo "<script>alert('Access denied. Please log in as a commuter.'); window.location.href='login.html';</script>";
    exit();
}

// Debug: Check connection
if ($conn->connect_error) {
    die("<script>alert('Database connection failed: " . $conn->connect_error . "'); window.location.href='commuter_dashboard.php';</script>");
}

if (isset($_GET['schedule_id'])) {
    $schedule_id = intval($_GET['schedule_id']);
    $commuter_id = $_SESSION['user_id'];
    $date = '2025-04-03'; // Hardcoded for now; could be dynamic
    $pickup_location = isset($_SESSION['pickup_location']) ? $_SESSION['pickup_location'] : 'Unknown'; // From session

    // Start transaction to ensure atomicity
    $conn->begin_transaction();

    try {
        // Insert booking into bookings table
        $sql = "INSERT INTO bookings (user_id, schedule_id, status, ride_date, pickup) 
                VALUES (?, ?, 'Scheduled', ?, ?)";
        $stmt = $conn->prepare($sql);
        if ($stmt === false) {
            die("<script>alert('Prepare failed (INSERT): " . $conn->error . "'); window.location.href='commuter_dashboard.php';</script>");
        }
        $stmt->bind_param("iiss", $commuter_id, $schedule_id, $date, $pickup_location);
        if (!$stmt->execute()) {
            die("<script>alert('Execute failed (INSERT): " . $stmt->error . "'); window.location.href='commuter_dashboard.php';</script>");
        }

        // Get the newly created booking ID
        $booking_id = $conn->insert_id;

        // Update driver_schedules status to Booked
        $sql = "UPDATE driver_schedules SET status = 'Booked' WHERE schedule_id = ?";
        $stmt = $conn->prepare($sql);
        if ($stmt === false) {
            die("<script>alert('Prepare failed (UPDATE): " . $conn->error . "'); window.location.href='commuter_dashboard.php';</script>");
        }
        $stmt->bind_param("i", $schedule_id);
        if (!$stmt->execute()) {
            die("<script>alert('Execute failed (UPDATE): " . $stmt->error . "'); window.location.href='commuter_dashboard.php';</script>");
        }

        // Commit transaction
        $conn->commit();

        // Fetch ride details with commuter and driver name (mimicking receipt.php)
        $sql = "SELECT 
                    b.id AS ride_id,
                    CONCAT(c.firstName, ' ', c.lastName) AS commuter_name,
                    b.pickup,
                    b.dropoff,
                    CONCAT(d.firstName, ' ', d.lastName) AS driver_name
                FROM bookings b
                JOIN users c ON b.user_id = c.id  -- Commuter details
                JOIN driver_schedules ds ON b.schedule_id = ds.schedule_id
                JOIN users d ON ds.driver_id = d.id  -- Driver details
                WHERE b.id = ?";
        $stmt = $conn->prepare($sql);
        if ($stmt === false) {
            die("<script>alert('Prepare failed (SELECT): " . $conn->error . "'); window.location.href='commuter_dashboard.php';</script>");
        }
        $stmt->bind_param("i", $booking_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $ride = $result->fetch_assoc();
        } else {
            die("<script>alert('Error: No ride found in database for ID = " . $booking_id . "'); window.location.href='commuter_dashboard.php';</script>");
        }

        ob_clean(); // Clears any output before generating the PDF

        // Generate PDF using FPDF (mimicking receipt.php)
        $pdf = new FPDF();
        $pdf->AddPage();
        $pdf->SetFont('Arial', 'B', 16);

        // Title
        $pdf->Cell(190, 10, 'Ride Receipt', 1, 1, 'C');

        // Receipt Details
        $pdf->SetFont('Arial', '', 12);
        $pdf->Ln(10);
        $pdf->Cell(40, 10, 'Name: ' . $ride['commuter_name']);
        $pdf->Ln(10);
        $pdf->Cell(40, 10, 'Pickup Location: ' . $ride['pickup']);
        $pdf->Ln(10);
        $pdf->Cell(40, 10, 'Drop-off Location: ' . $ride['dropoff']);
        $pdf->Ln(10);
        $pdf->Cell(40, 10, 'Driver Name: ' . $ride['driver_name']); // Added driver name
        $pdf->Ln(10);

        // Output PDF
        $pdf->Output('D', 'Ride_Receipt_' . $booking_id . '.pdf'); // Download PDF
        exit(); // Ensure no further execution

    } catch (Exception $e) {
        $conn->rollback();
        echo "<script>alert('Booking failed: " . $e->getMessage() . "'); window.location.href='commuter_dashboard.php';</script>";
        exit();
    }
} else {
    echo "<script>alert('Invalid schedule ID.'); window.location.href='commuter_dashboard.php';</script>";
}
?>
