<?php
// includes/db.php - Database Connection

// Include the configuration file
require_once 'config.php';

try {
    // Create a new PDO instance
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET, DB_USER, DB_PASS);

    // Set the PDO error mode to exception
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Optional: Set default fetch mode to associative array
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    // Optional: Define a constant to check if connection was included successfully
    // define('DB_CONNECTED', true);

} catch(PDOException $e) {
    // Log the error (consider using error_log)
    error_log("Database Connection Error: " . $e->getMessage());
    // Display a generic error message to the user
    die("خطا در اتصال به پایگاه داده. لطفاً بعداً دوباره تلاش کنید.");
    // Or redirect to an error page
    // header("Location: /error.php");
    // exit;
}

?>
