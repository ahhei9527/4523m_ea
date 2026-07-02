<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($product['fname'] ?? 'Product Details') ?> - Premium Living</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
</head>

<body>
    <?php
// product.php
session_start();
include '../connections/dbconn.php';

$fid = (int)($_GET['id'] ?? 0);
$error = '';
$product = null;
$materials = [];

if ($fid > 0) {
    // Fetch product
    $stmt = $conn->prepare("SELECT fid, fname, fdesc, fprice FROM Furnitures WHERE fid = ?");
    $stmt->bind_param("i", $fid);
    $stmt->execute();
    $product = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$product) {
        $error = "Product not found.";
    } else {
        // Fetch materials
        $stmt = $conn->prepare("
            SELECT m.mname, fm.pmqty, m.munit 
            FROM FurnitureMaterials fm 
            JOIN Materials m ON fm.mid = m.mid 
            WHERE fm.fid = ?
        ");
        $stmt->bind_param("i", $fid);
        $stmt->execute();
        $materials = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        // Calculate stock (same as shop.php)
        $stmt = $conn->prepare("
            SELECT MIN(FLOOR(m.mqty / NULLIF(fm.pmqty, 0))) AS stock
            FROM FurnitureMaterials fm
            JOIN Materials m ON fm.mid = m.mid
            WHERE fm.fid = ?
        ");
        $stmt->bind_param("i", $fid);
        $stmt->execute();
        $stockResult = $stmt->get_result()->fetch_assoc();
        $stock = $stockResult ? (int)$stockResult['stock'] : 0;
        $stmt->close();
    }
} else {
    $error = "Invalid product ID.";
}

// === Handle Add to Cart ===
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_to_cart'])) {
    $post_fid = (int)($_POST['fid'] ?? 0);
    $qty = max(1, (int)($_POST['qty'] ?? 1));

    if ($post_fid > 0 && $qty > 0) {
        $_SESSION['cart'] ??= [];
        $_SESSION['cart'][$post_fid] = ($_SESSION['cart'][$post_fid] ?? 0) + $qty;
        
        // Optional: success message
        $_SESSION['success'] = "Added to cart!";
    }

    header("Location: product.php?id=" . $post_fid);
    exit;
}

$conn->close();
?>
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
            <?php if (isset($_COOKIE['customer_id'])): ?>
                <span>Welcome,
                    <?= htmlspecialchars($_COOKIE['customer_name'] ?? 'Customer') ?>
                    <?= !empty($_COOKIE['company']) ? ', ' . htmlspecialchars($_COOKIE['company']) : '' ?>
                </span>
                <a href="logout.php" class="btn-outline">Logout</a>
            <?php else: ?>
                <a href="../login.php" class="btn-outline">Login</a>
            <?php endif; ?>
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

<div class="product-detail">

    <?php if ($error): ?>
        <div class="error-box">
            <?= htmlspecialchars($error) ?>
            <p><a href="../index.php">← Back to Home</a></p>
        </div>
    <?php elseif ($product): ?>

        <a href="../index.php" class="back-link" style="display:inline-block; margin-bottom:1rem;">← Back to Home</a>

        <div style="display: flex; gap: 3rem; flex-wrap: wrap;">
            <!-- Image -->
            <div style="flex: 1; min-width: 300px;">
                <?php if (!empty($product['fname'])): ?>
                    <img src="../images/<?= htmlspecialchars($product['fname']) ?>.png"
                        alt="<?= htmlspecialchars($product['fname']) ?>" class="product-image"
                        onerror="this.src='../images/placeholder.jpg';">
                <?php else: ?>
                    <div style="background:#f0f0f0; height:400px; display:flex; align-items:center; justify-content:center; border-radius:12px;">
                        <span style="color:#999; font-size:1.3rem;">No image available</span>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Details -->
            <div class="product-info" style="flex: 1.5; min-width: 350px;">
                <h1><?= htmlspecialchars($product['fname']) ?></h1>
                <div class="price">$<?= number_format($product['fprice'], 2) ?></div>

                <h3>Description</h3>
                <p><?= nl2br(htmlspecialchars($product['fdesc'])) ?></p>

                <?php if (!empty($materials)): ?>
                    <h3>Required Materials</h3>
                    <ul class="materials-list">
                        <?php foreach ($materials as $mat): ?>
                            <li>
                                <?= htmlspecialchars($mat['mname']) ?>
                                (<?= htmlspecialchars($mat['pmqty']) ?> <?= htmlspecialchars($mat['munit']) ?>)
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
                <form method="POST" style="margin-top: 2rem;">
                    <input type="hidden" name="fid" value="<?= $product['fid'] ?>">
                    <input type="hidden" name="add_to_cart" value="1">

                    <div style="display: flex; align-items: center; gap: 1rem; flex-wrap: wrap;">
                        <div>
                            <label for="qty"><strong>Quantity:</strong></label><br>
                            <input type="number" name="qty" id="qty" value="1" min="1" 
                                   max="<?= $stock ?? 999 ?>" 
                                   style="width: 80px; padding: 0.6rem; text-align: center; font-size: 1.1rem;">
                        </div>

                        <button type="submit" class="btn-primary" 
                                style="padding:1rem 2rem; font-size:1.1rem; margin-top: 1.5rem;">
                            <i class="fas fa-cart-plus"></i> Add to Cart
                        </button>
                    </div>
                </form>

            </div>
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
            <p>© <?= date("Y") ?> Premium Living Furniture Co. Ltd. All rights reserved.</p>
        </div>
    </footer>

</body>

</html>