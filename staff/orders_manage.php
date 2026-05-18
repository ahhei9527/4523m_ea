<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Orders - Premium Living Furniture</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
</head>

<body>
    <?php
    // staff/orders_manage.php
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
    
    $staff_name = $_COOKIE['staff_name'] ?? 'Staff';
    $is_admin = (isset($_COOKIE['staff_role']) && $_COOKIE['staff_role'] == "Administrator");

    include '../connections/dbconn.php';

    $status_options = [
        0 => 'Rejected',
        1 => 'Open',
        2 => 'Processing',
        3 => 'Approved',
        4 => 'Pending Delivery',
        5 => 'Completed'
    ];

    // Handle status update
    $message = $error = null;
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
        $oid = (int) $_POST['oid'];
        $new_status = (int) $_POST['ostatus'];
        mysqli_begin_transaction($conn);

        try {
            // 1. Get current status
            $prev_stmt = $conn->prepare("SELECT ostatus FROM Orders WHERE oid = ?");
            $prev_stmt->bind_param("i", $oid);
            $prev_stmt->execute();
            $prev_result = $prev_stmt->get_result();
            $old_status = $prev_result->fetch_assoc()['ostatus'] ?? -1;
            $prev_stmt->close();

            // 2. Update order
            $update_stmt = $conn->prepare("
                UPDATE Orders 
                SET ostatus = ?
                WHERE oid = ?
            ");
            $update_stmt->bind_param("ii", $new_status, $oid);
            $update_stmt->execute();
            if ($update_stmt->affected_rows === 0) {
                throw new Exception("Order #$oid not found or status unchanged.");
            }
            $update_stmt->close();

            // 3. Restore stock only if newly rejected
            if ($new_status === 0 && $old_status !== 0) {
                $items_stmt = $conn->prepare("
                    SELECT of.fid, of.oqty, fm.mid, fm.pmqty
                    FROM OrderFurnitures of
                    JOIN FurnitureMaterials fm ON of.fid = fm.fid
                    WHERE of.oid = ?
                ");
                $items_stmt->bind_param("i", $oid);
                $items_stmt->execute();
                $items_result = $items_stmt->get_result();

                while ($item = $items_result->fetch_assoc()) {
                    $qty_to_restore = $item['oqty'] * $item['pmqty'];

                    $restore_stmt = $conn->prepare("
                        UPDATE Materials 
                        SET mqty = mqty + ? 
                        WHERE mid = ?
                    ");
                    $restore_stmt->bind_param("ii", $qty_to_restore, $item['mid']);
                    $restore_stmt->execute();
                    $restore_stmt->close();
                }
                $items_stmt->close();
            }

            mysqli_commit($conn);

            $message = "Order #$oid updated to " . $status_options[$new_status] . ".";
            if ($new_status === 0 && $old_status !== 0) {
                $message .= " Material stock restored.";
            }

        } catch (Exception $e) {
            mysqli_rollback($conn);
            $error = "Operation failed for order #$oid: " . $e->getMessage();
        }
    }

    // Fetch all orders + customer info
    $orders = [];
    $stmt = $conn->prepare("
    SELECT 
        o.oid, o.odate, o.ototalamount, o.odeliverydate, o.odeliveraddress, 
        o.ostatus,  o.cid,
        c.cname, c.ctel
    FROM Orders o
    LEFT JOIN Customers c ON o.cid = c.cid
    ORDER BY o.odate DESC
");
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $orders[] = $row;
    }
    $stmt->close();

    // Fetch items for each order
    foreach ($orders as &$order) {
        $oid = $order['oid'];
        $items_stmt = $conn->prepare("
        SELECT of.oqty, f.fname, f.fprice
        FROM OrderFurnitures of
        JOIN Furnitures f ON of.fid = f.fid
        WHERE of.oid = ?
    ");
        $items_stmt->bind_param("i", $oid);
        $items_stmt->execute();
        $items_result = $items_stmt->get_result();

        $order['items'] = $items_result->fetch_all(MYSQLI_ASSOC);
        $items_stmt->close();
    }
    unset($order); // prevent reference bugs
    
    $conn->close();
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
            <a href="orders_manage.php" class="active">Orders</a>
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

        <h1 class="section-title" style="margin:2rem 0;">Manage Orders</h1>

        <?php if (isset($message)): ?>
            <div class="success-message"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>

        <?php if (isset($error)): ?>
            <div class="error-message"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <?php if (empty($orders)): ?>
            <p style="text-align:center; padding:4rem 0; color:#777;">
                No orders found.
            </p>
        <?php else: ?>
            <?php foreach ($orders as $order): ?>
                <div
                    style="background:white; border-radius:12px; box-shadow:0 4px 15px rgba(0,0,0,0.08); margin-bottom:2rem; padding:1.5rem;">
                    <div
                        style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1rem; flex-wrap:wrap; gap:1rem;">
                        <div>
                            <h3 style="margin:0;">Order #<?= $order['oid'] ?></h3>
                            <small>Placed: <?= date('d M Y H:i', strtotime($order['odate'])) ?></small><br>
                            <small>Customer: <?= htmlspecialchars($order['cname']) ?>
                                (<?= htmlspecialchars($order['ctel'] ?: 'No phone') ?>)</small>
                        </div>
                        <div>
                            <span
                                class="badge bg-<?= ['danger', 'warning', 'info', 'primary', 'secondary', 'success'][$order['ostatus']] ?? 'secondary' ?>">
                                <?= $status_options[$order['ostatus']] ?? 'Unknown' ?>
                            </span>
                        </div>
                    </div>

                    <!-- Order items -->
                    <table class="cart-table" style="width:100%; margin:1rem 0;">
                        <thead>
                            <tr>
                                <th>Item</th>
                                <th>Price</th>
                                <th>Qty</th>
                                <th>Subtotal</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($order['items'] as $item): ?>
                                <tr>
                                    <td><?= htmlspecialchars($item['fname']) ?></td>
                                    <td>$<?= number_format($item['fprice'], 2) ?></td>
                                    <td><?= $item['oqty'] ?></td>
                                    <td>$<?= number_format($item['fprice'] * $item['oqty'], 2) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>

                    <!-- Total & Delivery -->
                    <div
                        style="display:flex; justify-content:space-between; align-items:center; margin-top:1rem; flex-wrap:wrap; gap:1rem;">
                        <div>
                            <strong>Delivery to:</strong> <?= htmlspecialchars($order['odeliveraddress']) ?><br>
                            <strong>Delivery date:</strong> <?= date('d M Y H:i', strtotime($order['odeliverydate'])) ?>
                        </div>
                        <div style="font-weight:bold; font-size:1.2rem; color:#e67e22;">
                            Total: $<?= number_format($order['ototalamount'], 2) ?>
                        </div>
                    </div>

                    <!-- Status update form -->
                    <form method="POST" action=""
                        style="margin-top:1.5rem; display:flex; flex-wrap:wrap; gap:1rem; align-items:flex-end;">
                        <input type="hidden" name="oid" value="<?= $order['oid'] ?>">
                        <input type="hidden" name="update_status" value="1">

                        <div class="form-group" style="flex:1; min-width:200px;">
                            <label>Update Status</label>
                            <select name="ostatus" required style="width:100%; padding:0.6rem;">
                                <?php foreach ($status_options as $val => $label): ?>
                                    <option value="<?= $val ?>" <?= $order['ostatus'] == $val ? 'selected' : '' ?>>
                                        <?= $label ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <button type="submit" class="btn-primary" style="padding:0.6rem 1.5rem;">
                            Update Status
                        </button>
                    </form>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>

    </div>

</body>

</html>