<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your Cart - Premium Living Furniture</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
</head>

<body>
    <?php
    // customer/cart.php
    
    session_start();

    include '../connections/dbconn.php';

    // Cart is stored in session as array [fid => quantity]
    if (!isset($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }

    $cart_items = [];
    $total = 0.00;

    if (!empty($_SESSION['cart'])) {
        $fids = array_keys($_SESSION['cart']);
        $placeholders = implode(',', array_fill(0, count($fids), '?'));

        $stmt = $conn->prepare("
        SELECT fid, fname, fprice
        FROM Furnitures
        WHERE fid IN ($placeholders)
    ");
        $types = str_repeat('i', count($fids));
        $stmt->bind_param($types, ...$fids);
        $stmt->execute();
        $result = $stmt->get_result();

        while ($row = $result->fetch_assoc()) {
            $qty = $_SESSION['cart'][$row['fid']];
            $subtotal = $row['fprice'] * $qty;
            $total += $subtotal;

            $cart_items[] = [
                'fid' => $row['fid'],
                'fname' => $row['fname'],
                'fprice' => $row['fprice'],
                'qty' => $qty,
                'subtotal' => $subtotal
            ];
        }
        $stmt->close();
    }

    mysqli_close($conn);

    // Handle quantity update or remove
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $fid = (int) ($_POST['fid'] ?? 0);
        $action = $_POST['action'] ?? '';

        if ($fid > 0 && isset($_SESSION['cart'][$fid])) {
            if ($action === 'update') {
                $qty = (int) ($_POST['qty'] ?? 1);
                if ($qty > 0) {
                    $_SESSION['cart'][$fid] = $qty;
                } else {
                    unset($_SESSION['cart'][$fid]);
                }
            } elseif ($action === 'remove') {
                unset($_SESSION['cart'][$fid]);
            }
        }
        // Refresh page
        header("Location: cart.php");
        exit();
    }
    ?>
    <header class="navbar">
        <div class="logo">
            <h2>Premium Living</h2>
        </div>
        <nav class="nav-links">
            <a href="../index.php">Home</a>
            <a href="shop.php">Shop</a>
            <a href="orders.php">My Orders</a>
            <a href="profile.php">Profile</a>
        </nav>
        <div class="nav-right">
            <?php if (isset($_SESSION['customer_id'])): ?>
                <span>Welcome,
                    <?= htmlspecialchars($_SESSION['customer_name'] ?? 'Customer') ?>
                    <?= !empty($_SESSION['company']) ? ', ' . htmlspecialchars($_SESSION['company']) : '' ?>
                </span>
                <a href="logout.php" class="btn-outline">Logout</a>
            <?php else: ?>
                <a href="../login.php" class="btn-outline">Login</a>
            <?php endif; ?>
        </div>
        <div class="nav-right">
            <a href="cart.php" class="cart-icon">
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

    <div class="dashboard-container" style="max-width:1100px;">

        <h1 class="section-title" style="margin:2rem 0;">Your Shopping Cart</h1>

        <?php if (empty($cart_items)): ?>
            <div
                style="text-align:center; padding:4rem 0; background:white; border-radius:12px; box-shadow:0 4px 15px rgba(0,0,0,0.08);">
                <i class="fas fa-shopping-cart fa-5x" style="color:#bdc3c7; margin-bottom:1rem;"></i>
                <h3>Your cart is empty</h3>
                <p style="color:#777; margin:1rem 0;">Looks like you haven't added anything yet.</p>
                <a href="shop.php" class="btn-primary" style="padding:0.9rem 2rem;">Start Shopping</a>
            </div>
        <?php else: ?>
            <div class="cart-table-container"
                style="overflow-x:auto; background:white; border-radius:12px; box-shadow:0 4px 15px rgba(0,0,0,0.08);">
                <table class="cart-table">
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>Price</th>
                            <th>Quantity</th>
                            <th>Subtotal</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($cart_items as $item): ?>
                            <tr>
                                <td>
                                    <div style="display:flex; align-items:center; gap:1rem;">
                                        <!-- Placeholder image -->
                                        <img src="../images/<?= htmlspecialchars($item['fname']) ?>.png"
                                            alt="<?= htmlspecialchars($item['fname']) ?>"
                                            style="width:80px; height:80px; object-fit:cover; border-radius:6px;">
                                        <strong><?= htmlspecialchars($item['fname']) ?></strong>
                                    </div>
                                </td>
                                <td>$<?= number_format($item['fprice'], 2) ?></td>
                                <td>
                                    <form method="POST" action="" style="display:flex; align-items:center; gap:0.5rem;">
                                        <input type="hidden" name="fid" value="<?= $item['fid'] ?>">
                                        <input type="hidden" name="action" value="update">
                                        <input type="number" name="qty" value="<?= $item['qty'] ?>" min="1"
                                            style="width:70px; padding:0.4rem; text-align:center;">
                                        <button type="submit" class="btn-outline" style="padding:0.4rem 0.8rem;">
                                            Update
                                        </button>
                                    </form>
                                </td>
                                <td>$<?= number_format($item['subtotal'], 2) ?></td>
                                <td>
                                    <form method="POST" action="">
                                        <input type="hidden" name="fid" value="<?= $item['fid'] ?>">
                                        <input type="hidden" name="action" value="remove">
                                        <button type="submit" class="btn-outline" style="color:#e74c3c; border-color:#e74c3c;">
                                            Remove
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="3" style="text-align:right; font-weight:bold; padding:1rem;">
                                Total Amount:
                            </td>
                            <td colspan="2" style="font-weight:bold; font-size:1.3rem; color:#e67e22;">
                                $<?= number_format($total, 2) ?>
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <div style="text-align:right; margin-top:2rem;">
                <a href="checkout.php" class="btn-primary" style="padding:1rem 2.5rem; font-size:1.1rem;">
                    Proceed to Checkout →
                </a>
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