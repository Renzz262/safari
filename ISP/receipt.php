<?php

// Enable error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require('fpdf/fpdf.php'); // Include FPDF

// ✅ Check if ride_id is provided in the URL
if (!isset($_GET['ride_id']) || empty($_GET['ride_id'])) {
    die("No ride selected.");
}

$ride_id = intval($_GET['ride_id']);

// Database connection
include 'connect.php';

// ✅ Fetch ride details from database
$sql = "SELECT * FROM bookings WHERE id = ?";
$stmt = $conn->prepare($sql);
if (!$stmt) {
    die("SQL Error: " . $conn->error);
}

$stmt->bind_param("i", $ride_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $ride = $result->fetch_assoc();
} else {
    die("Error: No ride found in database for ID = " . $ride_id);
}

// ✅ Generate PDF using FPDF
$pdf = new FPDF();
$pdf->AddPage();
$pdf->SetFont('Arial', 'B', 16);

// Title
$pdf->Cell(190, 10, 'Ride Receipt', 1, 1, 'C');

// Receipt Details
$pdf->SetFont('Arial', '', 12);
$pdf->Ln(10);
$pdf->Cell(40, 10, 'Name: ' . $ride['name']);
$pdf->Ln(10);
$pdf->Cell(40, 10, 'Pickup Location: ' . $ride['pickup']);
$pdf->Ln(10);
$pdf->Cell(40, 10, 'Drop-off Location: ' . $ride['dropoff']);
$pdf->Ln(10);

// Output PDF
$pdf->Output('D', 'Ride_Receipt_' . $ride_id . '.pdf'); // Download PDF
?>
