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
    if (isset($_SESSION["customer_id"])) {
        header("Location: ../index.php");
        exit();
    }
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $action = $_POST['action'] ?? '';

        if ($action === 'add') {
            $cname = trim($_POST['cname'] ?? '');
            $cpass = trim($_POST['cpass'] ?? '');
            $cemail = trim($_POST['cemail'] ?? '');
            $ctel = trim($_POST['ctel'] ?? '');

            if (empty($cname) || empty($cpass) || empty($cemail)) {
                $error = "Name, password and email are required.";
            } elseif (strlen($cpass) < 6) {
                $error = "Password must be at least 6 characters.";
            } else {
                $hashed = password_hash($cpass, PASSWORD_DEFAULT);

                $stmt = $conn->prepare("
                INSERT INTO Customers (cname, cpassword, ctel, caddr)
                VALUES (?, ?, ?, ?)
            ");
                $stmt->bind_param("ssss", $cname, $hashed,$caddr, $ctel);

                if ($stmt->execute()) {
                    $message = "New customer registered successfully.";
                } else {
                    $error = "Error registering customer: " . $conn->error;
                }
                $stmt->close();
            }
        } else {
            $error = "Invalid action.";
        }
    }
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
            <a href="../login.php" class="btn-outline logout-btn">Login</a>
        </div>
    </header>
    <div class="form-container">
        <h2>New Customer Registration</h2>
        <form method="POST" action="">
            <input type="hidden" name="action" value="add">

            <div class="form-group">
                <label>Full Name</label>
                <input type="text" name="cname" required placeholder="e.g. Chan Tai Man">
            </div>

            <div class="form-group">
                <label>Password</label>
                <input type="password" name="cpass" required minlength="6" placeholder="Minimum 6 characters">
            </div>
            <div class="form-group">
                <label>Address</label>
                <input type="text" name="caddress" placeholder="e.g. 123 Main Street">
            </div>
            <div class="form-group">
                <label>Phone Number (optional)</label>
                <input type="text" name="ctel" placeholder="e.g. 9123 4567">
            </div>
            <button type="submit" class="btn-login" style="width:auto; padding:0.9rem 2rem;">
                <i class="fas fa-user-plus"></i> Register
            </button>
        </form>
    </div>

</body>

</html>