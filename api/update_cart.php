<?php
// api/update_cart.php - Update session cart and related variables via AJAX

require_once '../includes/session.php';
require_once '../includes/db.php'; // Not strictly needed here, but good practice if DB interaction is needed later

if (!isLoggedIn() || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'دسترسی غیر مجاز یا روش نامعتبر.']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

if (isset($input['cart'])) {
    $_SESSION['preorder_cart'] = $input['cart'];
}

if (isset($input['shipping_cost'])) {
    $_SESSION['shipping_cost'] = (float) $input['shipping_cost'];
}

if (isset($input['discount_amount'])) {
    $_SESSION['discount_amount'] = (float) $input['discount_amount'];
}

if (isset($input['discount_code'])) {
    $_SESSION['discount_code'] = $input['discount_code'];
}

if (isset($input['shipping_method_id'])) {
    $_SESSION['shipping_method_id'] = $input['shipping_method_id'];
}

if (isset($input['notes'])) {
    $_SESSION['preorder_notes'] = $input['notes'];
}

echo json_encode(['success' => true, 'message' => 'اطلاعات سبد با موفقیت به‌روز شد.']);
?>
