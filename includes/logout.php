<?php
// includes/logout.php - Handle User Logout

// Include session management
require_once 'session.php';

// Call the logout function
logoutUser();

// Redirect back to the login page
header("Location: ../pages/login.php");
exit; // Always exit after a header redirect
?>
