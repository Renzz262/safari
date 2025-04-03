<?php
session_start();
if (isset($_POST['pickup_location'])) {
    $_SESSION['pickup_location'] = $_POST['pickup_location'];
}
echo json_encode(['success' => true]);
exit();
?>
