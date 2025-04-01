<?php
// view_resume.php
include "connect.php";

if (isset($_GET['id'])) {
    $id = $_GET['id'];

    // Fetch resume from the driver_applications table
    $sql = "SELECT resume FROM driver_applications WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->bind_result($resume);
    $stmt->fetch();

    if ($resume) {
        echo "<h3>Resume</h3>";
        echo "<p>" . nl2br(htmlspecialchars($resume)) . "</p>";
    } else {
        echo "<p>Resume not found.</p>";
    }
}
?>
