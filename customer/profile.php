<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - Premium Living Furniture</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
</head>

<body>
    <?php
    // customer/profile.php
    
    session_start();

    if (!isset($_COOKIE['customer_id'])) {
        header("Location: login.php");
        exit();
    }

    include '../connections/dbconn.php';

    $customer_id = $_COOKIE['customer_id'];
    $message = '';
    $error = '';

    // Fetch current customer info
    $stmt = $conn->prepare("
    SELECT cname, ctel, caddr, company 
    FROM Customers 
    WHERE cid = ?
");
    $stmt->bind_param("i", $customer_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $customer = $result->fetch_assoc();
    $stmt->close();

    // Handle profile update
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
        $cname = trim($_POST['cname'] ?? '');
        $ctel = trim($_POST['ctel'] ?? '');
        $caddr = trim($_POST['caddr'] ?? '');
        $company = trim($_POST['company'] ?? '');

        if (empty($cname)) {
            $error = "Name is required.";
        } else {
            $update = $conn->prepare("
            UPDATE Customers 
            SET cname = ?, ctel = ?, caddr = ?, company = ?
            WHERE cid = ?
        ");
            $update->bind_param("ssssi", $cname, $ctel, $caddr, $company, $customer_id);

            if ($update->execute()) {
                $_COOKIE['customer_name'] = $cname;
                $message = "Profile updated successfully.";
            } else {
                $error = "Update failed: " . $conn->error;
            }
            $update->close();
        }
    }

    // Handle password change
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
        $old_pass = trim($_POST['old_password'] ?? '');
        $new_pass = trim($_POST['new_password'] ?? '');
        $confirm = trim($_POST['confirm_password'] ?? '');

        if (empty($old_pass) || empty($new_pass) || empty($confirm)) {
            $error = "All password fields are required.";
        } elseif ($new_pass !== $confirm) {
            $error = "New passwords do not match.";
        } elseif (strlen($new_pass) < 8) {
            $error = "New password must be at least 8 characters.";
        } else {
            // Verify old password
            $check = $conn->prepare("SELECT cpassword FROM Customers WHERE cid = ?");
            $check->bind_param("i", $customer_id);
            $check->execute();
            $result = $check->get_result();
            $row = $result->fetch_assoc();
            $check->close();

            if (!$row || !password_verify($old_pass, $row['cpassword'])) {
                $error = "Current password is incorrect.";
            } else {
                $hashed = password_hash($new_pass, PASSWORD_DEFAULT);

                $update = $conn->prepare("UPDATE Customers SET cpassword = ? WHERE cid = ?");
                $update->bind_param("si", $hashed, $customer_id);

                if ($update->execute()) {
                    $message = "Password changed successfully. Please log in again.";
                    // Optional: force logout
                    setcookie("customer_id", $row['cid'], time() - 120);
                    setcookie("customer_name", $row['cname'], time() - 120);
                    setcookie("customer_company", $row['company'], time() - 120);
                    header("Location: login.php");
                    exit(); // Ensure no further processing occurs
                } else {
                    $error = "Error changing password: " . $conn->error;
                }
                $update->close();
            }
        }
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
            <a href="orders.php">My Orders</a>
            <a href="profile.php" class="active">Profile</a>
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

    <div class="dashboard-container" style="max-width:700px; margin:2rem auto;">

        <h1 class="section-title" style="margin:2rem 0;">My Profile</h1>

        <?php if ($message): ?>
            <div class="success-message" style="margin-bottom:1.5rem;">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="error-message" style="margin-bottom:1.5rem;">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <!-- Profile Info & Edit Form -->
        <div class="form-container"
            style="background:white; padding:2rem; border-radius:12px; box-shadow:0 4px 15px rgba(0,0,0,0.08);">
            <h3>Personal Information</h3>
            <form method="POST" action="">
                <input type="hidden" name="update_profile" value="1">

                <div class="form-group">
                    <label>Full Name</label>
                    <input type="text" name="cname" value="<?= htmlspecialchars($customer['cname'] ?? '') ?>" required>
                </div>

                <div class="form-group">
                    <label>Phone Number</label>
                    <input type="text" name="ctel" value="<?= htmlspecialchars($customer['ctel'] ?? '') ?>">
                </div>

                <div class="form-group">
                    <label>Address</label>
                    <textarea name="caddr" rows="3"><?= htmlspecialchars($customer['caddr'] ?? '') ?></textarea>
                </div>

                <div class="form-group">
                    <label>Company (optional)</label>
                    <input type="text" name="company" value="<?= htmlspecialchars($customer['company'] ?? '') ?>">
                </div>

                <button type="submit" class="btn-primary" style="width:100%; margin-top:1.5rem;">
                    <i class="fas fa-save"></i> Update Profile
                </button>
            </form>
        </div>

        <!-- Change Password -->
        <div class="form-container"
            style="background:white; padding:2rem; border-radius:12px; box-shadow:0 4px 15px rgba(0,0,0,0.08); margin-top:2rem;">
            <h3>Change Password</h3>
            <form method="POST" action="">
                <input type="hidden" name="change_password" value="1">

                <div class="form-group">
                    <label>Current Password</label>
                    <input type="password" name="old_password" required autocomplete="current-password">
                </div>

                <div class="form-group">
                    <label>New Password</label>
                    <input type="password" name="new_password" required minlength="8" autocomplete="new-password">
                </div>

                <div class="form-group">
                    <label>Confirm New Password</label>
                    <input type="password" name="confirm_password" required minlength="8" autocomplete="new-password">
                </div>

                <button type="submit" class="btn-primary" style="width:100%; margin-top:1.5rem;">
                    <i class="fas fa-key"></i> Change Password
                </button>
            </form>
        </div>

        <div style="text-align:center; margin-top:3rem;">
            <a href="shop.php" class="btn-outline">Continue Shopping</a>
        </div>

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