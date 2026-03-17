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
    // customer/product.php
    session_start();
    include '../connections/dbconn.php';

    $error = '';
    $product = null;

    // 1. Get and validate ID from URL
    if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
        $error = "Invalid product ID.";
    } else {
        $fid = (int) $_GET['id'];  // safe integer cast
    
        // 2. Fetch product with prepared statement (prevents SQL injection)
        $stmt = $conn->prepare("
        SELECT fid, fname, fdesc, fprice
        FROM Furnitures
        WHERE fid = ?
        LIMIT 1
    ");
        $stmt->bind_param("i", $fid);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 0) {
            $error = "Product not found.";
        } else {
            $product = $result->fetch_assoc();
        }

        $stmt->close();
    }

    // Optional: Fetch materials / stock info (if needed)
    $materials = [];
    if ($product && !$error) {
        $mat_stmt = $conn->prepare("
        SELECT m.mname, m.munit, fm.pmqty
        FROM FurnitureMaterials fm
        JOIN Materials m ON fm.mid = m.mid
        WHERE fm.fid = ?
        ORDER BY m.mname
    ");
        $mat_stmt->bind_param("i", $fid);
        $mat_stmt->execute();
        $materials_result = $mat_stmt->get_result();
        while ($row = $materials_result->fetch_assoc()) {
            $materials[] = $row;
        }
        $mat_stmt->close();
    }

    mysqli_close($conn);
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
                <p><a href="shop.php">← Back to Shop</a></p>
            </div>
        <?php elseif ($product): ?>

            <a href="shop.php" class="back-link" style="display:inline-block; margin-bottom:1rem;">← Back to Shop</a>

            <div style="display: flex; gap: 3rem; flex-wrap: wrap;">
                <!-- Image -->
                <div style="flex: 1; min-width: 300px;">
                    <?php if (!empty($product['fname'])): ?>
                        <img src="../images/<?= htmlspecialchars($product['fname']) ?>.png"
                            alt="<?= htmlspecialchars($product['fname']) ?>" class="product-image"
                            onerror="this.src='/images/placeholder.jpg';">
                    <?php else: ?>
                        <div
                            style="background:#f0f0f0; height:400px; display:flex; align-items:center; justify-content:center; border-radius:12px;">
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
                                    (<?= htmlspecialchars($mat['pmqty']) ?>             <?= htmlspecialchars($mat['munit']) ?>)
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>

                    <button class="btn-primary" style="margin-top:2rem; padding:1rem 2rem; font-size:1.1rem;">
                        <i class="fas fa-cart-plus"></i> Add to Cart
                    </button>
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