<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - Premium Living</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>

<body>
    <?php
    // customer/checkout.php
    session_start();

    if (!isset($_SESSION['customer_id'])) {
        header("Location: login.php");
        exit();
    }

    include '../connections/dbconn.php';

    $cid = $_SESSION['customer_id'];
    $error = $success = '';

    // Get saved address
    $stmt = $conn->prepare("SELECT cname, caddr FROM Customers WHERE cid = ?");
    $stmt->bind_param("i", $cid);
    $stmt->execute();
    $customer = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    $saved_name = $customer['cname'] ?? '';
    $saved_addr = $customer['caddr'] ?? '';

    // Load cart items
    $cart_items = [];
    $total = 0;

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
            $sub = $row['fprice'] * $qty;
            $total += $sub;

            $cart_items[] = [
                'fid' => $row['fid'],
                'fname' => $row['fname'],
                'price' => $row['fprice'],
                'qty' => $qty,
                'subtotal' => $sub
            ];
        }
        $stmt->close();
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($cart_items)) {
        $addr = trim($_POST['delivery_address'] ?? '');
        $deldate = trim($_POST['delivery_date'] ?? '');

        if (empty($addr))
            $error = "Delivery address is required.";
        elseif (empty($deldate))
            $error = "Delivery date is required.";
        else {
            $conn->begin_transaction();

            try {
                // Create order
                $stmt = $conn->prepare("
                INSERT INTO Orders 
                (odate, ototalamount, cid, odeliverydate, odeliveraddress, ostatus)
                VALUES (NOW(), ?, ?, ?, ?, 1)
            ");
                $stmt->bind_param("diss", $total, $cid, $deldate, $addr);
                $stmt->execute();
                $oid = $conn->insert_id;
                $stmt->close();

                // Insert order items + deduct materials
                foreach ($cart_items as $item) {
                    // order item
                    $stmt = $conn->prepare("
                    INSERT INTO OrderFurnitures (oid, fid, oqty)
                    VALUES (?, ?, ?)
                ");
                    $stmt->bind_param("iii", $oid, $item['fid'], $item['qty']);
                    $stmt->execute();
                    $stmt->close();

                    // deduct material stock
                    $mstmt = $conn->prepare("
                    SELECT mid, pmqty 
                    FROM furniturematerials 
                    WHERE fid = ?
                ");
                    $mstmt->bind_param("i", $item['fid']);
                    $mstmt->execute();
                    $mres = $mstmt->get_result();

                    while ($mat = $mres->fetch_assoc()) {
                        $deduct = $mat['pmqty'] * $item['qty'];

                        $ustmt = $conn->prepare("
                        UPDATE Materials 
                        SET mqty = mqty - ? 
                        WHERE mid = ? AND mqty >= ?
                    ");
                        $ustmt->bind_param("iii", $deduct, $mat['mid'], $deduct);
                        $ustmt->execute();

                        if ($ustmt->affected_rows === 0) {
                            throw new Exception("Not enough stock for material #{$mat['mid']}");
                        }
                        $ustmt->close();
                    }
                    $mstmt->close();
                }

                $conn->commit();

                unset($_SESSION['cart']);
                $success = "Order #$oid placed successfully!";
                header("Refresh: 3; url=orders.php");
            } catch (Exception $e) {
                $conn->rollback();
                $error = $e->getMessage();
            }
        }
    }

    $conn->close();
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
        <?php endif; ?>
            <a href="logout.php" class="btn-outline">Logout</a>
        </div>
        <div class="nav-right">
            <a href="../customer/cart.php" class="cart-icon">
                <i class="fas fa-shopping-cart"></i>
                <span class="cart-count"><?= array_sum($_SESSION['cart'] ?? []) ?></span>
            </a>
            <button class="menu-toggle" id="menuToggle">
                <i class="fas fa-bars"></i>
            </button>
        </div>
        </nav?>
    </header>

    <div class="dashboard-container">
        <h1 class="section-title">Checkout</h1>

        <?php if ($success): ?>
            <div class="success-message"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="error-message"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <?php if (empty($cart_items)): ?>
            <p class="text-center">Your cart is empty. <a href="shop.php">Continue shopping</a></p>
        <?php else: ?>

            <div class="checkout-grid">
                <!-- Cart summary -->
                <div class="cart-summary">
                    <h2>Your Order</h2>
                    <table class="cart-table">
                        <thead>
                            <tr>
                                <th>Item</th>
                                <th>Price</th>
                                <th>Qty</th>
                                <th>Subtotal</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($cart_items as $item): ?>
                                <tr>
                                    <td><?= htmlspecialchars($item['fname']) ?></td>
                                    <td>$<?= number_format($item['price'], 2) ?></td>
                                    <td><?= $item['qty'] ?></td>
                                    <td>$<?= number_format($item['subtotal'], 2) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="3"><strong>Total</strong></td>
                                <td><strong>$<?= number_format($total, 2) ?></strong></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <!-- Delivery form -->
                <div class="delivery-form">
                    <h2>Delivery Details</h2>
                    <form method="POST">
                        <div class="form-group">
                            <label>Full Name</label>
                            <input type="text" value="<?= htmlspecialchars($saved_name) ?>" disabled>
                        </div>

                        <div class="form-group">
                            <label>Delivery Address <span class="required">*</span></label>
                            <textarea name="delivery_address" rows="3"
                                required><?= htmlspecialchars($saved_addr) ?></textarea>
                        </div>

                        <div class="form-group">
                            <label>Delivery Date <span class="required">*</span></label>
                            <input type="date" name="delivery_date" required
                                min="<?= date('Y-m-d', strtotime('+1 day')) ?>">
                        </div>

                        <button type="submit" class="btn-primary btn-large">
                            <i class="fas fa-check"></i> Place Order
                        </button>
                    </form>

                    <p class="small text-center" style="margin-top:1.5rem;">
                        By placing order you agree to our <a href="#">Terms & Conditions</a>
                    </p>
                </div>
            </div>

        <?php endif; ?>
    </div>

    <footer>
        <!-- your footer -->
    </footer>

</body>

</html>