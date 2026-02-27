<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Login - Premium Living Furniture</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
</head>

<body>
    <?php
    // customer/login.php
    
    session_start();
    // If already logged in as customer, redirect to main shop / home
    $register_message = '';
    if (isset($_SESSION['register_success'])) {
        $register_message = $_SESSION['register_success'];
        unset($_SESSION['register_success']); // show only once
    }

    $error = '';
    $input_ctel = '';

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $ctel = trim($_POST['ctel'] ?? '');
        $cpassword = trim($_POST['cpassword'] ?? '');

        $input_ctel = $ctel; // for repopulating the form
    
        if (empty($ctel) || empty($cpassword)) {
            $error = "Please enter both Phone Number and Password.";
        } else {
            include '../connections/dbconn.php';

            // Using prepared statement (good practice)
            $stmt = $conn->prepare("
            SELECT cid, cname, cpassword, ctel, company
            FROM Customers 
            WHERE ctel = ?
        ");

            $stmt->bind_param("s", $ctel);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows === 0) {
                $error = "Phone number does not exist.";
            } else {
                $row = $result->fetch_assoc();

                // Plain text comparison (as per your current DB design)
                // In real projects → use password_verify()
                if (password_verify($cpassword, $row['cpassword'])) {
                    // Login successful
                    $_SESSION['customer_id'] = $row['cid'];
                    $_SESSION['customer_phone'] = $row['ctel'];
                    $_SESSION['customer_name'] = $row['cname'];
                    $_SESSION['customer_company'] = $row['company'];
                    // $_SESSION['customer_role'] = ... (not needed if no roles in Customers table)
    
                    header("Location: ../index.php");  // or "shop.php", "orders.php", etc.
                    exit();
                } else {
                    $error = "Incorrect password.";
                }
            }

            $stmt->close();
            mysqli_close($conn);
        }
    }
    ?>
    <?php if ($register_message): ?>
        <script>
            alert("<?= htmlspecialchars($register_message, ENT_QUOTES, 'UTF-8') ?>");
            // Optional: redirect again just in case (but header() already did it)
            // window.location.href = "login.php";
        </script>
<?php endif; ?>
    <div class="login-container">
        <div class="login-box">
            <div class="login-header">
                <h1>Customer Login</h1>
                <p>Sign in to your account</p>
            </div>

            <?php if (!empty($error)): ?>
                    <div class="error-message">
                        <?= htmlspecialchars($error) ?>
                    </div>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="form-group">
                    <label for="ctel">Phone Number</label>
                    <input type="text" id="ctel" name="ctel" required placeholder="Enter your phone number"
                        value="<?= htmlspecialchars($input_ctel) ?>">
                </div>

                <div class="form-group">
                    <label for="cpassword">Password</label>
                    <input type="password" id="cpassword" name="cpassword" required placeholder="Enter your password">
                </div>

                <button type="submit" class="btn-login">
                    <i class="fas fa-sign-in-alt"></i> Login
                </button>
            </form>

            <a href="../index.php" class="back-link">← Back to Home</a>
        </div>
    </div>

</body>

</html>