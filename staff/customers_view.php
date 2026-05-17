<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Customers - Premium Living Furniture</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
</head>

<body>
    <?php
    // customers_view.php (admin-only customer list)
    // Must be logged in AND admin
    $role = isset($_COOKIE['staff_role']) ? strtolower(trim($_COOKIE['staff_role'])) : '';
    $is_admin = ($role === 'admin' || $role === 'administrator');

    // === AUTO EXTEND COOKIES ON ANY ACTIVITY ===
    if (isset($_COOKIE['staff_id'])) {
        $staff_id   = $_COOKIE['staff_id'];
        $staff_name = $_COOKIE['staff_name'] ?? 'Staff';
        $staff_role = $_COOKIE['staff_role'] ?? '';

        // Renew cookies (extend lifetime)
        setcookie('staff_id',   $staff_id,   time() + 1200, "/", "", false, true);
        setcookie('staff_name', $staff_name, time() + 1200, "/", "", false, true);
        setcookie('staff_role', $staff_role, time() + 1200, "/", "", false, true);
    } else {
        header("Location: login.php");
        exit();
    }

    include '../connections/dbconn.php';

    $staff_name = $_COOKIE['staff_name'] ?? 'Staff';
    $is_admin = (isset($_COOKIE['staff_role']) && $_COOKIE['staff_role'] == "admin");
    $message = '';
    $error = '';

    include '../connections/dbconn.php';

    $message = '';
    $error = '';

    // Load customer list
    $customerList = [];
    $result = mysqli_query($conn, "
    SELECT cid, cname, ctel, company 
    FROM Customers 
    ORDER BY cname ASC
");

    while ($row = mysqli_fetch_assoc($result)) {
        $customerList[] = $row;
    }

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
            <a href="report.php">Reports</a>
            <?php if ($is_admin): ?>
                <a href="staff_manage.php">Manage Staff</a>
                <a href="customers_view.php" class="active">Customers</a>
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

        <h1 class="section-title" style="margin-top:1.5rem;">Customer Management</h1>

        <?php if ($message): ?>
            <div class="success-message"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="error-message"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <h2 style="margin: 3rem 0 1rem; color:#2c3e50;">
            Existing Customers (<?= count($customerList) ?>)
        </h2>

        <?php if (empty($customerList)): ?>
            <p style="text-align:center; color:#777; padding:2rem;">No customer records found.</p>
        <?php else: ?>
            <div style="overflow-x:auto;">
                <table class="staff-table">
                    <thead>
                        <tr>
                            <th>CID</th>
                            <th>Name</th>
                            <th>Phone</th>
                            <th>Company</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($customerList as $customer): ?>
                            <tr>
                                <td><?= htmlspecialchars($customer['cid']) ?></td>
                                <td><?= htmlspecialchars($customer['cname']) ?></td>
                                <td><?= htmlspecialchars($customer['ctel'] ?: '—') ?></td>
                                <td><?= htmlspecialchars($customer['company'] ?: '—') ?></td>
                                <td style="text-align:center;">
                                    <form method="POST" action="customer_password_change.php" style="display:inline;">
                                        <input type="hidden" name="target_cid" value="<?= $customer['cid'] ?>">
                                        <button type="submit" class="btn-outline toggle-btn"
                                            style="padding:0.4rem 1rem; font-size:0.9rem;">
                                            Change Password
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>

        <p style="text-align:center; margin-top:2rem;">
            <a href="dashboard.php" class="btn-outline">← Back to Dashboard</a>
        </p>

    </div>

</body>

</html>