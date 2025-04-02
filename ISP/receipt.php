<?php
ob_start(); // Prevents output before PDF generation
session_start();
require('fpdf/fpdf.php');
include 'connect.php';

// ✅ Ensure ride_id is provided
if (!isset($_GET['ride_id']) || empty($_GET['ride_id'])) {
    die("<script>alert('No ride selected.'); window.location.href='commuter_dashboard.php';</script>");
}

$ride_id = intval($_GET['ride_id']);

// ✅ Fetch ride details with commuter and driver name
$sql = "SELECT 
            b.id AS ride_id,
            CONCAT(c.firstName, ' ', c.lastName) AS commuter_name,
            b.pickup,
            b.dropoff,
            CONCAT(d.firstName, ' ', d.lastName) AS driver_name
        FROM bookings b
        JOIN users c ON b.user_id = c.id  -- Commuter details
        JOIN users d ON b.driver_id = d.id  -- Driver details
        WHERE b.id = ?";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    die("<script>alert('SQL Prepare Failed: " . $conn->error . "'); window.location.href='commuter_dashboard.php';</script>");
}

$stmt->bind_param("i", $ride_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $ride = $result->fetch_assoc();
} else {
    die("<script>alert('Error: No ride found in database for ID = " . $ride_id . "'); window.location.href='commuter_dashboard.php';</script>");
}

ob_clean(); // Clears any output before generating the PDF

// ✅ Generate PDF using FPDF
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
$pdf->Cell(40, 10, 'Driver Name: ' . $ride['driver_name']); // ✅ Added driver name
$pdf->Ln(10);

// Output PDF
$pdf->Output('D', 'Ride_Receipt_' . $ride_id . '.pdf'); // Download PDF
exit(); // Ensure no further execution
?>
