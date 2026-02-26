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
<?php
// customer/shop.php
session_start();
include '../connections/dbconn.php';

// Sorting
$sort = $_GET['sort'] ?? 'price_asc';

$valid_sorts = [
    'price_asc'   => ['fprice ASC',  'Price: Low to High'],
    'price_desc'  => ['fprice DESC', 'Price: High to Low'],
    'name_asc'    => ['fname ASC',   'Name: A to Z'],
    'name_desc'   => ['fname DESC',  'Name: Z to A'],
    'stock_desc'  => ['stock DESC',  'In Stock (High to Low)'],
    'stock_asc'   => ['stock ASC',   'In Stock (Low to High)'],
];

$sort = isset($valid_sorts[$sort]) ? $sort : 'price_asc';
$order_by = $valid_sorts[$sort][0];

// Fetch products
$sql = "SELECT fid, fname, fdesc, fprice, stock FROM Furnitures ORDER BY $order_by";
$stmt = $conn->prepare($sql);
$stmt->execute();
$products = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Add to cart
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_to_cart'])) {
    $fid = (int)($_POST['fid'] ?? 0);
    $qty = max(1, (int)($_POST['qty'] ?? 1));

    if ($fid > 0 && $qty > 0) {
        $_SESSION['cart'] ??= [];
        $_SESSION['cart'][$fid] = ($_SESSION['cart'][$fid] ?? 0) + $qty;
    }

    $redirect = 'shop.php' . (isset($_GET['sort']) ? '?sort=' . urlencode($_GET['sort']) : '');
    header("Location: $redirect");
    exit;
}

$conn->close();
?>
<header class="navbar">
    <div class="logo"><h2>Premium Living</h2></div>
    <nav class="nav-links">
        <a href="../index.php">Home</a>
        <a href="shop.php" class="active">Shop</a>
        <a href="orders.php">My Orders</a>
        <a href="profile.php">Profile</a>
    </nav>
    <div class="nav-right">
        <?php if (!isset($_SESSION['customer_id'])): ?>
            <a href="../login.php" class="btn-outline">Login / Register</a>
        <?php else: ?>
            <a href="logout.php" class="btn-outline">Logout</a>
            <span>Welcome, <?= htmlspecialchars($_SESSION['customer_name'] ?? '') ?></span>
        <?php endif; ?>
    </div>
    <div class="nav-right">
        <a href="cart.php" class="cart-icon">
            <i class="fas fa-shopping-cart"></i>
            <span class="cart-count"><?= array_sum($_SESSION['cart'] ?? []) ?></span>
        </a>
        <button class="menu-toggle" id="menuToggle"><i class="fas fa-bars"></i></button>
    </div>
</header>

<div class="dashboard-container">
    <h1 class="section-title" style="margin:2rem 0;">All Furniture</h1>

    <!-- Sort -->
    <div style="display:flex; justify-content:flex-end; margin-bottom:2rem;">
        <form method="GET" style="display:flex; align-items:center; gap:0.8rem;">
            <label for="sort"><strong>Sort by:</strong></label>
            <select name="sort" id="sort" onchange="this.form.submit()" 
                    style="padding:0.5rem 1rem; border-radius:6px; border:1px solid #ccc; min-width:220px;">
                <?php foreach ($valid_sorts as $key => $info): ?>
                    <option value="<?= $key ?>" <?= $sort === $key ? 'selected' : '' ?>>
                        <?= htmlspecialchars($info[1]) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </form>
    </div>

    <?php if (empty($products)): ?>
        <p style="text-align:center; color:#777; padding:4rem 0;">No products available.</p>
    <?php else: ?>
        <div class="product-grid">
            <?php foreach ($products as $product): ?>
                <?php 
                    $stock = (int)($product['stock'] ?? 0);
                    $outOfStock = $stock <= 0;
                ?>
                <div class="product-card <?= $outOfStock ? 'out-of-stock' : '' ?>">
                    <div class="product-image">
                        <img src="../images/<?= htmlspecialchars($product['fname']) ?>.png"
                             alt="<?= htmlspecialchars($product['fname']) ?>"
                             style="width:100%; height:200px; object-fit:cover; border-radius:6px;">
                        <div class="price-tag">$<?= number_format($product['fprice'], 2) ?></div>
                    </div>
                    <div class="product-info">
                        <h3><?= htmlspecialchars($product['fname']) ?></h3>
                        <p class="desc"><?= htmlspecialchars(substr($product['fdesc'] ?? '', 0, 80)) ?>...</p>

                        <?php if ($stock > 0 && $stock <= 5): ?>
                            <p style="color:#e67e22; font-weight:bold; margin:0.5rem 0;">
                                Only <?= $stock ?> left!
                            </p>
                        <?php elseif ($outOfStock): ?>
                            <p style="color:#c0392b; font-weight:bold; margin:0.5rem 0;">
                                Out of Stock
                            </p>
                        <?php endif; ?>

                        <form method="POST">
                            <input type="hidden" name="fid" value="<?= $product['fid'] ?>">
                            <input type="hidden" name="add_to_cart" value="1">
                            <div style="display:flex; align-items:center; gap:0.8rem; justify-content:center; margin-top:1rem;">
                                <input type="number" name="qty" value="1" min="1" max="<?= $stock ?>" 
                                       <?= $outOfStock ? 'disabled' : '' ?>
                                       style="width:60px; padding:0.4rem; text-align:center;">
                                <button type="submit" class="btn-primary" 
                                        style="padding:0.6rem 1.2rem;" 
                                        <?= $outOfStock ? 'disabled' : '' ?>>
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
<?php ob_end_flush(); ?>
</body>
</html>