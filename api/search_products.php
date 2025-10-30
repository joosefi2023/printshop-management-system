<?php
// api/search_products.php - Search products via AJAX

require_once '../includes/session.php';
require_once '../includes/db.php';

if (!isLoggedIn() || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'دسترسی غیر مجاز یا روش نامعتبر.']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['search']) || empty(trim($input['search']))) {
    echo json_encode(['success' => false, 'message' => 'ورودی جستجو نامعتبر.']);
    exit;
}

$search_term = '%' . trim($input['search']) . '%';

try {
    $stmt = $pdo->prepare("
        SELECT product_id, product_name, product_price
        FROM products
        WHERE product_name LIKE :search_term
          AND product_status = 'active'
        ORDER BY product_name
        LIMIT 10
    ");

    $stmt->bindParam(':search_term', $search_term, PDO::PARAM_STR);

    $stmt->execute();
    $results = $stmt->fetchAll();

    echo json_encode(['success' => true, 'products' => $results]);

} catch (PDOException $e) {
    error_log("Product Search Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'خطای داخلی سرور.']);
}
?>
