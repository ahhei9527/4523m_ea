<?php
// customer/login.php

session_start();

// If already logged in as customer, redirect to main shop / home
if (isset($_SESSION['customer_id'])) {
    header("Location: ../index.php");  // or "shop.php" if you have one
    exit();
}

$error = '';
$input_cname = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $cname     = trim($_POST['cname'] ?? '');
    $cpassword = trim($_POST['cpassword'] ?? '');

    $input_cname = $cname; // for repopulating the form

    if (empty($cname) || empty($cpassword)) {
        $error = "Please enter both Username and Password.";
    } else {
        include '../connections/dbconn.php';

        // Using prepared statement (good practice)
        $stmt = $conn->prepare("
            SELECT cid, cname, cpassword
            FROM Customers 
            WHERE cname = ?
        ");

        $stmt->bind_param("s", $cname);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 0) {
            $error = "Username does not exist.";
        } else {
            $row = $result->fetch_assoc();

            // Plain text comparison (as per your current DB design)
            // In real projects → use password_verify()
            if (password_verify($cpassword, $row['cpassword'])) {
                // Login successful
                $_SESSION['customer_id']   = $row['cid'];
                $_SESSION['customer_name'] = $row['cname'];
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

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Login - Premium Living Furniture</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>
</head>
<body>

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
                    <label for="cname">Username</label>
                    <input type="text" id="cname" name="cname" required
                           placeholder="Enter your username"
                           value="<?= htmlspecialchars($input_cname) ?>">
                </div>

                <div class="form-group">
                    <label for="cpassword">Password</label>
                    <input type="password" id="cpassword" name="cpassword" required
                           placeholder="Enter your password">
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