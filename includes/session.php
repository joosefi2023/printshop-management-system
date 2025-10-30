<?php
// includes/session.php - Session Management

// Start the session (or resume an existing one)
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Function to check if a user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']) && isset($_SESSION['username']) && isset($_SESSION['role']);
}

// Function to check if the logged-in user is an admin
function isAdmin() {
    return isLoggedIn() && $_SESSION['role'] === 'admin';
}

// Function to get the full name of the logged-in user (combines first and last name)
function getUserFullName() {
    if (isLoggedIn()) {
        return htmlspecialchars($_SESSION['first_name'] . ' ' . $_SESSION['last_name']);
    }
    return 'کاربر ناشناس';
}

// Function to get the role of the logged-in user
function getUserRole() {
    if (isLoggedIn()) {
        return htmlspecialchars($_SESSION['role']);
    }
    return 'unknown';
}

// Function to get the user ID
function getUserId() {
    if (isLoggedIn()) {
        return (int) $_SESSION['user_id'];
    }
    return 0;
}

// Optional: Function to log out the user
function logoutUser() {
    // Unset all session variables
    $_SESSION = array();

    // If it's desired to kill the session, also delete the session cookie.
    // Note: This will destroy the session, and not just the session data!
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }

    // Finally, destroy the session.
    session_destroy();
}

?>
