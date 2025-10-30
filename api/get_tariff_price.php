<?php
// api/get_tariff_price.php - Get tariff price based on print options via AJAX

require_once '../includes/session.php';
require_once '../includes/db.php';

if (!isLoggedIn() || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'دسترسی غیر مجاز یا روش نامعتبر.']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['print_type']) || !isset($input['paper_size']) || !isset($input['sides']) || !isset($input['pages_per_side'])) {
    echo json_encode(['success' => false, 'message' => 'ورودی ناقص.']);
    exit;
}

$print_type = $input['print_type'];
$paper_size = $input['paper_size'];
$sides = $input['sides']; // '1' or '0.5'
$pages_per_side = $input['pages_per_side']; // '1', '0.5', '0.25'

try {
    $stmt = $pdo->prepare("
        SELECT base_price_per_sheet
        FROM tariffs
        WHERE print_type = :print_type
          AND paper_size = :paper_size
          AND sides = :sides
          AND pages_per_side = :pages_per_side
          AND is_active = 1
        LIMIT 1
    ");

    $stmt->bindParam(':print_type', $print_type, PDO::PARAM_STR);
    $stmt->bindParam(':paper_size', $paper_size, PDO::PARAM_STR);
    $stmt->bindParam(':sides', $sides, PDO::PARAM_STR); // Keep as string for ENUM
    $stmt->bindParam(':pages_per_side', $pages_per_side, PDO::PARAM_STR); // Keep as string for DECIMAL match

    $stmt->execute();
    $result = $stmt->fetch();

    if ($result) {
        echo json_encode(['success' => true, 'price' => (int)$result['base_price_per_sheet']]); // Cast to int if price is stored as int
    } else {
        echo json_encode(['success' => false, 'message' => 'قیمت پایه یافت نشد.']);
    }

} catch (PDOException $e) {
    error_log("Tariff Price Fetch Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'خطای داخلی سرور.']);
}
?>
