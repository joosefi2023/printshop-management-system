<?php
// pages/define_tariff.php - Define Print Tariffs

require_once '../includes/session.php';
require_once '../includes/db.php';

if (!isLoggedIn() || !isAdmin()) { // Ensure only admin can access
    header("Location: ../pages/login.php");
    exit;
}

$message = '';
$tariffs = [];

// Handle form submission for adding/editing
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id = $_POST['tariff_id'] ?? null; // Used for editing
    $print_type = $_POST['print_type'] ?? '';
    $paper_size = $_POST['paper_size'] ?? '';
    $sides = $_POST['sides'] ?? '';
    $pages_per_side = $_POST['pages_per_side'] ?? '';
    $base_price = $_POST['base_price_per_sheet'] ?? 0;
    $is_active = isset($_POST['is_active']) ? 1 : 0;

    if ($id) {
        // Update existing tariff
        try {
            $stmt = $pdo->prepare("
                UPDATE tariffs
                SET print_type = :print_type,
                    paper_size = :paper_size,
                    sides = :sides,
                    pages_per_side = :pages_per_side,
                    base_price_per_sheet = :base_price,
                    is_active = :is_active,
                    updated_at = NOW()
                WHERE id = :id
            ");
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->bindParam(':print_type', $print_type, PDO::PARAM_STR);
            $stmt->bindParam(':paper_size', $paper_size, PDO::PARAM_STR);
            $stmt->bindParam(':sides', $sides, PDO::PARAM_STR);
            $stmt->bindParam(':pages_per_side', $pages_per_side, PDO::PARAM_STR);
            $stmt->bindParam(':base_price', $base_price, PDO::PARAM_STR); // Use STR for decimals
            $stmt->bindParam(':is_active', $is_active, PDO::PARAM_INT);
            $stmt->execute();
            $message = "تعرفه با موفقیت ویرایش شد.";
        } catch (PDOException $e) {
            error_log("Tariff Update Error: " . $e->getMessage());
            $message = "خطا در ویرایش تعرفه.";
        }
    } else {
        // Insert new tariff
        try {
            $stmt = $pdo->prepare("
                INSERT INTO tariffs (print_type, paper_size, sides, pages_per_side, base_price_per_sheet, is_active)
                VALUES (:print_type, :paper_size, :sides, :pages_per_side, :base_price, :is_active)
            ");
            $stmt->bindParam(':print_type', $print_type, PDO::PARAM_STR);
            $stmt->bindParam(':paper_size', $paper_size, PDO::PARAM_STR);
            $stmt->bindParam(':sides', $sides, PDO::PARAM_STR);
            $stmt->bindParam(':pages_per_side', $pages_per_side, PDO::PARAM_STR);
            $stmt->bindParam(':base_price', $base_price, PDO::PARAM_STR);
            $stmt->bindParam(':is_active', $is_active, PDO::PARAM_INT);
            $stmt->execute();
            $message = "تعرفه جدید با موفقیت اضافه شد.";
        } catch (PDOException $e) {
            error_log("Tariff Insert Error: " . $e->getMessage());
            $message = "خطا در افزودن تعرفه جدید.";
        }
    }
}

// Handle deletion
if (isset($_GET['delete_id'])) {
    $delete_id = (int) $_GET['delete_id'];
    try {
        $stmt = $pdo->prepare("DELETE FROM tariffs WHERE id = :id");
        $stmt->bindParam(':id', $delete_id, PDO::PARAM_INT);
        $stmt->execute();
        $message = "تعرفه با موفقیت حذف شد.";
    } catch (PDOException $e) {
        error_log("Tariff Delete Error: " . $e->getMessage());
        $message = "خطا در حذف تعرفه.";
    }
}

// Fetch all tariffs for display
try {
    $stmt = $pdo->query("SELECT * FROM tariffs ORDER BY print_type, paper_size, sides, pages_per_side");
    $tariffs = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Tariff Fetch Error: " . $e->getMessage());
    $message = "خطا در بارگذاری تعرفه‌ها.";
}

// Fetch tariff for editing (if requested)
$editing_tariff = null;
if (isset($_GET['edit_id'])) {
    $edit_id = (int) $_GET['edit_id'];
    try {
        $stmt = $pdo->prepare("SELECT * FROM tariffs WHERE id = :id LIMIT 1");
        $stmt->bindParam(':id', $edit_id, PDO::PARAM_INT);
        $stmt->execute();
        $editing_tariff = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Tariff Fetch for Edit Error: " . $e->getMessage());
        $message = "خطا در بارگذاری تعرفه برای ویرایش.";
    }
}
?>

<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تعریف تعرفه چاپ - چاپسون</title>
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
            max-width: 1000px;
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
        .form-group {
            margin-bottom: 1rem;
        }
        label {
            display: block;
            margin-bottom: 0.5rem;
            color: #555;
        }
        input[type="text"], input[type="number"], select {
            width: 100%;
            padding: 0.5rem;
            border: 1px solid #ccc;
            border-radius: 4px;
            box-sizing: border-box;
        }
        input[type="checkbox"] {
            margin-right: 0.5rem;
        }
        .form-actions {
            display: flex;
            gap: 1rem;
            margin-top: 1rem;
        }
        button {
            padding: 0.5rem 1rem;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 1rem;
        }
        .btn-submit {
            background-color: #007bff;
            color: white;
        }
        .btn-submit:hover {
            background-color: #0056b3;
        }
        .btn-cancel {
            background-color: #6c757d;
            color: white;
        }
        .btn-cancel:hover {
            background-color: #5a6268;
        }
        .message {
            padding: 0.75rem;
            margin-bottom: 1rem;
            border-radius: 4px;
        }
        .message.success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .message.error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1rem;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 0.75rem;
            text-align: center;
        }
        th {
            background-color: #f2f2f2;
        }
        .action-buttons {
            display: flex;
            gap: 0.5rem;
            justify-content: center;
        }
        .btn-edit, .btn-delete {
            padding: 0.25rem 0.5rem;
            font-size: 0.9rem;
        }
        .btn-edit {
            background-color: #ffc107;
            color: black;
        }
        .btn-edit:hover {
            background-color: #e0a800;
        }
        .btn-delete {
            background-color: #dc3545;
            color: white;
        }
        .btn-delete:hover {
            background-color: #c82333;
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
        <h2 class="section-title">تعریف تعرفه چاپ</h2>

        <?php if ($message): ?>
            <div class="message <?php echo strpos($message, 'موفقیت') !== false ? 'success' : 'error'; ?>">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <form method="post" action="">
            <input type="hidden" name="tariff_id" value="<?php echo htmlspecialchars($editing_tariff['id'] ?? ''); ?>">
            <div class="form-group">
                <label for="print_type">نوع چاپ:</label>
                <select name="print_type" id="print_type" required>
                    <option value="black_white" <?php echo (isset($editing_tariff['print_type']) && $editing_tariff['print_type'] == 'black_white') ? 'selected' : ''; ?>>سیاه و سفید</option>
                    <option value="color" <?php echo (isset($editing_tariff['print_type']) && $editing_tariff['print_type'] == 'color') ? 'selected' : ''; ?>>رنگی</option>
                </select>
            </div>
            <div class="form-group">
                <label for="paper_size">سایز کاغذ:</label>
                <select name="paper_size" id="paper_size" required>
                    <option value="A3" <?php echo (isset($editing_tariff['paper_size']) && $editing_tariff['paper_size'] == 'A3') ? 'selected' : ''; ?>>A3</option>
                    <option value="A4" <?php echo (isset($editing_tariff['paper_size']) && $editing_tariff['paper_size'] == 'A4') ? 'selected' : ''; ?>>A4</option>
                    <option value="A5" <?php echo (isset($editing_tariff['paper_size']) && $editing_tariff['paper_size'] == 'A5') ? 'selected' : ''; ?>>A5</option>
                </select>
            </div>
            <div class="form-group">
                <label for="sides">وجه چاپ:</label>
                <select name="sides" id="sides" required>
                    <option value="single" <?php echo (isset($editing_tariff['sides']) && $editing_tariff['sides'] == 'single') ? 'selected' : ''; ?>>تک رو</option>
                    <option value="double" <?php echo (isset($editing_tariff['sides']) && $editing_tariff['sides'] == 'double') ? 'selected' : ''; ?>>دو رو</option>
                </select>
            </div>
            <div class="form-group">
                <label for="pages_per_side">تعداد صفحه در هر روی برگه:</label>
                <select name="pages_per_side" id="pages_per_side" required>
                    <option value="1.00" <?php echo (isset($editing_tariff['pages_per_side']) && $editing_tariff['pages_per_side'] == '1.00') ? 'selected' : ''; ?>>1 (یک)</option>
                    <option value="0.50" <?php echo (isset($editing_tariff['pages_per_side']) && $editing_tariff['pages_per_side'] == '0.50') ? 'selected' : ''; ?>>2 (دو)</option>
                    <option value="0.25" <?php echo (isset($editing_tariff['pages_per_side']) && $editing_tariff['pages_per_side'] == '0.25') ? 'selected' : ''; ?>>4 (چهار)</option>
                </select>
            </div>
            <div class="form-group">
                <label for="base_price_per_sheet">قیمت پایه هر برگ (تومان):</label>
                <input type="number" name="base_price_per_sheet" id="base_price_per_sheet" value="<?php echo htmlspecialchars($editing_tariff['base_price_per_sheet'] ?? ''); ?>" min="0" required>
            </div>
            <div class="form-group">
                <label>
                    <input type="checkbox" name="is_active" value="1" <?php echo (!isset($editing_tariff['is_active']) || $editing_tariff['is_active'] == 1) ? 'checked' : ''; ?>> فعال
                </label>
            </div>
            <div class="form-actions">
                <button type="submit" class="btn-submit"><?php echo isset($editing_tariff) ? 'ویرایش تعرفه' : 'افزودن تعرفه'; ?></button>
                <?php if (isset($editing_tariff)): ?>
                    <a href="?"><button type="button" class="btn-cancel">لغو ویرایش</button></a>
                <?php endif; ?>
            </div>
        </form>

        <h3 class="section-title">لیست تعرفه‌ها</h3>
        <table>
            <thead>
                <tr>
                    <th>شناسه</th>
                    <th>نوع چاپ</th>
                    <th>سایز کاغذ</th>
                    <th>وجه چاپ</th>
                    <th>صفحه در هر رو</th>
                    <th>قیمت پایه (تومان)</th>
                    <th>فعال</th>
                    <th>عملیات</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($tariffs as $tariff): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($tariff['id']); ?></td>
                        <td><?php echo htmlspecialchars($tariff['print_type'] == 'black_white' ? 'سیاه و سفید' : 'رنگی'); ?></td>
                        <td><?php echo htmlspecialchars($tariff['paper_size']); ?></td>
                        <td><?php echo htmlspecialchars($tariff['sides'] == 'single' ? 'تک رو' : 'دو رو'); ?></td>
                        <td><?php echo htmlspecialchars($tariff['pages_per_side']); ?></td>
                        <td><?php echo number_format($tariff['base_price_per_sheet']); ?></td>
                        <td><?php echo $tariff['is_active'] ? 'بله' : 'خیر'; ?></td>
                        <td class="action-buttons">
                            <a href="?edit_id=<?php echo $tariff['id']; ?>"><button class="btn-edit">ویرایش</button></a>
                            <a href="?delete_id=<?php echo $tariff['id']; ?>" onclick="return confirm('آیا از حذف این تعرفه اطمینان دارید؟')"><button class="btn-delete">حذف</button></a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </main>

</body>
</html>
