<?php
// api/submit_preorder.php - Submit the pre-order via AJAX

require_once '../includes/session.php';
require_once '../includes/db.php';

if (!isLoggedIn() || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'دسترسی غیر مجاز یا روش نامعتبر.']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['cart']) || empty($input['cart'])) {
    echo json_encode(['success' => false, 'message' => 'سبد خرید نمی‌تواند خالی باشد.']);
    exit;
}

$cart = $input['cart'];
$shipping_method_id = $input['shipping_method_id'] ?? null;
$shipping_cost = (float) ($input['shipping_cost'] ?? 0);
$discount_code = $input['discount_code'] ?? null;
$discount_amount = (float) ($input['discount_amount'] ?? 0);
$notes = $input['notes'] ?? '';
$existing_preorder_id = $input['existing_preorder_id'] ?? null; // For editing

// Validate cart items structure if needed
foreach ($cart as $item) {
    if (!isset($item['id'], $item['name'], $item['quantity'], $item['unit_price'], $item['total_price'])) {
        echo json_encode(['success' => false, 'message' => 'داده‌های سبد خرید نامعتبر است.']);
        exit;
    }
}

$pdo->beginTransaction();

try {
    $preorder_id = null;
    $is_edit = $existing_preorder_id && $existing_preorder_id > 0;

    if ($is_edit) {
        // Editing existing preorder
        $preorder_id = (int) $existing_preorder_id;
        // Delete existing items first
        $deleteItemsStmt = $pdo->prepare("DELETE FROM preorder_items WHERE preorder_id = :preorder_id");
        $deleteItemsStmt->bindParam(':preorder_id', $preorder_id, PDO::PARAM_INT);
        $deleteItemsStmt->execute();

        $deletePrintItemsStmt = $pdo->prepare("DELETE FROM preorder_printing_items WHERE preorder_id = :preorder_id");
        $deletePrintItemsStmt->bindParam(':preorder_id', $preorder_id, PDO::PARAM_INT);
        $deletePrintItemsStmt->execute();

        // Update the preorder record itself
        $stmt = $pdo->prepare("
            UPDATE preorders
            SET total_amount = :total_amount,
                discount_amount = :discount_amount,
                shipping_amount = :shipping_amount,
                final_amount = :final_amount,
                notes = :notes,
                updated_at = NOW()
            WHERE preorder_id = :preorder_id
        ");

        $total_amount = array_sum(array_column($cart, 'total_price'));
        $final_amount = $total_amount + $shipping_cost - $discount_amount;

        $stmt->bindParam(':total_amount', $total_amount, PDO::PARAM_STR); // Use STR to handle decimals
        $stmt->bindParam(':discount_amount', $discount_amount, PDO::PARAM_STR);
        $stmt->bindParam(':shipping_amount', $shipping_cost, PDO::PARAM_STR);
        $stmt->bindParam(':final_amount', $final_amount, PDO::PARAM_STR);
        $stmt->bindParam(':notes', $notes, PDO::PARAM_STR);
        $stmt->bindParam(':preorder_id', $preorder_id, PDO::PARAM_INT);

        $stmt->execute();

        if ($stmt->rowCount() === 0) {
            throw new Exception("پیش سفارش با شماره {$preorder_id} یافت نشد.");
        }

    } else {
        // Creating new preorder
        $stmt = $pdo->prepare("
            INSERT INTO preorders (total_amount, discount_amount, shipping_amount, final_amount, notes)
            VALUES (:total_amount, :discount_amount, :shipping_amount, :final_amount, :notes)
        ");

        $total_amount = array_sum(array_column($cart, 'total_price'));
        $final_amount = $total_amount + $shipping_cost - $discount_amount;

        $stmt->bindParam(':total_amount', $total_amount, PDO::PARAM_STR);
        $stmt->bindParam(':discount_amount', $discount_amount, PDO::PARAM_STR);
        $stmt->bindParam(':shipping_amount', $shipping_cost, PDO::PARAM_STR);
        $stmt->bindParam(':final_amount', $final_amount, PDO::PARAM_STR);
        $stmt->bindParam(':notes', $notes, PDO::PARAM_STR);

        $stmt->execute();
        $preorder_id = $pdo->lastInsertId();
    }

    // Insert items into preorder_items and preorder_printing_items
    foreach ($cart as $item) {
        if ($item['type'] === 'print') {
            // Insert into preorder_printing_items
            // Assuming $item contains detailed print properties
            // Extract properties from summary or pass them explicitly from JS
            // For now, let's assume summary contains enough info or they are in $item directly if added via calc
            // Example structure expected in $item for print:
            // { id: 'print_...', name: 'چاپ و تکثیر: filename', type: 'print', summary: '...', quantity: 1, unit_price: ..., total_price: ..., ... (detailed props) }

            // Extract or map properties from $item
            $file_name = substr($item['name'], strpos($item['name'], ': ') + 2); // Extract filename from 'چاپ و تکثیر: filename'
            $quantity = $item['quantity'];
            $total_price = $item['total_price'];
            $note = $item['summary']; // Or get from a specific note field if available

            // Map other properties from $item or parse summary if necessary
            // This requires careful alignment between JS object structure and DB fields.
            // For now, let's assume basic mapping and default values where needed.
            // You'll need to expand this mapping based on your full print item structure.
            $print_type = 'black_white'; // Placeholder - parse from summary or item
            $paper_size = 'A4'; // Placeholder
            $sides = '1'; // Placeholder
            $pages_per_side = '1'; // Placeholder
            $total_pages = 0; // Placeholder
            $binding_type = 'none'; // Placeholder
            $price_per_sheet = 0; // Placeholder
            $wire_size_1_qty = 0; // Placeholder - parse from summary or item
            $wire_size_2_qty = 0;
            $wire_size_3_qty = 0;
            $wire_size_4_qty = 0;
            $comb_qty = 0;
            $cover_price = 0;
            $devider_qty = 0;
            $devider_price = 0;
            $takroo_qty = 0;
            $takroo_price = 0;

            // You need to implement logic here to extract these values from $item or its summary
            // Example: preg_match or accessing specific keys added by addPrintToCart function
            // For now, this is a simplified insert with placeholders.
            // You must enhance this part to correctly map all print properties.

            $printStmt = $pdo->prepare("
                INSERT INTO preorder_printing_items (item_id, preorder_id, file_name, print_type, paper_size, sides, pages_per_side, total_pages, binding_type, price_per_sheet, wire_size_1_qty, wire_size_2_qty, wire_size_3_qty, wire_size_4_qty, comb_qty, cover_price, devider_qty, devider_price, takroo_qty, takroo_price, quantity, total_price, note)
                VALUES (:item_id, :preorder_id, :file_name, :print_type, :paper_size, :sides, :pages_per_side, :total_pages, :binding_type, :price_per_sheet, :wire_size_1_qty, :wire_size_2_qty, :wire_size_3_qty, :wire_size_4_qty, :comb_qty, :cover_price, :devider_qty, :devider_price, :takroo_qty, :takroo_price, :quantity, :total_price, :note)
            ");

            $printStmt->bindParam(':item_id', $item['id'], PDO::PARAM_STR); // Use JS-generated ID
            $printStmt->bindParam(':preorder_id', $preorder_id, PDO::PARAM_INT);
            $printStmt->bindParam(':file_name', $file_name, PDO::PARAM_STR);
            $printStmt->bindParam(':print_type', $print_type, PDO::PARAM_STR);
            $printStmt->bindParam(':paper_size', $paper_size, PDO::PARAM_STR);
            $printStmt->bindParam(':sides', $sides, PDO::PARAM_STR);
            $printStmt->bindParam(':pages_per_side', $pages_per_side, PDO::PARAM_STR);
            $printStmt->bindParam(':total_pages', $total_pages, PDO::PARAM_INT);
            $printStmt->bindParam(':binding_type', $binding_type, PDO::PARAM_STR);
            $printStmt->bindParam(':price_per_sheet', $price_per_sheet, PDO::PARAM_STR); // Use STR for decimals
            $printStmt->bindParam(':wire_size_1_qty', $wire_size_1_qty, PDO::PARAM_INT);
            $printStmt->bindParam(':wire_size_2_qty', $wire_size_2_qty, PDO::PARAM_INT);
            $printStmt->bindParam(':wire_size_3_qty', $wire_size_3_qty, PDO::PARAM_INT);
            $printStmt->bindParam(':wire_size_4_qty', $wire_size_4_qty, PDO::PARAM_INT);
            $printStmt->bindParam(':comb_qty', $comb_qty, PDO::PARAM_INT);
            $printStmt->bindParam(':cover_price', $cover_price, PDO::PARAM_STR);
            $printStmt->bindParam(':devider_qty', $devider_qty, PDO::PARAM_INT);
            $printStmt->bindParam(':devider_price', $devider_price, PDO::PARAM_STR);
            $printStmt->bindParam(':takroo_qty', $takroo_qty, PDO::PARAM_INT);
            $printStmt->bindParam(':takroo_price', $takroo_price, PDO::PARAM_STR);
            $printStmt->bindParam(':quantity', $quantity, PDO::PARAM_INT);
            $printStmt->bindParam(':total_price', $total_price, PDO::PARAM_STR);
            $printStmt->bindParam(':note', $note, PDO::PARAM_STR);

            $printStmt->execute();

        } else {
            // Insert into preorder_items (standard products)
            $stmt = $pdo->prepare("
                INSERT INTO preorder_items (preorder_id, product_id, product_name, quantity, unit_price, total_price, note)
                VALUES (:preorder_id, :product_id, :product_name, :quantity, :unit_price, :total_price, :note)
            ");

            $stmt->bindParam(':preorder_id', $preorder_id, PDO::PARAM_INT);
            $stmt->bindParam(':product_id', $item['id'], PDO::PARAM_INT); // Use product_id
            $stmt->bindParam(':product_name', $item['name'], PDO::PARAM_STR);
            $stmt->bindParam(':quantity', $item['quantity'], PDO::PARAM_INT);
            $stmt->bindParam(':unit_price', $item['unit_price'], PDO::PARAM_STR);
            $stmt->bindParam(':total_price', $item['total_price'], PDO::PARAM_STR);
            $stmt->bindParam(':note', $item['summary'], PDO::PARAM_STR); // Use summary as note for standard items if needed

            $stmt->execute();
        }
    }

    $pdo->commit();

    // Generate details text for the popup
    $details_text = "شماره پیش سفارش: **{$preorder_id}**\n";
    $details_text .= "تاریخ پیش فاکتور: " . date('Y/m/d') . "\n\n";

    foreach ($cart as $index => $item) {
        $details_text .= ($index + 1) . ". {$item['name']}\n";
        $details_text .= "   تعداد: {$item['quantity']}\n";
        $details_text .= "   قیمت: " . number_format($item['total_price']) . " تومان\n\n";
    }

    $shippingMethodName = 'نامشخص'; // Fetch name from DB if ID is available
    if ($shipping_method_id) {
        $shipStmt = $pdo->prepare("SELECT name FROM shipping_methods WHERE id = :id LIMIT 1");
        $shipStmt->bindParam(':id', $shipping_method_id, PDO::PARAM_INT);
        $shipStmt->execute();
        $shipRow = $shipStmt->fetch();
        if ($shipRow) {
            $shippingMethodName = $shipRow['name'];
        }
    }
    $details_text .= "**هزینه ارسال ({$shippingMethodName}):** " . number_format($shipping_cost) . " تومان\n";
    $details_text .= "**جمع کل قابل پرداخت:** " . number_format($final_amount) . " تومان\n\n";
    $details_text .= "لطفاً مبلغ را به شماره کارت:\n";
    $details_text .= "``6219861994497914``\n";
    $details_text .= "به نام محمدجواد فرجی واریز نمایید.\n\n";
    $details_text .= "همچنین لطفاً ارسال کنید:\n";
    $details_text .= "- نام و نام خانوادگی\n";
    $details_text .= "- شماره تماس\n";
    $details_text .= "- آدرس دقیق + کدپستی\n";
    $details_text .= "- تصویر فیش واریزی";

    $action = $is_edit ? 'ویرایش' : 'ثبت';
    echo json_encode([
        'success' => true,
        'message' => "پیش سفارش با شماره {$preorder_id} با موفقیت {$action} شد.",
        'preorder_id' => $preorder_id,
        'details_text' => $details_text
    ]);

} catch (Exception $e) {
    $pdo->rollback();
    error_log("Preorder Submit Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'خطا در ثبت/ویرایش پیش سفارش: ' . $e->getMessage()]);
}
?>
