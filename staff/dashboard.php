<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staff Dashboard - Premium Living Furniture</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
</head>

<body>
    <?php
    // staff/dashboard.php
    // === AUTO EXTEND COOKIES ON ANY ACTIVITY ===
    if (isset($_COOKIE['staff_id'])) {
        $staff_id   = $_COOKIE['staff_id'];
        $staff_name = $_COOKIE['staff_name'] ?? 'Staff';
        $staff_role = $_COOKIE['staff_role'] ?? '';

    } else {
        header("Location: login.php");
        exit();
    } 

    $staff_name = $_COOKIE['staff_name'] ?? 'Staff';
    $is_admin = (isset($_COOKIE['staff_role']) && $_COOKIE['staff_role'] == "Administrator");

    include '../connections/dbconn.php';

    // Quick stats
    $stats = [
        'total_orders' => 0,
        'pending_orders' => 0,
        'total_sales' => 0.00,
        'low_stock_items' => 0,
        'total_customers' => 0,
        'total_staff' => 0
    ];

    $res = mysqli_query($conn, "SELECT COUNT(*) AS cnt FROM Orders");
    if ($row = mysqli_fetch_assoc($res))
        $stats['total_orders'] = $row['cnt'];

    $res = mysqli_query($conn, "SELECT COUNT(*) AS cnt FROM Orders WHERE ostatus IN (1,2)");
    if ($row = mysqli_fetch_assoc($res))
        $stats['pending_orders'] = $row['cnt'];

    $res = mysqli_query($conn, "
    SELECT COALESCE(SUM(ototalamount), 0) AS total 
    FROM Orders 
    WHERE ostatus = 5
");
    if ($row = mysqli_fetch_assoc($res))
        $stats['total_sales'] = $row['total'];

    $res = mysqli_query($conn, "SELECT COUNT(*) AS cnt FROM Materials WHERE mqty < 10");
    if ($row = mysqli_fetch_assoc($res))
        $stats['low_stock_items'] = $row['cnt'];

    // Admin-only stats
    if ($is_admin) {
        $res = mysqli_query($conn, "SELECT COUNT(*) AS cnt FROM Customers");
        if ($row = mysqli_fetch_assoc($res))
            $stats['total_customers'] = $row['cnt'];

        $res = mysqli_query($conn, "SELECT COUNT(*) AS cnt FROM Staffs");
        if ($row = mysqli_fetch_assoc($res))
            $stats['total_staff'] = $row['cnt'];
    }

    mysqli_close($conn);
    ?>
    <header class="navbar">
        <div class="logo">
            <h2>Premium Living</h2>
            <small style="color:#bdc3c7; font-size:0.9rem;">Staff Area</small>
        </div>
        <nav class="nav-links">
            <a href="dashboard.php" class="active">Dashboard</a>
            <a href="furniture_add.php">Add Furniture</a>
            <a href="materials_add.php">Materials</a>
            <a href="orders_manage.php">Orders</a>
            <a href="report.php">Reports</a>
            <?php if ($is_admin): ?>
                <a href="staff_manage.php">Manage Staff</a>
                <a href="customers_view.php">Customers</a>
            <?php endif; ?>
        </nav>
        <div class="nav-right">
            <span style="color:#ecf0f1; margin-right:1.2rem;">
                <?= htmlspecialchars($staff_name) ?>
                <?php if ($is_admin): ?><small style="color:#e67e22;">(Administrator)</small><?php endif; ?>
            </span>
            <a href="logout.php" class="btn-outline logout-btn">Logout</a>
        </div>
    </header>

    <div class="dashboard-container">

        <div class="welcome-bar">
            <div>
                <h2>Welcome back, <?= htmlspecialchars($staff_name) ?>!</h2>
                <p>Manage your furniture inventory, orders & materials<?= $is_admin ? ' and system users' : '' ?></p>
            </div>
            <div>
                <i class="fas fa-user-tie fa-3x"></i>
            </div>
        </div>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-shopping-bag"></i></div>
                <div class="stat-value"><?= number_format($stats['total_orders']) ?></div>
                <div class="stat-label">Total Orders</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-clock"></i></div>
                <div class="stat-value"><?= number_format($stats['pending_orders']) ?></div>
                <div class="stat-label">Pending Orders</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-dollar-sign"></i></div>
                <div class="stat-value">$<?= number_format($stats['total_sales'], 2) ?></div>
                <div class="stat-label">Total Sales</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-exclamation-triangle"></i></div>
                <div class="stat-value"><?= number_format($stats['low_stock_items']) ?></div>
                <div class="stat-label">Low Stock Items</div>
            </div>

            <?php if ($is_admin): ?>
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-users"></i></div>
                    <div class="stat-value"><?= number_format($stats['total_customers']) ?></div>
                    <div class="stat-label">Registered Customers</div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-user-shield"></i></div>
                    <div class="stat-value"><?= number_format($stats['total_staff']) ?></div>
                    <div class="stat-label">Staff Accounts</div>
                </div>
            <?php endif; ?>
        </div>

        <h2 style="text-align:center; margin: 3rem 0 2rem; color:#2c3e50;">Quick Actions</h2>

        <div class="actions-grid">
            <div class="action-card">
                <div class="action-icon"><i class="fas fa-plus-circle"></i></div>
                <div class="action-title">New Furniture</div>
                <p>Add furniture item with materials & price</p>
                <a href="furniture_add.php" class="action-btn">Add Item</a>
            </div>

            <div class="action-card">
                <div class="action-icon"><i class="fas fa-boxes"></i></div>
                <div class="action-title">Materials</div>
                <p>Update stock or add new raw materials</p>
                <a href="materials_add.php" class="action-btn">Manage Stock</a>
            </div>

            <div class="action-card">
                <div class="action-icon"><i class="fas fa-clipboard-list"></i></div>
                <div class="action-title">Orders</div>
                <p>Review, accept, reject or update orders</p>
                <a href="orders_manage.php" class="action-btn">View Orders</a>
            </div>

            <div class="action-card">
                <div class="action-icon"><i class="fas fa-chart-line"></i></div>
                <div class="action-title">Reports</div>
                <p>Sales, stock usage, top products</p>
                <a href="report.php" class="action-btn">Generate Report</a>
            </div>

            <?php if ($is_admin): ?>
                <div class="action-card">
                    <div class="action-icon"><i class="fas fa-user-plus"></i></div>
                    <div class="action-title">Manage Staff</div>
                    <p>Add, edit or deactivate staff accounts</p>
                    <a href="staff_manage.php" class="action-btn">Staff Management</a>
                </div>

                <div class="action-card">
                    <div class="action-icon"><i class="fas fa-users-cog"></i></div>
                    <div class="action-title">Customers Overview</div>
                    <p>View customer list & basic activity</p>
                    <a href="customers_view.php" class="action-btn">View Customers</a>
                </div>

                <div class="action-card">
                    <div class="action-icon"><i class="fas fa-users-cog"></i></div>
                    <div class="action-title">Password Change</div>
                    <p>Change your password</p>
                    <a href="password_change.php" class="action-btn">Change Password</a>
                </div>
            <?php endif; ?>
        </div>

    </div>
</body>

</html>