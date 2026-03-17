<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Premium Living Furniture</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
</head>

<body>
    <?php
    if (isset($_cookie['customer_id'])) {
        header("Location: ../index.php");  // or "shop.php" if you have one
        exit();
    }
    if(isset($_cookie['staff_id'])) {
        header("Location: staff/dashboard.php");
        exit();
    }
    if (isset($_POST['staff_login'])) {
        header("Location: staff/login.php");
        exit();
    } elseif (isset($_POST['customer_login'])) {
        header("Location: customer/login.php");
        exit();
    } elseif (isset($_POST['customer_register'])) {
        header("Location: customer/register.php");
        exit();
    } 
    ?>
    <div class="login-container">
        <div class="login-box">
            <div class="login-header">
                <h1>Login</h1>
                <p>Choose your login type:</p>
            </div>

            <?php if (!empty($error)): ?>
                <div class="error-message">
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="form-group">
                    <button type="submit" class="btn-login" name="staff_login">
                        <i class="fas fa-sign-in-alt"></i> Staff Login
                    </button>
                    <br /><br />
                    <button type="submit" class="btn-login" name="customer_login">
                        <i class="fas fa-sign-in-alt"></i> Customer Login
                    </button>
                    <br /><br />
                    <button type="submit" class="btn-login" name="customer_register">
                        <i class="fas fa-user-plus"></i> Customer Register
                    </button>
            </form>

            <a href="index.php" class="back-link">← Back to Home</a>
        </div>
    </div>
    </div>
</body>

</html>