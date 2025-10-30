<?php
// api/search_customer.php - Search customer by phone via AJAX

require_once '../includes/session.php';
require_once '../includes/db.php';

if (!isLoggedIn() || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'دسترسی غیر مجاز یا روش نامعتبر.']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['phone']) || empty(trim($input['phone']))) {
    echo json_encode(['success' => false, 'message' => 'شماره موبایل نامعتبر.']);
    exit;
}

$phone = trim($input['phone']);

try {
    $stmt = $pdo->prepare("
        SELECT Id, customer_phone, c_first_name, c_last_name, customer_email, shipping_address, postal_code
        FROM customers
        WHERE customer_phone = :phone
        LIMIT 1
    ");
    $stmt->bindParam(':phone', $phone, PDO::PARAM_STR);
    $stmt->execute();
    $customer = $stmt->fetch();

    if ($customer) {
        echo json_encode(['success' => true, 'customer' => $customer]);
    } else {
        echo json_encode(['success' => false, 'message' => 'مشتری یافت نشد.']);
    }

} catch (PDOException $e) {
    error_log("Customer Search Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'خطای داخلی سرور.']);
}
?>
