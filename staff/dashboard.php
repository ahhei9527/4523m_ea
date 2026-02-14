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
    <?php include("../connections/dbconn.php");
    // staff/dashboard.php
    
    session_start();

    // Security: must be logged in
    $staff_name = $_SESSION['staff_name'] ?? 'Staff';
    $is_admin = (isset($_SESSION['staff_role']) && strtolower($_SESSION['staff_role']) === 'administrator');
    $is_staff = (isset($_SESSION['staff_role']) && in_array(strtolower($_SESSION['staff_role']), ['administrator', 'staff']));

    include '../connections/dbconn.php';
    if ($is_staff) {
        // Quick stats (same for everyone)
        $stats = [
            'total_orders' => 0,
            'pending_orders' => 0,
            'total_sales' => 0.00,
            'low_stock_items' => 0,
        ];

        $res = mysqli_query($conn, "SELECT COUNT(*) AS cnt FROM Orders");
        if ($r = mysqli_fetch_assoc($res))
            $stats['total_orders'] = $r['cnt'];

        $res = mysqli_query($conn, "SELECT COUNT(*) AS cnt FROM Orders WHERE ostatus IN (1,2)");
        if ($r = mysqli_fetch_assoc($res))
            $stats['pending_orders'] = $r['cnt'];

        $res = mysqli_query($conn, "SELECT COALESCE(SUM(ototalamount), 0) AS total FROM Orders WHERE ostatus = 5");
        if ($r = mysqli_fetch_assoc($res))
            $stats['total_sales'] = $r['total'];

        $res = mysqli_query($conn, "SELECT COUNT(*) AS cnt FROM Materials WHERE mqty < 10");
        if ($r = mysqli_fetch_assoc($res))
            $stats['low_stock_items'] = $r['cnt'];

        // Admin-only stats
        if ($is_admin) {
            $res = mysqli_query($conn, "SELECT COUNT(*) AS cnt FROM Customers");
            if ($r = mysqli_fetch_assoc($res))
                $stats['total_customers'] = $r['cnt'];

            $res = mysqli_query($conn, "SELECT COUNT(*) AS cnt FROM Staffs");
            if ($r = mysqli_fetch_assoc($res))
                $stats['total_staff'] = $r['cnt'];
        }

        mysqli_close($conn);
    } else if ($is_admin) {
        $stats = [
            'total_orders' => 0,
            'pending_orders' => 0,
            'total_sales' => 0.00,
            'low_stock_items' => 0,
            'total_customers' => 0,     // only shown to admin
            'total_staff' => 0      // only shown to admin
        ];

        $res = mysqli_query($conn, "SELECT COUNT(*) AS cnt FROM Orders");
        if ($r = mysqli_fetch_assoc($res))
            $stats['total_orders'] = $r['cnt'];

        $res = mysqli_query($conn, "SELECT COUNT(*) AS cnt FROM Orders WHERE ostatus IN (1,2)");
        if ($r = mysqli_fetch_assoc($res))
            $stats['pending_orders'] = $r['cnt'];

        $res = mysqli_query($conn, "SELECT COALESCE(SUM(ototalamount), 0) AS total FROM Orders WHERE ostatus = 5");
        if ($r = mysqli_fetch_assoc($res))
            $stats['total_sales'] = $r['total'];

        $res = mysqli_query($conn, "SELECT COUNT(*) AS cnt FROM Materials WHERE mqty < 10");
        if ($r = mysqli_fetch_assoc($res))
            $stats['low_stock_items'] = $r['cnt'];

        // Admin-only stats
        if ($is_admin) {
            $res = mysqli_query($conn, "SELECT COUNT(*) AS cnt FROM Customers");
            if ($r = mysqli_fetch_assoc($res))
                $stats['total_customers'] = $r['cnt'];

            $res = mysqli_query($conn, "SELECT COUNT(*) AS cnt FROM Staffs");
            if ($r = mysqli_fetch_assoc($res))
                $stats['total_staff'] = $r['cnt'];
        }
    } else {
        header("Location: login.php");
        exit();
    }
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
                <?php if ($is_admin): ?><small style="color:#e67e22;">(Admin)</small><?php endif; ?>
            </span>
            <a href="logout.php" class="btn-outline logout-btn">Logout</a>
        </div>
    </header>

    <div class="dashboard-container">

        <div class="welcome-bar">
            <div>
                <h2>Welcome back, <?= htmlspecialchars($staff_name) ?>!</h2>
                <p>Manage furniture, materials, orders <?= $is_admin ? 'and system users' : '' ?></p>
            </div>
            <div>
                <i class="fas fa-user-tie fa-3x"></i>
            </div>
        </div>

        <!-- Statistics -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-shopping-bag"></i></div>
                <div class="stat-value"><?= number_format($stats['total_orders']) ?></div>
                <div class="stat-label">Total Orders</div>
            </div>

            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-clock"></i></div>
                <div class="stat-value"><?= number_format($stats['pending_orders']) ?></div>
                <div class="stat-label">Pending</div>
            </div>

            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-dollar-sign"></i></div>
                <div class="stat-value">$<?= number_format($stats['total_sales'], 2) ?></div>
                <div class="stat-label">Completed Sales</div>
            </div>

            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-exclamation-triangle"></i></div>
                <div class="stat-value"><?= number_format($stats['low_stock_items']) ?></div>
                <div class="stat-label">Low Stock</div>
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

        <!-- Quick Actions -->
        <h2 class="section-title" style="margin: 3.5rem 0 2rem;">Quick Actions</h2>

        <div class="actions-grid">
            <div class="action-card">
                <div class="action-icon"><i class="fas fa-plus-circle"></i></div>
                <h3 class="action-title">New Furniture</h3>
                <p>Add furniture item with materials & price</p>
                <a href="furniture_add.php" class="action-btn">Add Item</a>
            </div>

            <div class="action-card">
                <div class="action-icon"><i class="fas fa-boxes"></i></div>
                <h3 class="action-title">Materials</h3>
                <p>Update stock or add new raw materials</p>
                <a href="materials_add.php" class="action-btn">Manage Stock</a>
            </div>

            <div class="action-card">
                <div class="action-icon"><i class="fas fa-clipboard-list"></i></div>
                <h3 class="action-title">Orders</h3>
                <p>Review, accept, reject or update orders</p>
                <a href="orders_manage.php" class="action-btn">View Orders</a>
            </div>

            <div class="action-card">
                <div class="action-icon"><i class="fas fa-chart-line"></i></div>
                <h3 class="action-title">Reports</h3>
                <p>Sales, stock usage, top products</p>
                <a href="report.php" class="action-btn">Generate Report</a>
            </div>

            <?php if ($is_admin): ?>
                <div class="action-card admin-only">
                    <div class="action-icon"><i class="fas fa-user-plus"></i></div>
                    <h3 class="action-title">Manage Staff</h3>
                    <p>Add, edit or deactivate staff accounts</p>
                    <a href="staff_manage.php" class="action-btn">Staff Management</a>
                </div>

                <div class="action-card admin-only">
                    <div class="action-icon"><i class="fas fa-users-cog"></i></div>
                    <h3 class="action-title">Customers Overview</h3>
                    <p>View customer list & basic activity</p>
                    <a href="customers_view.php" class="action-btn">View Customers</a>
                </div>
            <?php endif; ?>
        </div>

    </div>

</body>

</html>