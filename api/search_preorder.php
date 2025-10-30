<?php
// api/search_preorder.php - Search for an existing pre-order by ID via AJAX

require_once '../includes/session.php';
require_once '../includes/db.php';

if (!isLoggedIn() || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'دسترسی غیر مجاز یا روش نامعتبر.']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['id']) || empty(trim($input['id']))) {
    echo json_encode(['success' => false, 'message' => 'شماره پیش سفارش نامعتبر.']);
    exit;
}

$preorder_id = (int) $input['id'];

try {
    // Fetch preorder details
    $stmt = $pdo->prepare("
        SELECT preorder_id as id, total_amount, discount_amount, shipping_amount as shipping_cost, final_amount, notes, shipping_method_id
        FROM preorders
        WHERE preorder_id = :id
        LIMIT 1
    ");
    $stmt->bindParam(':id', $preorder_id, PDO::PARAM_INT);
    $stmt->execute();
    $preorder = $stmt->fetch();

    if (!$preorder) {
        echo json_encode(['success' => false, 'message' => 'پیش سفارش یافت نشد.']);
        exit;
    }

    // Fetch preorder items (standard products)
    $itemsStmt = $pdo->prepare("
        SELECT Id, preorder_id, product_id, product_name, quantity, unit_price, total_price, note
        FROM preorder_items
        WHERE preorder_id = :id
    ");
    $itemsStmt->bindParam(':id', $preorder_id, PDO::PARAM_INT);
    $itemsStmt->execute();
    $items = $itemsStmt->fetchAll(PDO::FETCH_ASSOC);

    // Fetch preorder printing items
    $printItemsStmt = $pdo->prepare("
        SELECT printing_item_id as Id, preorder_id, file_name, print_type, paper_size, sides, pages_per_side, total_pages, binding_type, price_per_sheet, wire_size_1_qty, wire_size_2_qty, wire_size_3_qty, wire_size_4_qty, comb_qty, cover_price, devider_qty, devider_price, takroo_qty, takroo_price, quantity, total_price, note
        FROM preorder_printing_items
        WHERE preorder_id = :id
    ");
    $printItemsStmt->bindParam(':id', $preorder_id, PDO::PARAM_INT);
    $printItemsStmt->execute();
    $printItems = $printItemsStmt->fetchAll(PDO::FETCH_ASSOC);

    // Combine items into a single array for the frontend cart structure
    $combined_items = [];
    foreach ($items as $item) {
        $combined_items[] = [
            'id' => $item['product_id'],
            'name' => $item['product_name'],
            'type' => 'product',
            'summary' => $item['note'], // Using note as summary for standard items
            'quantity' => $item['quantity'],
            'unit_price' => $item['unit_price'],
            'total_price' => $item['total_price']
        ];
    }
    foreach ($printItems as $item) {
        // Construct a summary string from print properties
        $summary = "چاپ فایل: {$item['file_name']}\n";
        $summary .= "نوع چاپ: {$item['print_type']}\n";
        $summary .= "سایز کاغذ: {$item['paper_size']}\n";
        $summary .= "وجه چاپ: {$item['sides']}\n";
        $summary .= "تعداد ص در هر رو: {$item['pages_per_side']}\n";
        $summary .= "تعداد صفحه: {$item['total_pages']}\n";
        if ($item['wire_size_1_qty'] > 0) $summary .= "سیم سایز 1 ({$item['wire_size_1_qty']})\n";
        if ($item['wire_size_2_qty'] > 0) $summary .= "سیم سایز 2 ({$item['wire_size_2_qty']})\n";
        if ($item['wire_size_3_qty'] > 0) $summary .= "سیم سایز 3 ({$item['wire_size_3_qty']})\n";
        if ($item['wire_size_4_qty'] > 0) $summary .= "سیم سایز 4 ({$item['wire_size_4_qty']})\n";
        if ($item['comb_qty'] > 0) $summary .= "منگنه ({$item['comb_qty']})\n";
        if ($item['devider_qty'] > 0) $summary .= "دیوایدر ({$item['devider_qty']})\n";
        if ($item['takroo_qty'] > 0) $summary .= "تک رو/رنگی ({$item['takroo_qty']})\n";
        if ($item['note']) $summary .= "توضیحات: {$item['note']}\n";

        $combined_items[] = [
            'id' => 'print_' . $item['Id'], // Use a unique ID prefix for print items
            'name' => 'چاپ و تکثیر: ' . $item['file_name'],
            'type' => 'print',
            'summary' => trim($summary),
            'quantity' => $item['quantity'],
            'unit_price' => $item['total_price'] / $item['quantity'], // Calculate unit price
            'total_price' => $item['total_price']
        ];
    }

    $preorder['items'] = $combined_items;

    echo json_encode(['success' => true, 'preorder' => $preorder]);

} catch (PDOException $e) {
    error_log("Preorder Search Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'خطای داخلی سرور.']);
}
?>
