<?php
// dashboard.php - Main Dashboard Page

// Include session management to check login status
require_once 'includes/session.php';

// Check if user is logged in, redirect to login if not
if (!isLoggedIn()) {
    header("Location: pages/login.php");
    exit;
}

// Include database connection if needed for dashboard stats later
// require_once 'includes/db.php';

$user_full_name = getUserFullName();
$user_role = getUserRole();

?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>داشبورد - چاپسون</title>
    <style>
        body {
            font-family: Tahoma, Arial, sans-serif; /* Use default theme font */
            margin: 0;
            padding: 0;
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
        .center-text {
            text-align: center;
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
            padding: 2rem;
        }
        .welcome-box {
            background-color: white;
            padding: 1.5rem;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            margin-bottom: 1.5rem;
        }
        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }
        .dashboard-box {
            background-color: white;
            padding: 1rem;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            text-align: center;
        }
        .quick-actions {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1rem;
            margin-top: 2rem;
        }
        .action-btn {
            background-color: #007bff;
            color: white;
            padding: 1rem;
            text-decoration: none;
            border-radius: 8px;
            text-align: center;
            display: block;
        }
        .action-btn:hover {
            background-color: #0056b3;
        }
    </style>
</head>
<body>

    <header class="header">
        <div class="user-info">
            <span class="user-name"><?php echo $user_full_name; ?></span>
            <span class="user-role"><?php echo $user_role; ?></span>
        </div>
        <div class="center-text">
            <h1>چاپسون</h1>
        </div>
        <div>
            <a href="includes/logout.php" class="logout-btn">خروج</a>
        </div>
    </header>

    <main class="main-content">
        <div class="welcome-box">
            <h2>به سیستم مدیریت چاپسون خوش آمدید</h2>
            <p>تاریخ و زمان فعلی: <span id="currentDateTime"></span></p>
        </div>

        <div class="dashboard-grid">
            <div class="dashboard-box">
                <h3>سفارشات تایید شده</h3>
                <p id="confirmed-orders-count">0</p>
            </div>
            <div class="dashboard-box">
                <h3>سفارشات نسیه</h3>
                <p id="credit-orders-count">0</p>
            </div>
            <div class="dashboard-box">
                <h3>مانده کل نسیه</h3>
                <p id="total-credit-remaining">0</p>
            </div>
            <div class="dashboard-box">
                <h3>فروش امروز</h3>
                <p id="today-sales">0</p>
            </div>
        </div>

        <div class="quick-actions">
            <a href="pages/preorder_create.php" class="action-btn">ثبت پیش سفارش جدید</a>
            <a href="pages/preorder_confirm.php" class="action-btn">تایید نهایی پیش سفارش</a>
            <a href="pages/orders.php" class="action-btn">پردازش گروهی سفارشات</a>
            <!-- Add more action buttons as needed -->
        </div>

    </main>

    <script>
        // Function to update date and time every second
        function updateDateTime() {
            const now = new Date();
            // Format as needed, e.g., "1404/08/10 - 14:30:25"
            // Using a simple format for now
            document.getElementById('currentDateTime').textContent = now.toLocaleString('fa-IR');
        }

        // Update time immediately and then every second
        updateDateTime();
        setInterval(updateDateTime, 1000);

        // Placeholder for fetching dashboard stats (will be implemented with AJAX later)
        // Example:
        // fetch('api/get_dashboard_stats.php')
        //     .then(response => response.json())
        //     .then(data => {
        //         document.getElementById('confirmed-orders-count').textContent = data.confirmed_count;
        //         document.getElementById('credit-orders-count').textContent = data.credit_count;
        //         document.getElementById('total-credit-remaining').textContent = data.total_remaining;
        //         document.getElementById('today-sales').textContent = data.today_sales;
        //     })
        //     .catch(error => console.error('Error fetching stats:', error));

    </script>

</body>
</html>
