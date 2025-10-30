<?php
// pages/preorder_confirm.php - Confirm and finalize a pre-order

require_once '../includes/session.php';
require_once '../includes/db.php';

if (!isLoggedIn()) {
    header("Location: login.php");
    exit;
}

// Initialize session variable to hold the preorder ID being confirmed
if (!isset($_SESSION['confirming_preorder_id'])) {
    $_SESSION['confirming_preorder_id'] = null;
}

$confirming_preorder_id = $_SESSION['confirming_preorder_id'];
?>

<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تایید نهایی پیش سفارش - چاپسون</title>
    <style>
        /* Reuse styles from preorder_create.php or define specific ones */
        body {
            font-family: Tahoma, Arial, sans-serif;
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
            max-width: 800px;
            margin: 1rem auto;
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
            width: calc(100% - 110px);
            padding: 0.5rem;
            margin-bottom: 1rem;
        }
        .search-btn {
            padding: 0.5rem 1rem;
            margin-bottom: 1rem;
            margin-left: 0.5rem;
        }
        .confirmation-popup {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background-color: white;
            padding: 2rem;
            border: 1px solid #ccc;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
            z-index: 1000;
            display: none; /* Hidden by default */
        }
        .popup-content {
            margin-bottom: 1rem;
        }
        .popup-buttons {
            text-align: center;
        }
        .popup-btn {
            padding: 0.5rem 1rem;
            margin: 0 0.5rem;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        .popup-btn-confirm {
            background-color: #28a745;
            color: white;
        }
        .popup-btn-cancel {
            background-color: #6c757d;
            color: white;
        }
        .customer-details input, .customer-details select {
            width: 100%;
            padding: 0.5rem;
            margin-bottom: 0.5rem;
            box-sizing: border-box;
        }
        .error-message {
            color: red;
            margin-top: 0.5rem;
        }
        .success-message {
            color: green;
            margin-top: 0.5rem;
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
        <h2 class="section-title">تایید نهایی پیش سفارش</h2>
        <input type="text" id="confirmPreorderSearch" class="search-box" placeholder="شماره پیش سفارش را وارد کنید...">
        <button class="search-btn" onclick="searchPreorderForConfirmation()">جستجو</button>

        <div id="preorderDetails" style="display:none;">
            <h3>جزئیات پیش سفارش #<span id="preorderIdDisplay"></span></h3>
            <div id="itemsList"></div>
            <div id="totalAmountDisplay"></div>
        </div>

        <div id="customerDetailsSection" class="customer-details" style="display:none; margin-top: 1rem;">
            <h3>اطلاعات مشتری و پرداخت</h3>
            <input type="text" id="customerPhone" placeholder="شماره موبایل مشتری">
            <button onclick="searchCustomerByPhone()">جستجو</button>
            <div id="customerSearchError" class="error-message" style="display:none;"></div>

            <input type="text" id="customerFirstName" placeholder="نام" disabled>
            <input type="text" id="customerLastName" placeholder="نام خانوادگی" disabled>
            <input type="email" id="customerEmail" placeholder="ایمیل" disabled>
            <textarea id="customerAddress" placeholder="آدرس" rows="3" disabled></textarea>
            <input type="text" id="customerPostalCode" placeholder="کد پستی" disabled>

            <label for="paymentMethod">واریز به:</label>
            <select id="paymentMethod">
                <option value="">انتخاب کنید...</option>
                <?php
                $stmt = $pdo->query("SELECT id, name, label FROM payment_methods WHERE is_active = 1");
                while ($row = $stmt->fetch()) {
                    echo "<option value='{$row['id']}'>{$row['name']} ({$row['label']})</option>";
                }
                ?>
            </select>

            <label for="depositAmount">مقدار واریزی (تومان):</label>
            <input type="number" id="depositAmount" onchange="calculateRemaining()">
            <div id="remainingAmountDisplay" style="color: red;"></div>

            <button class="submit-btn" onclick="finalizePreorder()" style="margin-top: 1rem;">ثبت سفارش نهایی</button>
            <div id="finalizationMessage" class="success-message" style="display:none;"></div>
        </div>
    </main>

    <div id="confirmationPopup" class="confirmation-popup">
        <div class="popup-content">
            <h3>پردازش اطلاعات (غیرفعال)</h3>
            <p>این بخش در حال حاضر غیرفعال است.</p>
            <!-- Checkbox for AI processing (currently disabled) -->
            <input type="checkbox" id="aiProcessCheckbox" disabled>
            <label for="aiProcessCheckbox">پردازش با هوش مصنوعی</label>
            <br><br>

            <div id="customerDetailsForm">
                <label for="popupCustomerPhone">شماره موبایل:</label>
                <input type="text" id="popupCustomerPhone" placeholder="شماره موبایل مشتری">
                <button onclick="searchCustomerInPopup()">جستجو</button>
                <div id="popupCustomerSearchError" class="error-message" style="display:none;"></div>

                <label for="popupCustomerFirstName">نام:</label>
                <input type="text" id="popupCustomerFirstName" disabled>

                <label for="popupCustomerLastName">نام خانوادگی:</label>
                <input type="text" id="popupCustomerLastName" disabled>

                <label for="popupCustomerEmail">ایمیل:</label>
                <input type="email" id="popupCustomerEmail" disabled>

                <label for="popupCustomerAddress">آدرس:</label>
                <textarea id="popupCustomerAddress" rows="3" disabled></textarea>

                <label for="popupCustomerPostalCode">کد پستی:</label>
                <input type="text" id="popupCustomerPostalCode" disabled>

                <label for="popupPaymentMethod">واریز به:</label>
                <select id="popupPaymentMethod">
                    <option value="">انتخاب کنید...</option>
                    <?php
                    // Re-run the query for the popup
                    $stmt = $pdo->query("SELECT id, name, label FROM payment_methods WHERE is_active = 1");
                    while ($row = $stmt->fetch()) {
                        echo "<option value='{$row['id']}'>{$row['name']} ({$row['label']})</option>";
                    }
                    ?>
                </select>

                <label for="popupDepositAmount">مقدار واریزی (تومان):</label>
                <input type="number" id="popupDepositAmount" onchange="calculateRemainingInPopup()">

                <div id="popupRemainingAmountDisplay" style="color: red;"></div>
            </div>
        </div>
        <div class="popup-buttons">
            <button class="popup-btn popup-btn-confirm" onclick="confirmFinalization()">ثبت</button>
            <button class="popup-btn popup-btn-cancel" onclick="closeConfirmationPopup()">بستن</button>
        </div>
    </div>

    <script>
        let confirmingPreorderId = <?php echo $confirming_preorder_id ? json_encode($confirming_preorder_id) : 'null'; ?>;
        let preorderDetails = null; // Store details fetched from search
        let totalAmount = 0;

        function searchPreorderForConfirmation() {
            const searchInput = document.getElementById('confirmPreorderSearch').value;
            if (!searchInput) {
                alert('لطفاً شماره پیش سفارش را وارد کنید.');
                return;
            }

            fetch('../api/search_preorder.php', { // Reuse the same search API
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ id: searchInput })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success && data.preorder) {
                    confirmingPreorderId = data.preorder.id;
                    preorderDetails = data.preorder;
                    totalAmount = data.preorder.final_amount; // Use final amount from preorder

                    document.getElementById('preorderIdDisplay').textContent = confirmingPreorderId;
                    document.getElementById('preorderDetails').style.display = 'block';

                    // Display items (simplified)
                    const itemsListDiv = document.getElementById('itemsList');
                    itemsListDiv.innerHTML = '<h4>موارد سفارش:</h4>';
                    data.preorder.items.forEach(item => {
                        const p = document.createElement('p');
                        p.textContent = `${item.name} (تعداد: ${item.quantity}, قیمت کل: ${item.total_price.toLocaleString()} تومان)`;
                        itemsListDiv.appendChild(p);
                    });

                    document.getElementById('totalAmountDisplay').innerHTML = `<strong>مبلغ کل سفارش: ${totalAmount.toLocaleString()} تومان</strong>`;
                    document.getElementById('customerDetailsSection').style.display = 'block';
                    document.getElementById('depositAmount').value = totalAmount; // Default to full amount
                    calculateRemaining(); // Calculate initial remaining

                    // Clear search box
                    document.getElementById('confirmPreorderSearch').value = '';

                } else {
                    alert(data.message || 'پیش سفارش یافت نشد.');
                }
            })
            .catch(error => {
                console.error('Error searching preorder for confirmation:', error);
                alert('خطا در ارتباط با سرور.');
            });
        }

        function searchCustomerByPhone() {
            const phone = document.getElementById('customerPhone').value;
            if (!phone) {
                document.getElementById('customerSearchError').textContent = 'لطفاً شماره موبایل را وارد کنید.';
                document.getElementById('customerSearchError').style.display = 'block';
                return;
            }

            fetch('../api/search_customer.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ phone: phone })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success && data.customer) {
                    document.getElementById('customerFirstName').value = data.customer.c_first_name || '';
                    document.getElementById('customerLastName').value = data.customer.c_last_name || '';
                    document.getElementById('customerEmail').value = data.customer.customer_email || '';
                    document.getElementById('customerAddress').value = data.customer.shipping_address || ''; // Assuming shipping addr is used
                    document.getElementById('customerPostalCode').value = data.customer.postal_code || ''; // Assuming postal code field exists or use from addr
                    document.getElementById('customerSearchError').style.display = 'none';
                    // Set default payment method if available
                    if (data.customer.default_payment_method_id) {
                         document.getElementById('paymentMethod').value = data.customer.default_payment_method_id;
                    }
                } else {
                    document.getElementById('customerFirstName').value = '';
                    document.getElementById('customerLastName').value = '';
                    document.getElementById('customerEmail').value = '';
                    document.getElementById('customerAddress').value = '';
                    document.getElementById('customerPostalCode').value = '';
                    document.getElementById('customerSearchError').textContent = data.message || 'مشتری یافت نشد.';
                    document.getElementById('customerSearchError').style.display = 'block';
                }
            })
            .catch(error => {
                console.error('Error searching customer:', error);
                document.getElementById('customerSearchError').textContent = 'خطا در ارتباط با سرور.';
                document.getElementById('customerSearchError').style.display = 'block';
            });
        }

        function calculateRemaining() {
            const deposit = parseFloat(document.getElementById('depositAmount').value) || 0;
            const remaining = totalAmount - deposit;
            document.getElementById('remainingAmountDisplay').textContent = `مانده: ${remaining.toLocaleString()} تومان`;
        }

        function finalizePreorder() {
            if (!confirmingPreorderId || !preorderDetails) {
                alert('ابتدا یک پیش سفارش را جستجو کنید.');
                return;
            }

            const customerPhone = document.getElementById('customerPhone').value;
            const customerFirstName = document.getElementById('customerFirstName').value;
            const customerLastName = document.getElementById('customerLastName').value;
            const customerEmail = document.getElementById('customerEmail').value;
            const customerAddress = document.getElementById('customerAddress').value;
            const customerPostalCode = document.getElementById('customerPostalCode').value;
            const paymentMethodId = document.getElementById('paymentMethod').value;
            const depositAmount = parseFloat(document.getElementById('depositAmount').value) || 0;

            if (!customerPhone || !customerFirstName || !customerLastName || !customerAddress || !paymentMethodId) {
                alert('لطفاً تمام فیلدهای الزامی را پر کنید.');
                return;
            }

            const remaining = totalAmount - depositAmount;
            const isCompletelyPaid = remaining <= 0 ? 'yes' : 'no';

            const dataToSend = {
                preorder_id: confirmingPreorderId,
                customer_phone: customerPhone,
                customer_first_name: customerFirstName,
                customer_last_name: customerLastName,
                customer_email: customerEmail,
                customer_address: customerAddress,
                customer_postal_code: customerPostalCode,
                payment_method_id: paymentMethodId,
                deposit_amount: depositAmount,
                remaining_amount: remaining,
                is_completely_paid: isCompletelyPaid,
                total_amount: totalAmount
            };

            fetch('../api/finalize_preorder.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(dataToSend)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    document.getElementById('finalizationMessage').textContent = data.message;
                    document.getElementById('finalizationMessage').style.display = 'block';
                    // Optionally clear the form or redirect
                    document.getElementById('confirmPreorderSearch').value = '';
                    document.getElementById('preorderDetails').style.display = 'none';
                    document.getElementById('customerDetailsSection').style.display = 'none';
                    confirmingPreorderId = null;
                    preorderDetails = null;
                } else {
                    alert(data.message || 'خطا در تایید نهایی پیش سفارش.');
                }
            })
            .catch(error => {
                console.error('Error finalizing preorder:', error);
                alert('خطا در ارتباط با سرور.');
            });
        }

        // --- Popup Logic (Currently Disabled as per requirements) ---
        function openConfirmationPopup() {
            // This function is linked to the button in the main form,
            // but the button itself is not present in the current HTML as the requirement was to have a single "ثبت سفارش نهایی" button.
            // The popup logic is kept here for potential future use or if the design changes.
            document.getElementById('confirmationPopup').style.display = 'block';
            // Populate popup fields with main form data initially if needed
            document.getElementById('popupCustomerPhone').value = document.getElementById('customerPhone').value;
            // ... other fields if needed initially ...
        }

        function closeConfirmationPopup() {
            document.getElementById('confirmationPopup').style.display = 'none';
        }

        function searchCustomerInPopup() {
            // Implement similar to searchCustomerByPhone but for the popup fields
            const phone = document.getElementById('popupCustomerPhone').value;
            // ... (similar fetch logic as searchCustomerByPhone, updating popup fields) ...
            // For now, let's just alert as it's complex to duplicate
             alert('جستجوی مشتری در پاپ‌آپ نیاز به پیاده‌سازی بیشتر دارد.');
        }

        function calculateRemainingInPopup() {
            // Implement similar to calculateRemaining but for the popup fields
            const deposit = parseFloat(document.getElementById('popupDepositAmount').value) || 0;
            const remaining = totalAmount - deposit;
            document.getElementById('popupRemainingAmountDisplay').textContent = `مانده: ${remaining.toLocaleString()} تومان`;
        }

        function confirmFinalization() {
            // Implement the finalization logic triggered by the popup confirm button
            // This would essentially be the same as finalizePreorder() but using data from the popup form
            // For now, let's just close the popup as the main form handles finalization
             closeConfirmationPopup();
             alert('پاپ‌آپ تایید غیرفعال است. از فرم اصلی استفاده کنید.');
        }

    </script>

</body>
</html>
