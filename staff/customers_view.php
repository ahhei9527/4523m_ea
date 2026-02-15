<?php
// customers_view.php (admin-only customer list)

session_start();

// Must be logged in AND admin
$role = isset($_SESSION['staff_role']) ? strtolower(trim($_SESSION['staff_role'])) : '';
$is_admin = ($role === 'admin' || $role === 'administrator');

if (!isset($_SESSION['staff_id']) || !$is_admin) {
    header("Location: dashboard.php");
    exit();
}

include '../connections/dbconn.php';

$message = '';
$error   = '';

// Load customer list
$customerList = [];
$result = mysqli_query($conn, "
    SELECT cid, cname, cemail, ctel, company 
    FROM Customers 
    ORDER BY cname ASC
");

while ($row = mysqli_fetch_assoc($result)) {
    $customerList[] = $row;
}

mysqli_close($conn);
?>

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

<header class="navbar">
    <div class="logo">
        <h2>Premium Living</h2>
        <small>Staff Area</small>
    </div>
    <nav class="nav-links">
        <a href="dashboard.php">Dashboard</a>
        <a href="furniture_add.php">Add Furniture</a>
        <a href="materials_add.php">Materials</a>
        <a href="orders_manage.php">Orders</a>
        <a href="report.php">Reports</a>
        <a href="staff_manage.php">Manage Staff</a>
        <a href="customers_view.php" class="active">Customers</a>
    </nav>
    <div class="nav-right">
        <span style="color:#ecf0f1; margin-right:1.2rem;">
            <?= htmlspecialchars($_SESSION['staff_name'] ?? 'Admin') ?>
            (<?= htmlspecialchars(ucfirst($_SESSION['staff_role'] ?? 'Staff')) ?>)
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
                        <th>Email</th>
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
                            <td><?= htmlspecialchars($customer['cemail']) ?></td>
                            <td><?= htmlspecialchars($customer['ctel'] ?: '—') ?></td>
                            <td><?= htmlspecialchars($customer['company'] ?: '—') ?></td>
                            <td style="text-align:center;">
                                <form method="POST" action="customer_password_change.php" style="display:inline;">
                                    <input type="hidden" name="target_cid" value="<?= $customer['cid'] ?>">
                                    <button type="submit" class="btn-outline toggle-btn" style="padding:0.4rem 1rem; font-size:0.9rem;">
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