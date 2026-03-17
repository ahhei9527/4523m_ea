<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sales Report - Premium Living Furniture</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
</head>

<body>
    <?php
    // staff/report.php
    // Must be logged in as staff
    if (!isset($_COOKIE['staff_id'])) {
        header("Location: login.php");
        exit();
    }
    $staff_name = $_COOKIE['staff_name'];
    $is_admin = (isset($_COOKIE['staff_role']) && $_COOKIE['staff_role'] === "admin");

    include '../connections/dbconn.php';
    // Optional date range filter
    $start_date = $_GET['start_date'] ?? date('Y-m-01'); // default: first day of current month
    $end_date = $_GET['end_date'] ?? date('Y-m-d');   // default: today
    
    // Total sales & completed orders
    $total_sales = 0;
    $completed_orders = 0;
    $stmt = $conn->prepare("
    SELECT COUNT(*) AS order_count, COALESCE(SUM(ototalamount), 0) AS total_sales
    FROM Orders
    WHERE ostatus = 5
      AND odate BETWEEN ? AND ?
");
    $stmt->bind_param("ss", $start_date, $end_date);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $completed_orders = $row['order_count'];
    $total_sales = $row['total_sales'];
    $stmt->close();

    // Top-selling products (quantity sold & revenue)
    $top_products = [];
    $stmt = $conn->prepare("
    SELECT f.fname, SUM(of.oqty) AS total_qty, SUM(of.oqty * f.fprice) AS total_revenue
    FROM OrderFurnitures of
    JOIN Furnitures f ON of.fid = f.fid
    JOIN Orders o ON of.oid = o.oid
    WHERE o.ostatus = 5
      AND o.odate BETWEEN ? AND ?
    GROUP BY f.fid, f.fname
    ORDER BY total_qty DESC
    LIMIT 10
");
    $stmt->bind_param("ss", $start_date, $end_date);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $top_products[] = $row;
    }
    $stmt->close();

    // Low stock materials
    $low_stock = [];
    $result = $conn->query("
    SELECT mname, mqty, munit
    FROM Materials
    WHERE mqty < 10
    ORDER BY mqty ASC
");
    while ($row = $result->fetch_assoc()) {
        $low_stock[] = $row;
    }

    // Sales by month (last 12 months)
    $sales_by_month = [];
    $stmt = $conn->prepare("
    SELECT DATE_FORMAT(odate, '%Y-%m') AS month, 
           COUNT(*) AS order_count, 
           COALESCE(SUM(ototalamount), 0) AS monthly_total
    FROM Orders
    WHERE ostatus = 5
      AND odate >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
    GROUP BY DATE_FORMAT(odate, '%Y-%m')
    ORDER BY month DESC
");
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $sales_by_month[] = $row;
    }
    $stmt->close();

    mysqli_close($conn);
    ?>

    <header class="navbar">
        <div class="logo">
            <h2>Premium Living</h2>
            <small style="color:#bdc3c7; font-size:0.9rem;">Staff Area</small>
        </div>
        <nav class="nav-links">
            <a href="dashboard.php">Dashboard</a>
            <a href="furniture_add.php">Add Furniture</a>
            <a href="materials_add.php">Materials</a>
            <a href="orders_manage.php">Orders</a>
            <a href="report.php" class="active">Reports</a>
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

        <h1 class="section-title" style="margin:2rem 0;">Sales Report</h1>

        <!-- Date Range Filter -->
        <form method="GET" action=""
            style="background:white; padding:1.5rem; border-radius:12px; box-shadow:0 4px 15px rgba(0,0,0,0.08); margin-bottom:2rem;">
            <div style="display:flex; gap:1.5rem; flex-wrap:wrap; align-items:flex-end;">
                <div class="form-group">
                    <label>Start Date</label>
                    <input type="date" name="start_date" value="<?= htmlspecialchars($start_date) ?>">
                </div>
                <div class="form-group">
                    <label>End Date</label>
                    <input type="date" name="end_date" value="<?= htmlspecialchars($end_date) ?>">
                </div>
                <button type="submit" class="btn-primary" style="padding:0.7rem 1.5rem;">
                    <i class="fas fa-filter"></i> Filter
                </button>
            </div>
        </form>

        <!-- Key Metrics -->
        <div class="stats-grid" style="margin-bottom:3rem;">
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-dollar-sign"></i></div>
                <div class="stat-value">$<?= number_format($total_sales, 2) ?></div>
                <div class="stat-label">Total Sales (Selected Period)</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-shopping-bag"></i></div>
                <div class="stat-value"><?= number_format($completed_orders) ?></div>
                <div class="stat-label">Completed Orders</div>
            </div>
        </div>

        <!-- Top Selling Products -->
        <h2 style="margin:2rem 0 1rem; color:#2c3e50;">Top Selling Products</h2>
        <?php if (empty($top_products)): ?>
            <p style="text-align:center; color:#777;">No sales data in selected period.</p>
        <?php else: ?>
            <div style="overflow-x:auto;">
                <table class="staff-table">
                    <thead>
                        <tr>
                            <th>Product Name</th>
                            <th>Units Sold</th>
                            <th>Total Revenue</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($top_products as $p): ?>
                            <tr>
                                <td><?= htmlspecialchars($p['fname']) ?></td>
                                <td><?= number_format($p['total_qty']) ?></td>
                                <td>$<?= number_format($p['total_revenue'], 2) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>

        <!-- Low Stock Alert -->
        <h2 style="margin:3rem 0 1rem; color:#2c3e50;">Low Stock Materials (qty < 10)</h2>
                <?php if (empty($low_stock)): ?>
                    <p style="color:#28a745; text-align:center;">All materials have sufficient stock.</p>
                <?php else: ?>
                    <div style="overflow-x:auto;">
                        <table class="staff-table">
                            <thead>
                                <tr>
                                    <th>Material Name</th>
                                    <th>Current Quantity</th>
                                    <th>Unit</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($low_stock as $m): ?>
                                    <tr style="background:#fff3cd;">
                                        <td><?= htmlspecialchars($m['mname']) ?></td>
                                        <td><?= $m['mqty'] ?></td>
                                        <td><?= htmlspecialchars($m['munit']) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>

                <!-- Sales by Month -->
                <h2 style="margin:3rem 0 1rem; color:#2c3e50;">Sales by Month (Last 12 Months)</h2>
                <?php if (empty($sales_by_month)): ?>
                    <p style="text-align:center; color:#777;">No sales data available.</p>
                <?php else: ?>
                    <div style="overflow-x:auto;">
                        <table class="staff-table">
                            <thead>
                                <tr>
                                    <th>Month</th>
                                    <th>Orders</th>
                                    <th>Total Sales</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($sales_by_month as $m): ?>
                                    <tr>
                                        <td><?= date('M Y', strtotime($m['month'] . '-01')) ?></td>
                                        <td><?= $m['order_count'] ?></td>
                                        <td>$<?= number_format($m['monthly_total'], 2) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>

    </div>

</body>