<?php
// pages/preorder_create.php - Create New Pre-Order or Load Existing One

require_once '../includes/session.php';
require_once '../includes/db.php';

if (!isLoggedIn()) {
    header("Location: login.php");
    exit;
}

// Initialize cart session if not already set
if (!isset($_SESSION['preorder_cart'])) {
    $_SESSION['preorder_cart'] = [];
}

// Initialize preorder number session if not set (for new preorders)
if (!isset($_SESSION['current_preorder_id'])) {
    $_SESSION['current_preorder_id'] = null; // Will be set after creation
}

$preorder_id = $_SESSION['current_preorder_id'];
$cart_items = $_SESSION['preorder_cart'];
$cart_total = 0;
$shipping_cost = 0;
$discount_amount = 0;
$final_total = 0;

// Calculate initial totals based on session cart
foreach ($cart_items as $item) {
    $cart_total += $item['total_price'];
}
if (isset($_SESSION['shipping_cost'])) {
    $shipping_cost = $_SESSION['shipping_cost'];
}
if (isset($_SESSION['discount_amount'])) {
    $discount_amount = $_SESSION['discount_amount'];
}
$final_total = $cart_total + $shipping_cost - $discount_amount;

?>

<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ثبت/ویرایش پیش سفارش - چاپسون</title>
    <style>
        body {
            font-family: Tahoma, Arial, sans-serif; /* Use default theme font */
            margin: 0;
            padding: 1rem;
            background-color: #f8f9fa;
        }
        .header {
            background-color: #343a40;
            color: white;
            padding: 1rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .user-info {
            text-align: right;
        }
        .user-info span {
            display: block;
        }
        .user-name {
            font-weight: bold;
        }
        .user-role {
            font-size: 0.9em;
            color: #adb5bd;
        }
        .logout-btn {
            background-color: #dc3545;
            color: white;
            padding: 0.5rem 1rem;
            text-decoration: none;
            border-radius: 4px;
        }
        .logout-btn:hover {
            background-color: #c82333;
        }
        .main-content {
            display: grid;
            grid-template-columns: 1fr 1fr; /* Two equal columns */
            gap: 1rem;
            margin-top: 1rem;
        }
        .left-column, .right-column {
            background-color: white;
            padding: 1rem;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        .section-title {
            margin-top: 0;
            border-bottom: 1px solid #ccc;
            padding-bottom: 0.5rem;
        }
        .search-box {
            width: calc(100% - 110px); /* Make space for button */
            padding: 0.5rem;
            margin-bottom: 1rem;
        }
        .search-btn, .new-btn {
            padding: 0.5rem 1rem;
            margin-bottom: 1rem;
            margin-left: 0.5rem;
        }
        .new-btn {
            background-color: #28a745;
        }
        .new-btn:hover {
            background-color: #218838;
        }
        .calculator-section input, .calculator-section select {
            width: 100%;
            padding: 0.5rem;
            margin-bottom: 0.5rem;
            box-sizing: border-box;
        }
        .binding-option {
            display: flex;
            align-items: center;
            margin-bottom: 0.5rem;
        }
        .binding-option input[type="checkbox"] {
            margin-right: 0.5rem;
        }
        .binding-option input[type="number"] {
            width: 60px;
            margin-right: 0.5rem;
        }
        .calculate-btn {
            background-color: #ffc107;
            color: black;
            padding: 0.5rem 1rem;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            width: 100%;
        }
        .calculate-btn:hover {
            background-color: #e0a800;
        }
        .cart-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.5rem;
            border-bottom: 1px solid #eee;
        }
        .cart-item-details {
            flex-grow: 1;
        }
        .cart-item-quantity {
            width: 60px;
            margin: 0 1rem;
        }
        .cart-item-remove {
            background-color: #dc3545;
            color: white;
            border: none;
            padding: 0.25rem 0.5rem;
            cursor: pointer;
            border-radius: 4px;
        }
        .cart-total {
            font-weight: bold;
            margin-top: 1rem;
            text-align: right;
        }
        .summary-box {
            background-color: #e9ecef;
            padding: 0.5rem;
            border-radius: 4px;
            margin-top: 0.5rem;
            white-space: pre-line; /* Preserve line breaks in summary */
        }
        .shipping-discount-section {
            margin-top: 1rem;
        }
        .shipping-discount-section select, .shipping-discount-section input {
            width: 100%;
            padding: 0.5rem;
            margin-bottom: 0.5rem;
            box-sizing: border-box;
        }
        .final-total {
            font-size: 1.2em;
            font-weight: bold;
            text-align: center;
            margin-top: 1rem;
            padding: 0.5rem;
            background-color: #d4edda;
            border: 1px solid #c3e6cb;
            border-radius: 4px;
        }
        .submit-buttons {
            display: flex;
            justify-content: space-between;
            margin-top: 1rem;
        }
        .submit-btn, .confirm-btn {
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 1rem;
        }
        .submit-btn {
            background-color: #007bff;
            color: white;
        }
        .submit-btn:hover {
            background-color: #0056b3;
        }
        .confirm-btn {
            background-color: #28a745;
            color: white;
        }
        .confirm-btn:hover, .confirm-btn:disabled {
            background-color: #1e7e34;
            opacity: 0.6;
            cursor: not-allowed;
        }
        .notes-section textarea {
            width: 100%;
            padding: 0.5rem;
            margin-top: 0.5rem;
            box-sizing: border-box;
            resize: vertical;
        }
        .preorder-number-display {
            font-size: 1.2em;
            font-weight: bold;
            text-align: center;
            padding: 0.5rem;
            background-color: #f0f0f0;
            border-radius: 4px;
            margin-bottom: 1rem;
        }
    </style>
</head>
<body>

    <header class="header">
        <div class="user-info">
            <span class="user-name"><?php echo getUserFullName(); ?></span>
            <span class="user-role"><?php echo getUserRole(); ?></span>
        </div>
        <div>
            <a href="../includes/logout.php" class="logout-btn">خروج</a>
        </div>
    </header>

    <main class="main-content">
        <div class="left-column">
            <h2 class="section-title">جستجوی پیش سفارش</h2>
            <input type="text" id="preorderSearch" class="search-box" placeholder="شماره پیش سفارش را وارد کنید...">
            <button class="search-btn" onclick="searchPreorder()">جستجو</button>
            <button class="new-btn" onclick="newPreorder()">پیش سفارش جدید</button>

            <div id="preorderNumberDisplay" class="preorder-number-display" style="<?php echo $preorder_id ? '' : 'display:none;'; ?>">
                شماره پیش سفارش: <?php echo $preorder_id; ?>
            </div>

            <h2 class="section-title">سبد خرید</h2>
            <div id="cartItems">
                <?php if (empty($cart_items)): ?>
                    <p>سبد خرید خالی است.</p>
                <?php else: ?>
                    <?php foreach ($cart_items as $key => $item): ?>
                        <div class="cart-item">
                            <div class="cart-item-details">
                                <div><?php echo htmlspecialchars($item['name']); ?></div>
                                <div class="summary-box"><?php echo htmlspecialchars($item['summary']); ?></div>
                            </div>
                            <input type="number" class="cart-item-quantity" value="<?php echo $item['quantity']; ?>" min="1" onchange="updateCartQuantity(<?php echo $key; ?>, this.value)">
                            <button class="cart-item-remove" onclick="removeCartItem(<?php echo $key; ?>)">حذف</button>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            <div class="cart-total">جمع کل سبد: <?php echo number_format($cart_total); ?> تومان</div>

            <div class="shipping-discount-section">
                <h3 class="section-title">هزینه ارسال و کد تخفیف</h3>
                <label for="shippingMethod">روش ارسال:</label>
                <select id="shippingMethod" onchange="updateShippingCost()">
                    <option value="">انتخاب کنید...</option>
                    <?php
                    $stmt = $pdo->query("SELECT id, name, code, default_price FROM shipping_methods WHERE is_active = 1 ORDER BY sort_order");
                    while ($row = $stmt->fetch()) {
                        $selected = (isset($_SESSION['shipping_method_id']) && $_SESSION['shipping_method_id'] == $row['id']) ? 'selected' : '';
                        echo "<option value='{$row['id']}' data-price='{$row['default_price']}' $selected>{$row['name']}</option>";
                    }
                    ?>
                </select>
                <label for="shippingCostInput">هزینه ارسال (تومان):</label>
                <input type="number" id="shippingCostInput" value="<?php echo $shipping_cost; ?>" onchange="updateShippingCost()" <?php echo (isset($_SESSION['shipping_method_id']) && $_SESSION['shipping_method_id']) ? '' : 'disabled'; ?>>

                <label for="discountCode">کد تخفیف:</label>
                <input type="text" id="discountCode" value="<?php echo isset($_SESSION['discount_code']) ? $_SESSION['discount_code'] : ''; ?>" onchange="applyDiscountCode()">
                <button onclick="applyDiscountCode()">اعمال</button>
                <div id="discountAmountDisplay">مقدار تخفیف: <?php echo number_format($discount_amount); ?> تومان</div>
            </div>

            <div class="final-total">جمع کل قابل پرداخت: <?php echo number_format($final_total); ?> تومان</div>

            <div class="notes-section">
                <label for="preorderNotes">توضیحات پیش سفارش:</label>
                <textarea id="preorderNotes" rows="3"><?php echo isset($_SESSION['preorder_notes']) ? $_SESSION['preorder_notes'] : ''; ?></textarea>
            </div>

            <div class="submit-buttons">
                <button class="submit-btn" onclick="submitPreorder()">ثبت پیش سفارش</button>
                <button class="confirm-btn" onclick="confirmPreorder()" <?php echo $preorder_id ? '' : 'disabled'; ?>>تایید نهایی پیش سفارش</button>
            </div>
        </div>

        <div class="right-column">
            <h2 class="section-title">جستجوی محصول</h2>
            <input type="text" id="productSearch" class="search-box" placeholder="نام محصول را جستجو کنید..." oninput="searchProducts()">
            <div id="productSearchResults" style="max-height: 200px; overflow-y: auto; border: 1px solid #ccc; padding: 0.5rem; margin-bottom: 1rem;"></div>

            <h2 class="section-title">ماشین حساب چاپ</h2>
            <div class="calculator-section">
                <label for="fileName">نام فایل:</label>
                <input type="text" id="fileName" placeholder="نام فایل را وارد کنید">
                <label for="printType">نوع چاپ:</label>
                <select id="printType">
                    <option value="black_white">سیاه و سفید</option>
                    <option value="color">رنگی</option>
                </select>
                <label for="paperSize">سایز کاغذ:</label>
                <select id="paperSize">
                    <option value="A4">A4</option>
                    <option value="A3">A3</option>
                    <option value="A5">A5</option>
                </select>
                <label for="sides">وجه چاپ:</label>
                <select id="sides" onchange="updatePagesPerSideOptions()">
                    <option value="1">تک رو (1)</option>
                    <option value="0.5">دو رو (0.5)</option>
                </select>
                <label for="pagesPerSide">تعداد صفحه در هر روی برگه:</label>
                <select id="pagesPerSide">
                    <option value="1">1</option>
                    <option value="0.5">2</option>
                    <option value="0.25">4</option>
                </select>
                <label for="totalPages">تعداد صفحه:</label>
                <input type="number" id="totalPages" placeholder="تعداد صفحات کل را وارد کنید" min="1">

                <label for="basePricePerSheet">قیمت هر برگ (تومان):</label>
                <input type="number" id="basePricePerSheet" placeholder="بر اساس تعرفه" readonly> <!-- Make editable if needed -->

                <label for="totalSheets">تعداد برگ (خواندنی):</label>
                <input type="number" id="totalSheets" readonly>

                <!-- Binding Options -->
                <div class="binding-option">
                    <input type="checkbox" id="wireSize1" onchange="toggleBindingInput('wireSize1Qty')">
                    <label for="wireSize1">سیم سایز 1</label>
                    <input type="number" id="wireSize1Qty" min="0" disabled>
                </div>
                <div class="binding-option">
                    <input type="checkbox" id="wireSize2" onchange="toggleBindingInput('wireSize2Qty')">
                    <label for="wireSize2">سیم سایز 2</label>
                    <input type="number" id="wireSize2Qty" min="0" disabled>
                </div>
                <div class="binding-option">
                    <input type="checkbox" id="wireSize3" onchange="toggleBindingInput('wireSize3Qty')">
                    <label for="wireSize3">سیم سایز 3</label>
                    <input type="number" id="wireSize3Qty" min="0" disabled>
                </div>
                <div class="binding-option">
                    <input type="checkbox" id="wireSize4" onchange="toggleBindingInput('wireSize4Qty')">
                    <label for="wireSize4">سیم سایز 4</label>
                    <input type="number" id="wireSize4Qty" min="0" disabled>
                </div>
                <div class="binding-option">
                    <input type="checkbox" id="comb" onchange="toggleBindingInput('combQty')">
                    <label for="comb">منگنه</label>
                    <input type="number" id="combQty" min="0" disabled>
                </div>
                <div class="binding-option">
                    <input type="checkbox" id="devider" onchange="toggleBindingInput('deviderQty')">
                    <label for="devider">دیوایدر</label>
                    <input type="number" id="deviderQty" min="0" disabled>
                </div>
                <div class="binding-option">
                    <input type="checkbox" id="takroo" onchange="toggleBindingInput('takrooQty')">
                    <label for="takroo">تک رو/رنگی</label>
                    <input type="number" id="takrooQty" min="0" disabled>
                </div>

                <label for="takrooPrice">قیمت واحد تک رو/رنگی (تومان):</label>
                <input type="number" id="takrooPrice" placeholder="بر اساس تعرفه" readonly> <!-- Make editable if needed -->

                <label for="printNote">توضیحات چاپ:</label>
                <input type="text" id="printNote" placeholder="توضیحات اضافی...">

                <button class="calculate-btn" onclick="calculatePrintCost()">محاسبه</button>
                <div id="printSummary" class="summary-box" style="display:none;"></div>
                <label for="printQuantity">تعداد (پیش فرض 1):</label>
                <input type="number" id="printQuantity" value="1" min="1">
                <button class="calculate-btn" onclick="addPrintToCart()">افزودن به سبد خرید</button>
            </div>
        </div>
    </main>

    <script>
        // --- Session-Based Cart Management ---
        let cart = <?php echo json_encode($cart_items); ?>;
        let currentPreorderId = <?php echo $preorder_id ? json_encode($preorder_id) : 'null'; ?>;
        let cartTotal = <?php echo $cart_total; ?>;
        let shippingCost = <?php echo $shipping_cost; ?>;
        let discountAmount = <?php echo $discount_amount; ?>;
        let finalTotal = <?php echo $final_total; ?>;
        let discountCode = <?php echo isset($_SESSION['discount_code']) ? json_encode($_SESSION['discount_code']) : 'null'; ?>;
        let shippingMethodId = <?php echo isset($_SESSION['shipping_method_id']) ? json_encode($_SESSION['shipping_method_id']) : 'null'; ?>;
        let preorderNotes = <?php echo isset($_SESSION['preorder_notes']) ? json_encode($_SESSION['preorder_notes']) : '""'; ?>;

        // Function to update the cart display and totals in the HTML
        function updateCartDisplay() {
            const cartItemsDiv = document.getElementById('cartItems');
            cartItemsDiv.innerHTML = '';
            if (cart.length === 0) {
                cartItemsDiv.innerHTML = '<p>سبد خرید خالی است.</p>';
            } else {
                cart.forEach((item, index) => {
                    const itemDiv = document.createElement('div');
                    itemDiv.className = 'cart-item';
                    itemDiv.innerHTML = `
                        <div class="cart-item-details">
                            <div>${item.name}</div>
                            <div class="summary-box">${item.summary}</div>
                        </div>
                        <input type="number" class="cart-item-quantity" value="${item.quantity}" min="1" onchange="updateCartQuantity(${index}, this.value)">
                        <button class="cart-item-remove" onclick="removeCartItem(${index})">حذف</button>
                    `;
                    cartItemsDiv.appendChild(itemDiv);
                });
            }
            document.querySelector('.cart-total').textContent = `جمع کل سبد: ${cartTotal.toLocaleString()} تومان`;
            document.getElementById('discountAmountDisplay').textContent = `مقدار تخفیف: ${discountAmount.toLocaleString()} تومان`;
            document.querySelector('.final-total').textContent = `جمع کل قابل پرداخت: ${finalTotal.toLocaleString()} تومان`;
        }

        // Function to update quantity of an item in the session cart
        function updateCartQuantity(index, newQuantity) {
            if (newQuantity < 1) newQuantity = 1; // Ensure minimum quantity
            cart[index].quantity = parseInt(newQuantity);
            cart[index].total_price = cart[index].unit_price * cart[index].quantity; // Update total price for the item
            recalculateTotals();
            updateCartDisplay();
            // Send updated cart to server via AJAX (or update session via hidden form submit if preferred)
            updateSessionCart();
        }

        // Function to remove an item from the session cart
        function removeCartItem(index) {
            cart.splice(index, 1);
            recalculateTotals();
            updateCartDisplay();
            updateSessionCart();
        }

        // Function to recalculate totals based on cart, shipping, and discount
        function recalculateTotals() {
            cartTotal = cart.reduce((sum, item) => sum + item.total_price, 0);
            finalTotal = cartTotal + shippingCost - discountAmount;
        }

        // Function to update the PHP session cart via AJAX
        function updateSessionCart() {
            // This function will make an AJAX call to a PHP script to update the session
            // Example using fetch:
            fetch('../api/update_cart.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    cart: cart,
                    shipping_cost: shippingCost,
                    discount_amount: discountAmount,
                    discount_code: discountCode,
                    shipping_method_id: shippingMethodId,
                    notes: preorderNotes
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    console.log("Session cart updated successfully.");
                } else {
                    console.error("Error updating session cart:", data.message);
                }
            })
            .catch(error => console.error("Fetch error:", error));
        }

        // --- Print Calculator Logic ---
        // Function to update Pages Per Side options based on Sides
        function updatePagesPerSideOptions() {
            const sidesSelect = document.getElementById('sides');
            const ppsSelect = document.getElementById('pagesPerSide');
            const selectedSidesValue = sidesSelect.value;

            // Clear existing options
            ppsSelect.innerHTML = '';

            // Define options based on sides
            if (selectedSidesValue == '1') { // Single Sided
                ppsSelect.innerHTML = `
                    <option value="1">1</option>
                    <option value="0.5">2</option>
                    <option value="0.25">4</option>
                `;
            } else if (selectedSidesValue == '0.5') { // Double Sided
                ppsSelect.innerHTML = `
                    <option value="1">1</option>
                    <option value="0.5">2</option>
                    <option value="0.25">4</option>
                `;
            }
        }

        // Function to toggle binding input fields
        function toggleBindingInput(inputId) {
            const input = document.getElementById(inputId);
            input.disabled = !input.disabled;
            if (!input.disabled) {
                input.value = 1; // Set default value when enabled
            } else {
                input.value = 0; // Reset to 0 when disabled
            }
        }

        // Function to calculate print cost based on tariff
        function calculatePrintCost() {
            const printType = document.getElementById('printType').value;
            const paperSize = document.getElementById('paperSize').value;
            const sides = document.getElementById('sides').value; // '1' or '0.5'
            const pagesPerSideValue = parseFloat(document.getElementById('pagesPerSide').value); // 1, 0.5, 0.25
            const totalPages = parseInt(document.getElementById('totalPages').value);

            if (!printType || !paperSize || !sides || !pagesPerSideValue || !totalPages || totalPages <= 0) {
                alert('لطفاً تمام فیلدهای مربوط به چاپ را پر کنید.');
                return;
            }

            // Calculate total sheets
            const totalSheets = totalPages * parseFloat(sides) * pagesPerSideValue;
            document.getElementById('totalSheets').value = totalSheets;

            // Fetch base price from tariff via AJAX
            fetch('../api/get_tariff_price.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    print_type: printType,
                    paper_size: paperSize,
                    sides: sides,
                    pages_per_side: pagesPerSideValue
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success && data.price) {
                    document.getElementById('basePricePerSheet').value = data.price;
                    // Calculate Takroo Price (if applicable)
                    if (document.getElementById('takroo').checked) {
                        // Fetch price for single-sided based on print_type and paper_size
                        fetch('../api/get_tariff_price.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                            },
                            body: JSON.stringify({
                                print_type: printType,
                                paper_size: paperSize,
                                sides: '1', // Single sided for takroo
                                pages_per_side: '1' // Assuming 1 page per side for base calculation
                            })
                        })
                        .then(response => response.json())
                        .then(takrooData => {
                            if (takrooData.success && takrooData.price) {
                                document.getElementById('takrooPrice').value = takrooData.price;
                            } else {
                                document.getElementById('takrooPrice').value = 0;
                            }
                        })
                        .catch(error => {
                            console.error('Error fetching takroo price:', error);
                            document.getElementById('takrooPrice').value = 0;
                        });
                    } else {
                        document.getElementById('takrooPrice').value = 0;
                    }
                } else {
                    alert('قیمت پایه برای این ترکیب یافت نشد یا خطایی رخ داد.');
                    document.getElementById('basePricePerSheet').value = 0;
                }
            })
            .catch(error => {
                console.error('Error fetching tariff price:', error);
                alert('خطا در دریافت قیمت تعرفه.');
            });
        }

        // Function to add calculated print item to the cart
        function addPrintToCart() {
            const fileName = document.getElementById('fileName').value;
            const printType = document.getElementById('printType').value;
            const paperSize = document.getElementById('paperSize').value;
            const sides = document.getElementById('sides').value;
            const pagesPerSide = document.getElementById('pagesPerSide').value;
            const totalPages = parseInt(document.getElementById('totalPages').value);
            const basePrice = parseFloat(document.getElementById('basePricePerSheet').value) || 0;
            const totalSheets = parseFloat(document.getElementById('totalSheets').value) || 0;
            const wire1Qty = parseInt(document.getElementById('wireSize1Qty').value) || 0;
            const wire2Qty = parseInt(document.getElementById('wireSize2Qty').value) || 0;
            const wire3Qty = parseInt(document.getElementById('wireSize3Qty').value) || 0;
            const wire4Qty = parseInt(document.getElementById('wireSize4Qty').value) || 0;
            const combQty = parseInt(document.getElementById('combQty').value) || 0;
            const deviderQty = parseInt(document.getElementById('deviderQty').value) || 0;
            const takrooQty = parseInt(document.getElementById('takrooQty').value) || 0;
            const takrooPrice = parseFloat(document.getElementById('takrooPrice').value) || 0;
            const note = document.getElementById('printNote').value;
            const quantity = parseInt(document.getElementById('printQuantity').value) || 1;

            if (!fileName || !printType || !paperSize || !sides || !pagesPerSide || !totalPages || basePrice <= 0) {
                alert('لطفاً تمام فیلدهای مربوط به چاپ را به درستی پر کنید و محاسبه را انجام دهید.');
                return;
            }

            // Calculate total price for this print item
            let itemTotalPrice = (totalSheets * basePrice) +
                                 (wire1Qty * 0) + (wire2Qty * 0) + (wire3Qty * 0) + (wire4Qty * 0) + // Assuming base prices for wire/comb are fetched separately or are fixed
                                 (combQty * 0) +
                                 (deviderQty * 0) +
                                 (takrooQty * takrooPrice); // Only add takroo cost if applicable

            itemTotalPrice *= quantity; // Multiply by quantity of this print item

            // Create summary string
            let summary = `نوع چاپ: ${printType === 'black_white' ? 'سیاه و سفید' : 'رنگی'}\n`;
            summary += `سایز کاغذ: ${paperSize}\n`;
            summary += `وجه چاپ: ${sides == '1' ? 'تک رو' : 'دو رو'}\n`;
            summary += `تعداد ص در هر رو: ${pagesPerSide}\n`;
            summary += `تعداد صفحه: ${totalPages}\n`;
            if (wire1Qty > 0) summary += `سیم سایز 1 (${wire1Qty})\n`;
            if (wire2Qty > 0) summary += `سیم سایز 2 (${wire2Qty})\n`;
            if (wire3Qty > 0) summary += `سیم سایز 3 (${wire3Qty})\n`;
            if (wire4Qty > 0) summary += `سیم سایز 4 (${wire4Qty})\n`;
            if (combQty > 0) summary += `منگنه (${combQty})\n`;
            if (deviderQty > 0) summary += `دیوایدر (${deviderQty})\n`;
            if (takrooQty > 0) summary += `تک رو/رنگی (${takrooQty})\n`;
            if (note) summary += `توضیحات: ${note}\n`;

            // Add to cart array
            cart.push({
                id: 'print_' + Date.now(), // Unique ID for print items
                name: 'چاپ و تکثیر: ' + fileName,
                type: 'print',
                summary: summary.trim(),
                quantity: quantity,
                unit_price: itemTotalPrice / quantity, // Store unit price
                total_price: itemTotalPrice
            });

            recalculateTotals();
            updateCartDisplay();
            updateSessionCart(); // Update session

            // Reset calculator fields (optional)
            document.getElementById('fileName').value = '';
            document.getElementById('totalPages').value = '';
            document.getElementById('basePricePerSheet').value = '';
            document.getElementById('totalSheets').value = '';
            document.getElementById('printNote').value = '';
            document.getElementById('printQuantity').value = '1';
            // Reset binding checkboxes and inputs
            document.getElementById('wireSize1').checked = false;
            document.getElementById('wireSize2').checked = false;
            document.getElementById('wireSize3').checked = false;
            document.getElementById('wireSize4').checked = false;
            document.getElementById('comb').checked = false;
            document.getElementById('devider').checked = false;
            document.getElementById('takroo').checked = false;
            toggleBindingInput('wireSize1Qty');
            toggleBindingInput('wireSize2Qty');
            toggleBindingInput('wireSize3Qty');
            toggleBindingInput('wireSize4Qty');
            toggleBindingInput('combQty');
            toggleBindingInput('deviderQty');
            toggleBindingInput('takrooQty');
            document.getElementById('takrooPrice').value = '';
            document.getElementById('printSummary').style.display = 'none';
        }

        // --- Product Search Logic ---
        function searchProducts() {
            const searchTerm = document.getElementById('productSearch').value;
            if (searchTerm.length < 2) {
                document.getElementById('productSearchResults').innerHTML = '';
                return;
            }

            fetch('../api/search_products.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ search: searchTerm })
            })
            .then(response => response.json())
            .then(data => {
                const resultsDiv = document.getElementById('productSearchResults');
                if (data.success && data.products.length > 0) {
                    resultsDiv.innerHTML = '';
                    data.products.forEach(product => {
                        if (product.product_category !== 2) { // Assuming category ID 2 is 'چاپ و تکثیر'
                            const div = document.createElement('div');
                            div.textContent = `${product.product_name} - ${product.product_price.toLocaleString()} تومان`;
                            div.style.padding = '0.5rem';
                            div.style.borderBottom = '1px solid #eee';
                            div.style.cursor = 'pointer';
                            div.onclick = () => addToCartFromSearch(product);
                            resultsDiv.appendChild(div);
                        }
                    });
                } else {
                    resultsDiv.innerHTML = '<p>محصولی یافت نشد.</p>';
                }
            })
            .catch(error => {
                console.error('Error searching products:', error);
                document.getElementById('productSearchResults').innerHTML = '<p>خطا در جستجو.</p>';
            });
        }

        // Function to add a product from search results to the cart
        function addToCartFromSearch(product) {
            const existingIndex = cart.findIndex(item => item.id === product.product_id && item.type === 'product');
            if (existingIndex > -1) {
                cart[existingIndex].quantity += 1;
                cart[existingIndex].total_price = cart[existingIndex].unit_price * cart[existingIndex].quantity;
            } else {
                cart.push({
                    id: product.product_id,
                    name: product.product_name,
                    type: 'product', // Distinguish from 'print'
                    summary: '', // Standard products don't have a summary like prints
                    quantity: 1,
                    unit_price: parseFloat(product.product_price),
                    total_price: parseFloat(product.product_price)
                });
            }
            recalculateTotals();
            updateCartDisplay();
            updateSessionCart();
            // Clear search box after adding
            document.getElementById('productSearch').value = '';
            document.getElementById('productSearchResults').innerHTML = '';
        }

        // --- Shipping and Discount Logic ---
        function updateShippingCost() {
            const methodSelect = document.getElementById('shippingMethod');
            const input = document.getElementById('shippingCostInput');
            const selectedOption = methodSelect.options[methodSelect.selectedIndex];
            const defaultPrice = selectedOption ? parseFloat(selectedOption.getAttribute('data-price')) : 0;

            if (methodSelect.value) {
                input.disabled = false;
                if (input.value === '' || parseFloat(input.value) === 0) {
                    input.value = defaultPrice; // Set default if empty or zero
                }
                shippingCost = parseFloat(input.value) || 0;
            } else {
                input.disabled = true;
                shippingCost = 0;
                input.value = 0;
            }
            shippingMethodId = methodSelect.value; // Update session var
            recalculateTotals();
            updateCartDisplay();
            updateSessionCart();
        }

        function applyDiscountCode() {
            const codeInput = document.getElementById('discountCode');
            const code = codeInput.value.trim();

            if (!code) {
                discountCode = null;
                discountAmount = 0;
                recalculateTotals();
                updateCartDisplay();
                updateSessionCart();
                return;
            }

            fetch('../api/apply_discount.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ code: code })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    discountCode = code;
                    discountAmount = parseFloat(data.discount_amount) || 0;
                    recalculateTotals();
                    updateCartDisplay();
                    updateSessionCart();
                    alert('کد تخفیف با موفقیت اعمال شد.');
                } else {
                    alert(data.message || 'کد تخفیف نامعتبر است یا قابل استفاده نیست.');
                    codeInput.value = discountCode || ''; // Revert input to last valid code or empty
                }
            })
            .catch(error => {
                console.error('Error applying discount:', error);
                alert('خطا در بررسی کد تخفیف.');
            });
        }

        // --- Pre-Order Submission Logic ---
        function submitPreorder() {
            if (cart.length === 0) {
                alert('سبد خرید نمی‌تواند خالی باشد.');
                return;
            }

            const notes = document.getElementById('preorderNotes').value;
            preorderNotes = notes; // Update session var

            // Prepare data to send
            const dataToSend = {
                cart: cart,
                shipping_method_id: shippingMethodId,
                shipping_cost: shippingCost,
                discount_code: discountCode,
                discount_amount: discountAmount,
                notes: notes,
                existing_preorder_id: currentPreorderId // Send if editing
            };

            fetch('../api/submit_preorder.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(dataToSend)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert(data.message);
                    // Update session with new/updated preorder ID and clear cart
                    currentPreorderId = data.preorder_id;
                    document.getElementById('preorderNumberDisplay').textContent = `شماره پیش سفارش: ${data.preorder_id}`;
                    document.getElementById('preorderNumberDisplay').style.display = 'block';
                    // Update confirm button state
                    document.querySelector('.confirm-btn').disabled = false;
                    // Clear session cart and reset UI
                    cart = [];
                    recalculateTotals();
                    updateCartDisplay();
                    updateSessionCart(); // This should clear the session cart server-side
                    // Show details popup (simplified here, you'd create a proper modal)
                    const detailsText = data.details_text; // This comes from the PHP response
                    const textArea = document.createElement('textarea');
                    textArea.value = detailsText;
                    textArea.style.width = '100%';
                    textArea.style.height = '300px';
                    textArea.style.fontFamily = 'monospace';
                    textArea.style.whiteSpace = 'pre';
                    textArea.style.overflow = 'auto';
                    const popup = document.createElement('div');
                    popup.style.position = 'fixed';
                    popup.style.top = '50%';
                    popup.style.left = '50%';
                    popup.style.transform = 'translate(-50%, -50%)';
                    popup.style.backgroundColor = 'white';
                    popup.style.padding = '20px';
                    popup.style.border = '1px solid #ccc';
                    popup.style.zIndex = '1000';
                    popup.appendChild(textArea);
                    const copyBtn = document.createElement('button');
                    copyBtn.textContent = 'کپی';
                    copyBtn.onclick = () => navigator.clipboard.writeText(detailsText);
                    const closeBtn = document.createElement('button');
                    closeBtn.textContent = 'بستن';
                    closeBtn.onclick = () => document.body.removeChild(popup);
                    const buttonDiv = document.createElement('div');
                    buttonDiv.style.marginTop = '10px';
                    buttonDiv.appendChild(copyBtn);
                    buttonDiv.appendChild(closeBtn);
                    popup.appendChild(buttonDiv);
                    document.body.appendChild(popup);
                    // Clear local variables that might hold old data
                    shippingMethodId = null;
                    shippingCost = 0;
                    discountCode = null;
                    discountAmount = 0;
                    preorderNotes = '';
                } else {
                    alert(data.message || 'خطا در ثبت پیش سفارش.');
                }
            })
            .catch(error => {
                console.error('Error submitting preorder:', error);
                alert('خطا در ارتباط با سرور.');
            });
        }

        // --- Pre-Order Confirmation Logic (will be linked from confirm page) ---
        function confirmPreorder() {
            if (!currentPreorderId) {
                alert('ابتدا یک پیش سفارش را جستجو یا ثبت کنید.');
                return;
            }
            // Redirect to the confirmation page, passing the ID
            window.location.href = 'preorder_confirm.php?id=' + encodeURIComponent(currentPreorderId);
        }

        // --- Search Existing Pre-Order Logic ---
        function searchPreorder() {
            const searchInput = document.getElementById('preorderSearch').value;
            if (!searchInput) {
                alert('لطفاً شماره پیش سفارش را وارد کنید.');
                return;
            }

            fetch('../api/search_preorder.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ id: searchInput })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success && data.preorder) {
                    // Load the preorder data into the session/cart
                    currentPreorderId = data.preorder.id;
                    document.getElementById('preorderNumberDisplay').textContent = `شماره پیش سفارش: ${data.preorder.id}`;
                    document.getElementById('preorderNumberDisplay').style.display = 'block';
                    // Assuming data.preorder.items contains the items
                    cart = data.preorder.items || [];
                    shippingMethodId = data.preorder.shipping_method_id;
                    shippingCost = data.preorder.shipping_cost;
                    discountCode = data.preorder.discount_code;
                    discountAmount = data.preorder.discount_amount;
                    preorderNotes = data.preorder.notes;

                    // Update UI based on loaded data
                    recalculateTotals();
                    updateCartDisplay();
                    updateSessionCart(); // Sync loaded data to session
                    document.getElementById('preorderNotes').value = preorderNotes;
                    document.getElementById('discountCode').value = discountCode || '';
                    document.getElementById('shippingMethod').value = shippingMethodId || '';
                    updateShippingCost(); // Trigger update for input field

                    // Enable confirm button
                    document.querySelector('.confirm-btn').disabled = false;
                    // Clear search box
                    document.getElementById('preorderSearch').value = '';

                } else {
                    alert(data.message || 'پیش سفارش یافت نشد.');
                }
            })
            .catch(error => {
                console.error('Error searching preorder:', error);
                alert('خطا در ارتباط با سرور.');
            });
        }

        // --- New Pre-Order Logic ---
        function newPreorder() {
            // Clear current session data
            currentPreorderId = null;
            cart = [];
            shippingMethodId = null;
            shippingCost = 0;
            discountCode = null;
            discountAmount = 0;
            preorderNotes = '';
            recalculateTotals();
            updateCartDisplay();
            updateSessionCart(); // Clear session cart
            document.getElementById('preorderSearch').value = '';
            document.getElementById('preorderNotes').value = '';
            document.getElementById('discountCode').value = '';
            document.getElementById('shippingMethod').value = '';
            document.getElementById('shippingCostInput').value = '0';
            document.getElementById('shippingCostInput').disabled = true;
            document.getElementById('preorderNumberDisplay').style.display = 'none';
            document.querySelector('.confirm-btn').disabled = true; // Disable confirm for new
        }

        // Initialize cart display on page load
        updateCartDisplay();

    </script>

</body>
</html>
