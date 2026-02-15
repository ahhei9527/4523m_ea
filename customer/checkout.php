<?php
// customer/checkout.php

session_start();

if (!isset($_SESSION['customer_id'])) {
    header("Location: login.php");
    exit();
}

include '../connections/dbconn.php';

$customer_id = $_SESSION['customer_id'];
$message = '';
$error = '';

// Fetch customer saved address (for pre-fill)
$customer_stmt = $conn->prepare("SELECT caddr FROM Customers WHERE cid = ?");
$customer_stmt->bind_param("i", $customer_id);
$customer_stmt->execute();
$customer_result = $customer_stmt->get_result();
$customer = $customer_result->fetch_assoc();
$customer_stmt->close();

$saved_address = $customer['caddr'] ?? '';

// Cart processing
$cart_items = [];
$total = 0.00;

if (!empty($_SESSION['cart'])) {
    $fids = array_keys($_SESSION['cart']);
    $placeholders = implode(',', array_fill(0, count($fids), '?'));

    $stmt = $conn->prepare("
        SELECT fid, fname, fprice, fdesc
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

// Process checkout
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_SESSION['cart'])) {
    $delivery_address = trim($_POST['delivery_address'] ?? '');
    $delivery_date = trim($_POST['delivery_date'] ?? '');

    if (empty($delivery_address)) {
        $error = "Delivery address is required.";
    } elseif (empty($delivery_date)) {
        $error = "Delivery date is required.";
    } else {
        $conn->begin_transaction();

        try {
            // Create order
            $stmt_order = $conn->prepare("
                INSERT INTO Orders (odate, ototalamount, cid, odeliverydate, odeliveraddress, ostatus)
                VALUES (NOW(), ?, ?, ?, ?, 1)
            ");
            $stmt_order->bind_param("disi", $total, $customer_id, $delivery_date, $delivery_address);
            $stmt_order->execute();
            $order_id = $conn->insert_id;
            $stmt_order->close();

            // Add order items & deduct material stock
            foreach ($_SESSION['cart'] as $fid => $qty) {
                $stmt_item = $conn->prepare("
                    INSERT INTO OrderFurnitures (oid, fid, oqty)
                    VALUES (?, ?, ?)
                ");
                $stmt_item->bind_param("iii", $order_id, $fid, $qty);
                $stmt_item->execute();
                $stmt_item->close();

                $mat_stmt = $conn->prepare("
                    SELECT fm.mid, fm.pmqty
                    FROM FurnitureMaterials fm
                    WHERE fm.fid = ?
                ");
                $mat_stmt->bind_param("i", $fid);
                $mat_stmt->execute();
                $mat_result = $mat_stmt->get_result();

                while ($mat = $mat_result->fetch_assoc()) {
                    $deduct_qty = $mat['pmqty'] * $qty;
                    $update_mat = $conn->prepare("
                        UPDATE Materials 
                        SET mqty = mqty - ? 
                        WHERE mid = ? AND mqty >= ?
                    ");
                    $update_mat->bind_param("iii", $deduct_qty, $mat['mid'], $deduct_qty);
                    $update_mat->execute();

                    if ($update_mat->affected_rows === 0) {
                        throw new Exception("Insufficient stock for material ID " . $mat['mid']);
                    }
                    $update_mat->close();
                }
                $mat_stmt->close();
            }

            $conn->commit();

            unset($_SESSION['cart']);

            $message = "Order #$order_id placed successfully! Thank you.";
            header("Refresh: 3; url=orders.php");
        } catch (Exception $e) {
            $conn->rollback();
            $error = "Order failed: " . $e->getMessage();
        }
    }
}

mysqli_close($conn);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - Premium Living Furniture</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
</head>

<body>

    <header class="navbar">
        <div class="logo">
            <h2>Premium Living</h2>
        </div>
        <nav class="nav-links">
            <a href="../index.php">Home</a>
            <a href="shop.php">Shop</a>
            <a href="orders.php">My Orders</a>
            <a href="profile.php">Profile</a>
            <a href="cart.php">Cart</a>
        </nav>
        <div class="nav-right">
            <span>Welcome, <?= htmlspecialchars($_SESSION['customer_name'] ?? 'Guest') ?></span>
            <a href="logout.php" class="btn-outline">Logout</a>
        </div>
    </header>

    <div class="dashboard-container" style="max-width:900px;">

        <h1 class="section-title" style="margin:2rem 0;">Checkout</h1>

        <?php if ($message): ?>
            <div class="success-message" style="margin-bottom:2rem;">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="error-message" style="margin-bottom:2rem;">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <?php if (empty($_SESSION['cart'])): ?>
            <p style="text-align:center; padding:3rem 0; color:#777;">
                Your cart is empty. <a href="shop.php">Continue shopping</a>.
            </p>
        <?php else: ?>
            <!-- Cart summary -->
            <div
                style="background:white; border-radius:12px; box-shadow:0 4px 15px rgba(0,0,0,0.08); padding:1.5rem; margin-bottom:2rem;">
                <h3>Your Order Summary</h3>
                <table class="cart-table" style="width:100%; margin-top:1rem;">
                    <thead>
                        <tr>
                            <th>Item</th>
                            <th>Price</th>
                            <th>Qty</th>
                            <th>Subtotal</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        // Re-open connection for cart summary
                        include '../connections/dbconn.php';

                        foreach ($_SESSION['cart'] as $fid => $qty):
                            $stmt = $conn->prepare("SELECT fname, fprice FROM Furnitures WHERE fid = ?");
                            $stmt->bind_param("i", $fid);
                            $stmt->execute();
                            $row = $stmt->get_result()->fetch_assoc();
                            $subtotal = $row['fprice'] * $qty;
                            ?>
                            <tr>
                                <td><?= htmlspecialchars($row['fname']) ?></td>
                                <td>$<?= number_format($row['fprice'], 2) ?></td>
                                <td><?= $qty ?></td>
                                <td>$<?= number_format($subtotal, 2) ?></td>
                            </tr>
                            <?php
                            $stmt->close();
                        endforeach;

                        mysqli_close($conn);
                        ?>
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="3" style="text-align:right; font-weight:bold;">Total:</td>
                            <td style="font-weight:bold; color:#e67e22;">$<?= number_format($total, 2) ?></td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <!-- Checkout form -->
            <div class="form-container"
                style="background:white; padding:2rem; border-radius:12px; box-shadow:0 4px 15px rgba(0,0,0,0.08);">
                <h3>Delivery Information</h3>
                <form method="POST" action="">
                    <div class="form-group">
                        <label>Delivery Address *</label>
                        <textarea name="delivery_address" required rows="3"
                            style="width:100%; padding:0.8rem; border:1px solid #ddd; border-radius:6px;">
                                <?= htmlspecialchars($saved_address) ?>
                            </textarea>
                    </div>

                    <div class="form-group">
                        <label for="delivery_date">Delivery Date & Time *</label>
                        <input type="datetime-local" id="delivery_date" name="delivery_date" required
                            min="<?= date('Y-m-d\T09:00', strtotime('+3 days')) ?>"
                            max="<?= date('Y-m-d\T18:00', strtotime('+7 days')) ?>">

                        <small style="color:#777; display:block; margin-top:6px;">
                            Delivery available only **3–7 days from today** and **between 09:00 – 18:00**.
                        </small>
                    </div>

                    <div style="text-align:right; margin-top:2rem;">
                        <button type="submit" class="btn-primary" style="padding:1rem 2.5rem; font-size:1.1rem;">
                            Place Order
                        </button>
                    </div>
                </form>
            </div>
        <?php endif; ?>

    </div>

    <script src="../js/main.js"></script>
</body>

</html>