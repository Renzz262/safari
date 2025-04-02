<?php
session_start();
session_unset(); // Unset all session variables
session_destroy(); // Destroy the session

// Clear session cookies (important)
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time() - 3600, '/');
}

// Prevent back button from accessing cached pages
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

// Redirect to login page
header("Location: login.html");
exit();
?>
