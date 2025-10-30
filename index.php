<?php
// index.php - Main Entry Point

// Include session management
require_once 'includes/session.php';

// Redirect based on login status
if (isLoggedIn()) {
    header("Location: dashboard.php");
} else {
    header("Location: pages/login.php");
}
exit; // Always exit after a header redirect
?>
