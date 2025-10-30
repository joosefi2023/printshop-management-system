<?php
// api/apply_discount.php - Validate and apply discount code via AJAX

require_once '../includes/session.php';
require_once '../includes/db.php';

if (!isLoggedIn() || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'دسترسی غیر مجاز یا روش نامعتبر.']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['code']) || empty(trim($input['code']))) {
    echo json_encode(['success' => false, 'message' => 'کد تخفیف نامعتبر.']);
    exit;
}

$code = trim($input['code']);

try {
    // Fetch discount code details
    $stmt = $pdo->prepare("
        SELECT id, code, type, value, min_order_amount, max_discount_amount, usage_limit, user_limit, valid_from, valid_until, is_active, allowed_payment_gateways
        FROM discount_codes
        WHERE code = :code AND is_active = 1
        LIMIT 1
    ");
    $stmt->bindParam(':code', $code, PDO::PARAM_STR);
    $stmt->execute();
    $discount = $stmt->fetch();

    if (!$discount) {
        echo json_encode(['success' => false, 'message' => 'کد تخفیف یافت نشد یا غیرفعال است.']);
        exit;
    }

    // Check validity dates
    $now = new DateTime();
    if ($discount['valid_from'] && new DateTime($discount['valid_from']) > $now) {
        echo json_encode(['success' => false, 'message' => 'این کد تخفیف هنوز فعال نشده است.']);
        exit;
    }
    if ($discount['valid_until'] && new DateTime($discount['valid_until']) < $now) {
        echo json_encode(['success' => false, 'message' => 'این کد تخفیف منقضی شده است.']);
        exit;
    }

    // Check usage limits (global and per user)
    // Global limit
    if ($discount['usage_limit'] !== null) {
        $usageStmt = $pdo->prepare("SELECT COUNT(*) FROM payments WHERE description LIKE :code_pattern");
        $code_pattern = '%' . $discount['code'] . '%'; // Assuming description stores applied codes
        $usageStmt->bindParam(':code_pattern', $code_pattern);
        $usageStmt->execute();
        $globalUsageCount = $usageStmt->fetchColumn();
        if ($globalUsageCount >= $discount['usage_limit']) {
             echo json_encode(['success' => false, 'message' => 'تعداد دفعات استفاده از این کد تخفیف به پایان رسیده است.']);
             exit;
        }
    }

    // Per-user limit (requires user session)
    if ($discount['user_limit'] > 0) {
        $userId = getUserId();
        if ($userId > 0) { // Ensure user is logged in
            $usageStmt = $pdo->prepare("SELECT COUNT(*) FROM payments WHERE order_id IN (SELECT order_id FROM orders WHERE customer_id = :user_id) AND description LIKE :code_pattern");
            $usageStmt->bindParam(':user_id', $userId, PDO::PARAM_INT); // This assumes user_id in orders refers to customer_id, adjust if needed
            $usageStmt->bindParam(':code_pattern', $code_pattern);
            $usageStmt->execute();
            $userUsageCount = $usageStmt->fetchColumn();
            if ($userUsageCount >= $discount['user_limit']) {
                 echo json_encode(['success' => false, 'message' => 'شما بیش از حد مجاز از این کد تخفیف استفاده کرده‌اید.']);
                 exit;
            }
        } else {
             // If user is not logged in, we can't check per-user limit, might need to deny if limit is strict
             // For now, we'll proceed if no user is logged in, but ideally, discounts requiring user limits should need login.
             // Or, we check against the session cart total if no user ID.
             // For simplicity here, we'll assume the check happens during finalization when user context is clearer.
             // This is a simplification. A better approach might involve temporary session-based tracking for guest carts.
             // For now, let's just warn if not logged in and a user limit exists.
             error_log("Warning: Discount code {$code} has a user limit, but user is not logged in for check.");
        }
    }


    // Calculate discount based on cart total (we need to get it from session)
    $cart_total = 0;
    if (isset($_SESSION['preorder_cart'])) {
        foreach ($_SESSION['preorder_cart'] as $item) {
            $cart_total += $item['total_price'];
        }
    }

    // Add shipping cost temporarily for min_order check, subtract later
    $total_with_shipping = $cart_total + ($_SESSION['shipping_cost'] ?? 0);

    if ($discount['min_order_amount'] > 0 && $total_with_shipping < $discount['min_order_amount']) {
        echo json_encode(['success' => false, 'message' => "حداقل مبلغ سبد خرید برای استفاده از این کد {$discount['min_order_amount']} تومان است."]);
        exit;
    }

    $discount_amount = 0;
    if ($discount['type'] == 'percentage') {
        $discount_amount = min($cart_total * ($discount['value'] / 100), $discount['max_discount_amount'] ?? PHP_FLOAT_MAX);
    } elseif ($discount['type'] == 'fixed') {
        $discount_amount = min($discount['value'], $cart_total); // Cannot discount more than cart total
    }

    // Ensure discount doesn't exceed cart total
    $discount_amount = min($discount_amount, $cart_total);

    echo json_encode(['success' => true, 'discount_amount' => $discount_amount]);

} catch (PDOException $e) {
    error_log("Discount Apply Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'خطای داخلی سرور.']);
}
?>
