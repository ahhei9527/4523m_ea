<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Registration - Premium Living Furniture</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
</head>

<body>
    <?php
    session_start();
    include '../connections/dbconn.php';
    $message = '';
    $error = '';
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $action = $_POST['action'] ?? '';
        if ($action === 'add') {
            $cname = trim($_POST['cname'] ?? '');
            $cpass = trim($_POST['cpass'] ?? '');
            $caddress = trim($_POST['caddress'] ?? '');
            $cemail = trim($_POST['cemail'] ?? '');
            $ctel = trim($_POST['ctel'] ?? '');
            $company = trim($_POST['company'] ?? '');
            if (empty($cname) || empty($cpass) || empty($caddress) || empty($cemail)) {
                $error = "Name, password, address and email are required.";
            } elseif (strlen($cpass) < 6) {
                $error = "Password must be at least 6 characters.";
            } else {
                $check = $conn->prepare("SELECT cid FROM Customers WHERE cemail = ?");
                $check->bind_param("s", $cemail);
                $check->execute();
                if ($check->get_result()->num_rows > 0) {
                    $error = "This email is already registered.";
                } else {
                    $hashed = password_hash($cpass, PASSWORD_DEFAULT);

                    $stmt = $conn->prepare("
                INSERT INTO Customers (cname, cpassword, caddr, cemail, ctel, company)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
                    $stmt->bind_param("ssssss", $cname, $hashed, $caddress, $cemail, $ctel, $company);

                    if ($stmt->execute()) {
                        $_SESSION['register_success'] = "Registration successful! Please log in.";
                        header("Location: login.php");  // or your customer login page
                        exit();
                    } else {
                        $error = "Registration failed: " . $conn->error;
                    }
                    $stmt->close();
                }
                $check->close();
            }
        }
    }
    mysqli_close($conn);
    ?>
    <header class="navbar">
    <div class="logo">
        <h2>Premium Living</h2>
        <small>Customer Registration</small>
    </div>
    <nav class="nav-links">
        <a href="../index.php">Home</a>
    </nav>
    <div class="nav-right">
        <a href="login.php" class="btn-outline">Login</a>
    </div>
</header>

<div class="dashboard-container" style="max-width:600px; margin:2rem auto;">

    <?php if (isset($_SESSION['register_success'])): ?>
        <div class="success-message">
            <?= htmlspecialchars($_SESSION['register_success']) ?>
        </div>
        <?php unset($_SESSION['register_success']); ?>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="error-message">
            <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <div class="form-container" style="background:white; padding:2.5rem; border-radius:12px; box-shadow:0 4px 20px rgba(0,0,0,0.1);">
        <h2>New Customer Registration</h2>
        <form method="POST" action="">
            <input type="hidden" name="action" value="add">

            <div class="form-group">
                <label>Full Name *</label>
                <input type="text" name="cname" required placeholder="e.g. Chan Tai Man">
            </div>

            <div class="form-group">
                <label>Password *</label>
                <input type="password" name="cpass" required minlength="6" placeholder="Minimum 6 characters">
            </div>

            <div class="form-group">
                <label>Address *</label>
                <input type="text" name="caddress" required placeholder="e.g. Flat 12A, Tower 3, Kowloon">
            </div>

            <div class="form-group">
                <label>Email *</label>
                <input type="email" name="cemail" required placeholder="e.g. user@example.com">
            </div>

            <div class="form-group">
                <label>Phone Number (optional)</label>
                <input type="tel" name="ctel" placeholder="e.g. 9123 4567">
            </div>

            <div class="form-group">
                <label>Company (optional)</label>
                <input type="text" name="ccompany" placeholder="e.g. ABC Company">
            </div>

            <button type="submit" class="btn-login" style="width:100%; margin-top:1.5rem;">
                <i class="fas fa-user-plus"></i> Register
            </button>
        </form>

        <p style="text-align:center; margin-top:1.5rem;">
            Already have an account? 
            <a href="login.php" style="color:#e67e22; font-weight:500;">Login here</a>
        </p>
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