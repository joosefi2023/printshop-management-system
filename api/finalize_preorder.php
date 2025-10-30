<?php
// api/finalize_preorder.php - Finalize a pre-order into an order

require_once '../includes/session.php';
require_once '../includes/db.php';

if (!isLoggedIn() || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'دسترسی غیر مجاز یا روش نامعتبر.']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['preorder_id']) || !isset($input['customer_phone']) || !isset($input['payment_method_id']) || !isset($input['deposit_amount'])) {
    echo json_encode(['success' => false, 'message' => 'ورودی ناقص.']);
    exit;
}

$preorder_id = (int) $input['preorder_id'];
$customer_phone = trim($input['customer_phone']);
$customer_first_name = trim($input['customer_first_name'] ?? '');
$customer_last_name = trim($input['customer_last_name'] ?? '');
$customer_email = trim($input['customer_email'] ?? '');
$customer_address = trim($input['customer_address'] ?? '');
$customer_postal_code = trim($input['customer_postal_code'] ?? '');
$payment_method_id = (int) $input['payment_method_id'];
$deposit_amount = (float) $input['deposit_amount'];
$remaining_amount = (float) $input['remaining_amount'];
$is_completely_paid = $input['is_completely_paid'] === 'yes' ? 'yes' : 'no';
$total_amount = (float) $input['total_amount'];

// Fetch the pre-order and its items
try {
    $pdo->beginTransaction();

    // Check if pre-order exists
    $preStmt = $pdo->prepare("SELECT * FROM preorders WHERE preorder_id = :id LIMIT 1");
    $preStmt->bindParam(':id', $preorder_id, PDO::PARAM_INT);
    $preStmt->execute();
    $preorder = $preStmt->fetch();

    if (!$preorder) {
        throw new Exception("پیش سفارش با شماره {$preorder_id} یافت نشد.");
    }

    // Fetch customer or create if doesn't exist
    $customer_id = null;
    $custStmt = $pdo->prepare("SELECT Id FROM customers WHERE customer_phone = :phone LIMIT 1");
    $custStmt->bindParam(':phone', $customer_phone, PDO::PARAM_STR);
    $custStmt->execute();
    $existingCustomer = $custStmt->fetch();

    if ($existingCustomer) {
        $customer_id = $existingCustomer['Id'];
        // Optionally update existing customer details if provided and changed
        if ($customer_first_name || $customer_last_name || $customer_email || $customer_address) {
             $updateCustStmt = $pdo->prepare("
                 UPDATE customers
                 SET c_first_name = COALESCE(NULLIF(:first_name, ''), c_first_name),
                     c_last_name = COALESCE(NULLIF(:last_name, ''), c_last_name),
                     customer_email = COALESCE(NULLIF(:email, ''), customer_email),
                     shipping_address = COALESCE(NULLIF(:address, ''), shipping_address),
                     postal_code = COALESCE(NULLIF(:postal_code, ''), postal_code),
                     updated_at = NOW()
                 WHERE Id = :id
             ");
             $updateCustStmt->bindParam(':first_name', $customer_first_name);
             $updateCustStmt->bindParam(':last_name', $customer_last_name);
             $updateCustStmt->bindParam(':email', $customer_email);
             $updateCustStmt->bindParam(':address', $customer_address);
             $updateCustStmt->bindParam(':postal_code', $customer_postal_code);
             $updateCustStmt->bindParam(':id', $customer_id, PDO::PARAM_INT);
             $updateCustStmt->execute();
        }
    } else {
        // Create new customer
        $insertCustStmt = $pdo->prepare("
            INSERT INTO customers (customer_phone, c_first_name, c_last_name, customer_email, shipping_address, postal_code)
            VALUES (:phone, :first_name, :last_name, :email, :address, :postal_code)
        ");
        $insertCustStmt->bindParam(':phone', $customer_phone, PDO::PARAM_STR);
        $insertCustStmt->bindParam(':first_name', $customer_first_name, PDO::PARAM_STR);
        $insertCustStmt->bindParam(':last_name', $customer_last_name, PDO::PARAM_STR);
        $insertCustStmt->bindParam(':email', $customer_email, PDO::PARAM_STR);
        $insertCustStmt->bindParam(':address', $customer_address, PDO::PARAM_STR);
        $insertCustStmt->bindParam(':postal_code', $customer_postal_code, PDO::PARAM_STR);
        $insertCustStmt->execute();
        $customer_id = $pdo->lastInsertId();
    }

    // Fetch payment method details
    $payStmt = $pdo->prepare("SELECT name, label FROM payment_methods WHERE id = :id LIMIT 1");
    $payStmt->bindParam(':id', $payment_method_id, PDO::PARAM_INT);
    $payStmt->execute();
    $payment_method = $payStmt->fetch();

    if (!$payment_method) {
        throw new Exception("روش پرداخت نامعتبر.");
    }

    // Fetch shipping method details from preorder
    $shipping_method_name = 'نامشخص';
    if ($preorder['shipping_method_id']) {
        $shipStmt = $pdo->prepare("SELECT name FROM shipping_methods WHERE id = :id LIMIT 1");
        $shipStmt->bindParam(':id', $preorder['shipping_method_id'], PDO::PARAM_INT);
        $shipStmt->execute();
        $shipRow = $shipStmt->fetch();
        if ($shipRow) {
            $shipping_method_name = $shipRow['name'];
        }
    }


    // Insert into orders table
    $order_status = 'تایید شده'; // Initial status
    $insertOrderStmt = $pdo->prepare("
        INSERT INTO orders (order_id, customer_id, customer_name, customer_email, customer_phone, status, payment_method, total_amount, discount_amount, shipping_amount, final_amount, is_completely_paid, remaining, shipping_address, notes)
        VALUES (:order_id, :customer_id, :customer_name, :customer_email, :customer_phone, :status, :payment_method, :total_amount, :discount_amount, :shipping_amount, :final_amount, :is_completely_paid, :remaining, :shipping_address, :notes)
    ");

    // Use preorder_id as order_id
    $insertOrderStmt->bindParam(':order_id', $preorder_id, PDO::PARAM_INT);
    $insertOrderStmt->bindParam(':customer_id', $customer_id, PDO::PARAM_INT);
    $insertOrderStmt->bindParam(':customer_name', $customer_first_name . ' ' . $customer_last_name, PDO::PARAM_STR);
    $insertOrderStmt->bindParam(':customer_email', $customer_email, PDO::PARAM_STR);
    $insertOrderStmt->bindParam(':customer_phone', $customer_phone, PDO::PARAM_STR);
    $insertOrderStmt->bindParam(':status', $order_status, PDO::PARAM_STR);
    $insertOrderStmt->bindParam(':payment_method', $payment_method['name'], PDO::PARAM_STR); // Store name or label?
    $insertOrderStmt->bindParam(':total_amount', $total_amount, PDO::PARAM_STR);
    $insertOrderStmt->bindParam(':discount_amount', $preorder['discount_amount'], PDO::PARAM_STR);
    $insertOrderStmt->bindParam(':shipping_amount', $preorder['shipping_amount'], PDO::PARAM_STR);
    $insertOrderStmt->bindParam(':final_amount', $preorder['final_amount'], PDO::PARAM_STR);
    $insertOrderStmt->bindParam(':is_completely_paid', $is_completely_paid, PDO::PARAM_STR);
    $insertOrderStmt->bindParam(':remaining', $remaining_amount, PDO::PARAM_STR);
    $insertOrderStmt->bindParam(':shipping_address', $customer_address, PDO::PARAM_STR);
    $insertOrderStmt->bindParam(':notes', $preorder['notes'], PDO::PARAM_STR);

    $insertOrderStmt->execute();

    // Fetch preorder items and printing items
    $preItemsStmt = $pdo->prepare("SELECT * FROM preorder_items WHERE preorder_id = :id");
    $preItemsStmt->bindParam(':id', $preorder_id, PDO::PARAM_INT);
    $preItemsStmt->execute();
    $preorder_items = $preItemsStmt->fetchAll(PDO::FETCH_ASSOC);

    $prePrintItemsStmt = $pdo->prepare("SELECT * FROM preorder_printing_items WHERE preorder_id = :id");
    $prePrintItemsStmt->bindParam(':id', $preorder_id, PDO::PARAM_INT);
    $prePrintItemsStmt->execute();
    $preorder_printing_items = $prePrintItemsStmt->fetchAll(PDO::FETCH_ASSOC);

    // Insert items into order_items
    foreach ($preorder_items as $item) {
        $insertOrderItemStmt = $pdo->prepare("
            INSERT INTO order_items (order_id, product_id, product_name, quantity, unit_price, total_price, note)
            VALUES (:order_id, :product_id, :product_name, :quantity, :unit_price, :total_price, :note)
        ");
        $insertOrderItemStmt->bindParam(':order_id', $preorder_id, PDO::PARAM_INT); // Use preorder_id as order_id
        $insertOrderItemStmt->bindParam(':product_id', $item['product_id'], PDO::PARAM_INT);
        $insertOrderItemStmt->bindParam(':product_name', $item['product_name'], PDO::PARAM_STR);
        $insertOrderItemStmt->bindParam(':quantity', $item['quantity'], PDO::PARAM_INT);
        $insertOrderItemStmt->bindParam(':unit_price', $item['unit_price'], PDO::PARAM_STR);
        $insertOrderItemStmt->bindParam(':total_price', $item['total_price'], PDO::PARAM_STR);
        $insertOrderItemStmt->bindParam(':note', $item['note'], PDO::PARAM_STR);
        $insertOrderItemStmt->execute();
    }

    // Insert printing items into printing_items
    foreach ($preorder_printing_items as $item) {
        $insertPrintingItemStmt = $pdo->prepare("
            INSERT INTO printing_items (item_id, order_id, file_name, print_type, paper_size, sides, pages_per_side, total_pages, binding_type, price_per_sheet, wire_size_1_qty, wire_size_2_qty, wire_size_3_qty, wire_size_4_qty, comb_qty, cover_price, devider_qty, devider_price, takroo_qty, takroo_price, quantity, total_price, note)
            VALUES (:item_id, :order_id, :file_name, :print_type, :paper_size, :sides, :pages_per_side, :total_pages, :binding_type, :price_per_sheet, :wire_size_1_qty, :wire_size_2_qty, :wire_size_3_qty, :wire_size_4_qty, :comb_qty, :cover_price, :devider_qty, :devider_price, :takroo_qty, :takroo_price, :quantity, :total_price, :note)
        ");
        // Map preorder_printing_items fields to printing_items fields
        // Note: item_id in printing_items likely refers to the ID from order_items, which is complex to determine here without a direct link.
        // A common approach is to link via order_items.Id after inserting them, or store a temporary link.
        // For simplicity here, we'll insert with a placeholder or NULL for item_id if it's a foreign key to order_items.
        // If item_id in printing_items is just an internal unique ID, use $item['printing_item_id'] or generate a new one.
        // Let's assume it's an internal unique ID and use the existing one, mapping preorder_id to order_id.
        $insertPrintingItemStmt->bindParam(':item_id', $item['printing_item_id'], PDO::PARAM_INT); // This might need adjustment based on actual FK relationship
        $insertPrintingItemStmt->bindParam(':order_id', $preorder_id, PDO::PARAM_INT); // Use preorder_id as order_id
        $insertPrintingItemStmt->bindParam(':file_name', $item['file_name'], PDO::PARAM_STR);
        $insertPrintingItemStmt->bindParam(':print_type', $item['print_type'], PDO::PARAM_STR);
        $insertPrintingItemStmt->bindParam(':paper_size', $item['paper_size'], PDO::PARAM_STR);
        $insertPrintingItemStmt->bindParam(':sides', $item['sides'], PDO::PARAM_STR);
        $insertPrintingItemStmt->bindParam(':pages_per_side', $item['pages_per_side'], PDO::PARAM_STR);
        $insertPrintingItemStmt->bindParam(':total_pages', $item['total_pages'], PDO::PARAM_INT);
        $insertPrintingItemStmt->bindParam(':binding_type', $item['binding_type'], PDO::PARAM_STR);
        $insertPrintingItemStmt->bindParam(':price_per_sheet', $item['price_per_sheet'], PDO::PARAM_STR);
        $insertPrintingItemStmt->bindParam(':wire_size_1_qty', $item['wire_size_1_qty'], PDO::PARAM_INT);
        $insertPrintingItemStmt->bindParam(':wire_size_2_qty', $item['wire_size_2_qty'], PDO::PARAM_INT);
        $insertPrintingItemStmt->bindParam(':wire_size_3_qty', $item['wire_size_3_qty'], PDO::PARAM_INT);
        $insertPrintingItemStmt->bindParam(':wire_size_4_qty', $item['wire_size_4_qty'], PDO::PARAM_INT);
        $insertPrintingItemStmt->bindParam(':comb_qty', $item['comb_qty'], PDO::PARAM_INT);
        $insertPrintingItemStmt->bindParam(':cover_price', $item['cover_price'], PDO::PARAM_STR);
        $insertPrintingItemStmt->bindParam(':devider_qty', $item['devider_qty'], PDO::PARAM_INT);
        $insertPrintingItemStmt->bindParam(':devider_price', $item['devider_price'], PDO::PARAM_STR);
        $insertPrintingItemStmt->bindParam(':takroo_qty', $item['takroo_qty'], PDO::PARAM_INT);
        $insertPrintingItemStmt->bindParam(':takroo_price', $item['takroo_price'], PDO::PARAM_STR);
        $insertPrintingItemStmt->bindParam(':quantity', $item['quantity'], PDO::PARAM_INT);
        $insertPrintingItemStmt->bindParam(':total_price', $item['total_price'], PDO::PARAM_STR);
        $insertPrintingItemStmt->bindParam(':note', $item['note'], PDO::PARAM_STR);
        $insertPrintingItemStmt->execute();
    }

    // Record the deposit as a transaction
    if ($deposit_amount > 0) {
        $insertTransStmt = $pdo->prepare("
            INSERT INTO transactions (type, amount, bank_account_id, description, related_order_id, date)
            SELECT 'income', :amount, pm.Bank_account_id, CONCAT('واریز سفارش ', :order_id), :order_id, CURDATE()
            FROM payment_methods pm
            WHERE pm.id = :payment_method_id
            LIMIT 1
        ");
        $insertTransStmt->bindParam(':amount', $deposit_amount, PDO::PARAM_STR);
        $insertTransStmt->bindParam(':order_id', $preorder_id, PDO::PARAM_INT);
        $insertTransStmt->bindParam(':payment_method_id', $payment_method_id, PDO::PARAM_INT);
        $insertTransStmt->execute();

        // If not fully paid, record the remaining amount as a scheduled payment
        if ($remaining_amount > 0) {
            $paymentType = 'customer'; // Default to customer payment type
            // Determine payment type based on method if needed (e.g., snappey)
            // ... logic to check payment method name/label and set $paymentType ...

            $insertPaymentStmt = $pdo->prepare("
                INSERT INTO payments (order_id, amount, scheduled_date, payment_type, status, description)
                VALUES (:order_id, :amount, DATE_ADD(CURDATE(), INTERVAL 7 DAY), :payment_type, 'scheduled', 'مانده سفارش')
            "); // Assuming a 7-day schedule for remaining, adjust as needed
            $insertPaymentStmt->bindParam(':order_id', $preorder_id, PDO::PARAM_INT);
            $insertPaymentStmt->bindParam(':amount', $remaining_amount, PDO::PARAM_STR);
            $insertPaymentStmt->bindParam(':payment_type', $paymentType, PDO::PARAM_STR);
            $insertPaymentStmt->execute();
        }
    }


    // Delete the pre-order and its items
    $deletePreItemsStmt = $pdo->prepare("DELETE FROM preorder_items WHERE preorder_id = :id");
    $deletePreItemsStmt->bindParam(':id', $preorder_id, PDO::PARAM_INT);
    $deletePreItemsStmt->execute();

    $deletePrePrintItemsStmt = $pdo->prepare("DELETE FROM preorder_printing_items WHERE preorder_id = :id");
    $deletePrePrintItemsStmt->bindParam(':id', $preorder_id, PDO::PARAM_INT);
    $deletePrePrintPrintItemsStmt->execute();

    $deletePreorderStmt = $pdo->prepare("DELETE FROM preorders WHERE preorder_id = :id");
    $deletePreorderStmt->bindParam(':id', $preorder_id, PDO::PARAM_INT);
    $deletePreorderStmt->execute();


    $pdo->commit();

    echo json_encode(['success' => true, 'message' => "پیش سفارش {$preorder_id} با موفقیت به سفارش تبدیل شد."]);

} catch (Exception $e) {
    $pdo->rollback();
    error_log("Preorder Finalization Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'خطا در تایید نهایی پیش سفارش: ' . $e->getMessage()]);
}
?>
