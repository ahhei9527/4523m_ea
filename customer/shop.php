<?php
// customer/shop.php

session_start();

// Redirect if not logged in (optional - remove if shop is public)
if (!isset($_SESSION['customer_id'])) {
    header("Location: login.php");
    exit();
}

include '../connections/dbconn.php';

// Handle sort
$sort = $_GET['sort'] ?? 'price_asc';
$order_by = ($sort === 'price_desc') ? 'fprice DESC' : 'fprice ASC';

$stmt = $conn->prepare("
    SELECT fid, fname, fdesc, fprice
    FROM Furnitures
    ORDER BY $order_by
");
$stmt->execute();
$result = $stmt->get_result();

$products = [];
while ($row = $result->fetch_assoc()) {
    $products[] = $row;
}
$stmt->close();

// Handle add to cart
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_to_cart'])) {
    $fid = (int) $_POST['fid'];
    $qty = (int) ($_POST['qty'] ?? 1);

    if ($fid > 0 && $qty > 0) {
        if (!isset($_SESSION['cart'])) {
            $_SESSION['cart'] = [];
        }
        $_SESSION['cart'][$fid] = ($_SESSION['cart'][$fid] ?? 0) + $qty;
    }
    header("Location: shop.php");
    exit();
}

mysqli_close($conn);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shop - Premium Living Furniture</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>

<body>

    <header class="navbar">
        <div class="logo">
            <h2>Premium Living</h2>
        </div>
        <nav class="nav-links">
            <a href="../index.php">Home</a>
            <a href="shop.php" class="active">Shop</a>
            <a href="orders.php">My Orders</a>
            <a href="profile.php">Profile</a>
        </nav>
        <div class="nav-right">
            <span>Welcome, <?= htmlspecialchars($_SESSION['customer_name'] ?? 'Guest') ?></span>
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
    </header>

    <div class="dashboard-container">

        <h1 class="section-title" style="margin:2rem 0;">All Furniture</h1>

        <!-- Sort -->
        <div style="display:flex; justify-content:flex-end; margin-bottom:2rem;">
            <form method="GET" action="">
                <label>Sort by price:</label>
                <select name="sort" onchange="this.form.submit()">
                    <option value="price_asc" <?= ($sort ?? 'price_asc') === 'price_asc' ? 'selected' : '' ?>>Low to High
                    </option>
                    <option value="price_desc" <?= ($sort ?? 'price_asc') === 'price_desc' ? 'selected' : '' ?>>High to Low
                    </option>
                </select>
            </form>
        </div>

        <?php if (empty($products)): ?>
            <p style="text-align:center; color:#777; padding:4rem 0;">No products available at the moment.</p>
        <?php else: ?>
            <div class="product-grid">
                <?php foreach ($products as $product): ?>
                    <div class="product-card">
                        <div class="product-image">
                            <img src="../images/<?= htmlspecialchars($product['fname']) ?>.png"
                                alt="<?= htmlspecialchars($product['fname']) ?>"
                                style="width:100%; height:200px; object-fit:cover; border-radius:6px;">
                            <div class="price-tag">$<?= number_format($product['fprice'], 2) ?></div>
                        </div>
                        <div class="product-info">
                            <h3><?= htmlspecialchars($product['fname']) ?></h3>
                            <p class="desc">
                                <?= htmlspecialchars(substr($product['fdesc'] ?? 'Beautiful furniture piece', 0, 80)) ?>...
                            </p>

                            <form method="POST" action="" class="add-to-cart-form">
                                <input type="hidden" name="fid" value="<?= $product['fid'] ?>">
                                <input type="hidden" name="add_to_cart" value="1">
                                <div
                                    style="display:flex; align-items:center; gap:0.8rem; justify-content:center; margin-top:1rem;">
                                    <input type="number" name="qty" value="1" min="1"
                                        style="width:60px; padding:0.4rem; text-align:center;">
                                    <button type="submit" class="btn-primary" style="padding:0.6rem 1.2rem;">
                                        <i class="fas fa-cart-plus"></i> Add to Cart
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
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
                    <li><a href="customer/shop.php">Shop</a></li>
                    <li><a href="customer/orders.php">Orders</a></li>
                    <li><a href="customer/profile.php">My Account</a></li>
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