<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Premium Living Furniture - Luxury & Comfort</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
</head>

<body>
    <?php
    include("connections/dbconn.php");
    session_start();

    $query = "
    SELECT f.fid, f.fname, f.fdesc, f.fprice,
           GROUP_CONCAT(m.mname SEPARATOR ', ') AS materials 
    FROM Furnitures f
    LEFT JOIN FurnitureMaterials fm ON f.fid = fm.fid
    LEFT JOIN Materials m ON fm.mid = m.mid
    GROUP BY f.fid
    ORDER BY f.fprice DESC
    LIMIT 6
";
    $result = mysqli_query($conn, $query);
    $featured = [];

    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $featured[] = $row;
        }
    } else {
        echo "<p style='color:red; text-align:center;'>Database error: " . mysqli_error($conn) . "</p>";
    }
    ?>
    <!-- Navigation Bar -->
    <header class="navbar">
        <div class="logo">
            <h2>Premium Living</h2>
        </div>
        <nav class="nav-links">
            <a href="index.php" class="active">Home</a>
            <a href="customer/shop.php">Shop</a>
            <a href="customer/orders.php">My Orders</a>
            <a href="customer/profile.php">Profile</a>
        </nav>
        <div class="nav-right">
            <?php if (isset($_COOKIE['customer_id'])): ?>
                <span class="welcome-text">
                    Welcome, <?= htmlspecialchars($_COOKIE['customer_name'] ?? 'Customer') ?>
                    <?= !empty($_COOKIE['customer_company']) ? ', ' . htmlspecialchars($_COOKIE['customer_company']) : '' ?>
                </span>
                <a href="customer/logout.php" class="btn-outline">Logout</a>
            <?php else: ?>
                <a href="login.php" class="btn-outline">Login</a>
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

    <!-- Hero Section -->
    <section class="hero">
        <div class="hero-content">
            <h1>Transform Your Home with Timeless Elegance</h1>
            <p>Discover premium furniture crafted with care and style</p>
            <a href="customer/shop.php" class="btn-primary">Shop Now</a>
        </div>
    </section>

    <!-- Featured Products -->
    <section class="featured-products">
        <div class="section-title">
            <h2>Featured Collections</h2>
            <p>Our most loved pieces this season</p>
        </div>

        <div class="product-grid">
            <?php if (empty($featured)): ?>
                <p style="text-align:center; grid-column: 1 / -1; padding: 4rem 0;">
                    No featured products available yet.
                </p>
            <?php else: ?>
                <?php foreach ($featured as $item): ?>
                    <div class="product-card">
                        <div class="product-image">
                            <a href="customer/product.php?id=<?= $item['fid'] ?>"
                                title="View <?= htmlspecialchars($item['fname']) ?>">
                                <?php
                                $img_src = !empty($item['fimage'])
                                    ? '/' . htmlspecialchars($item['fimage'])
                                    : 'images/' . htmlspecialchars($item['fname']) . '.png';
                                ?>
                                <img src="<?= $img_src ?>" alt="<?= htmlspecialchars($item['fname']) ?>"
                                    style="width:100%; height:200px; object-fit:cover; border-radius:6px;"
                                    onerror="this.src='/images/placeholder.jpg'; this.alt='Image not found';">
                            </a>
                            <div class="price-tag">$<?= number_format($item['fprice'], 2) ?></div>
                        </div>

                        <div class="product-info">
                            <h3><?= htmlspecialchars($item['fname']) ?></h3>
                            <p class="desc">
                                <?= htmlspecialchars(substr($item['fdesc'] ?? 'Elegant and modern design', 0, 80)) ?>...
                            </p>

                            <?php if (!empty($item['materials'])): ?>
                                <p class="materials" style="color:#777; font-size:0.9rem; margin:0.5rem 0;">
                                    Materials: <?= htmlspecialchars($item['materials']) ?>
                                </p>
                            <?php endif; ?>

                            <a href="customer/product.php?id=<?= $item['fid'] ?>" class="btn-view">View Details</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <div class="center-btn">
            <a href="customer/shop.php" class="btn-outline">View All Products</a>
        </div>
    </section>

    <!-- Footer -->
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

    <script src="js/main.js"></script>
</body>

</html>

<?php
mysqli_close($conn);
?>