<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Orders - Premium Living Furniture</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
</head>

<body>
    <?php
    // customer/orders.php
    
    session_start();

    if (!isset($_SESSION['customer_id'])) {
        header("Location: login.php");
        exit();
    }

    include '../connections/dbconn.php';

    $customer_id = $_SESSION['customer_id'];

    // Fetch all orders for this customer
    $orders = [];
    $stmt = $conn->prepare("
    SELECT oid, odate, ototalamount, odeliverydate, odeliveraddress, ostatus
    FROM Orders
    WHERE cid = ?
    ORDER BY odate DESC
");
    $stmt->bind_param("i", $customer_id);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $orders[] = $row;
    }
    $stmt->close();

    // Status labels and colors
    $status_labels = [
        0 => ['Rejected', 'danger'],
        1 => ['Open', 'warning'],
        2 => ['Processing', 'info'],
        3 => ['Approved', 'primary'],
        4 => ['Pending Delivery', 'secondary'],
        5 => ['Completed', 'success']
    ];

    // Fetch order items for each order
    foreach ($orders as $key => $dummy) {
        $order_id = $orders[$key]['oid'];

        $items_stmt = $conn->prepare("
        SELECT of.oqty, f.fname, f.fprice
        FROM OrderFurnitures of
        JOIN Furnitures f ON of.fid = f.fid
        WHERE of.oid = ?
    ");
        $items_stmt->bind_param("i", $order_id);
        $items_stmt->execute();
        $items_result = $items_stmt->get_result();

        $orders[$key]['items'] = [];
        while ($item = $items_result->fetch_assoc()) {
            $orders[$key]['items'][] = $item;
        }
        $items_stmt->close();
    }

    mysqli_close($conn);
    ?>
    <header class="navbar">
        <div class="logo">
            <h2>Premium Living</h2>
        </div>
        <nav class="nav-links">
            <a href="../index.php">Home</a>
            <a href="shop.php">Shop</a>
            <a href="orders.php" class="active">My Orders</a>
            <a href="profile.php">Profile</a>
        </nav>
        <div class="nav-right">
            <span>Welcome, <?= htmlspecialchars($_SESSION['customer_name'] ?? 'Guest') ?>, <?= htmlspecialchars($_SESSION['company'] ?? '') ?></span>
            <a href="logout.php" class="btn-outline">Logout</a>
        </div>
        <div class="nav-right">
            <a href="../customer/cart.php" class="cart-icon">
                <i class="fas fa-shopping-cart"></i>
                <span class="cart-count">
                    <?= array_sum($_SESSION['cart'] ?? []) ?>
                </span>
            </a>
            <button class="menu-toggle" id="menuToggle">
                <i class="fas fa-bars"></i>
            </button>
        </div>
    </header>

    <div class="dashboard-container">

        <h1 class="section-title" style="margin:2rem 0;">My Orders</h1>

        <?php if (empty($orders)): ?>
            <div
                style="text-align:center; padding:4rem 0; background:white; border-radius:12px; box-shadow:0 4px 15px rgba(0,0,0,0.08);">
                <i class="fas fa-box-open fa-5x" style="color:#bdc3c7; margin-bottom:1rem;"></i>
                <h3>You have no orders yet</h3>
                <p style="color:#777; margin:1rem 0;">Start shopping now!</p>
                <a href="shop.php" class="btn-primary" style="padding:0.9rem 2rem;">Browse Products</a>
            </div>
        <?php else: ?>
            <div style="overflow-x:auto;">
                <?php foreach ($orders as $order): ?>
                    <div
                        style="background:white; border-radius:12px; box-shadow:0 4px 15px rgba(0,0,0,0.08); margin-bottom:2rem; padding:1.5rem;">
                        <div
                            style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1rem; flex-wrap:wrap; gap:1rem;">
                            <div>
                                <h3 style="margin:0;">Order #<?= $order['oid'] ?></h3>
                                <small>Placed on: <?= date('d M Y H:i', strtotime($order['odate'])) ?></small>
                            </div>
                            <div>
                                <span class="badge bg-<?= $status_labels[$order['ostatus']][1] ?? 'secondary' ?>">
                                    <?= $status_labels[$order['ostatus']][0] ?? 'Unknown' ?>
                                </span>
                            </div>
                        </div>

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

                        <div style="text-align:right; font-weight:bold; margin-top:1rem;">
                            Total: $<?= number_format($order['ototalamount'], 2) ?>
                        </div>

                        <div style="margin-top:1rem; color:#555;">
                            <strong>Delivery to:</strong> <?= htmlspecialchars($order['odeliveraddress']) ?><br>
                            <strong>Delivery date:</strong> <?= date('d M Y H:i', strtotime($order['odeliverydate'])) ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <div style="text-align:center; margin-top:2rem;">
                <a href="shop.php" class="btn-primary">Continue Shopping</a>
            </div>
        <?php endif; ?>

    </div>
    <footer>
        <div class="footer-content">
            <div class="footer-section">
                <h3>Premium Living</h3>
                <p>Bringing elegance and comfort to your home since 2015.</p>
            </div>
            <div class="footer-section">
                <h3>Quick Links</h3>
                <ul>
                    <li><a href="../customer/shop.php">Shop</a></li>
                    <li><a href="../customer/orders.php">Orders</a></li>
                    <li><a href="../customer/profile.php">My Account</a></li>
                </ul>
            </div>
            <div class="footer-section">
                <h3>Contact</h3>
                <p>Email: support@premiumliving.com</p>
                <p>Phone: +852 1234 5678</p>
            </div>
        </div>
        <div class="footer-bottom">
            <p>©
                <?= date("Y") ?> Premium Living Furniture Co. Ltd. All rights reserved.
            </p>
        </div>
    </footer>
</body>

</html>