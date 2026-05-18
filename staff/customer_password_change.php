<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Change Customer Password</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
</head>

<body>
    <?php
    // staff/password_change.php
    // Must be logged in as admin
    $role = isset($_COOKIE['staff_role']) ? strtolower(trim($_COOKIE['staff_role'])) : '';
    $is_admin = ($role === 'admin' || $role === 'Administrator');

    // === AUTO EXTEND COOKIES ON ANY ACTIVITY ===
    if (isset($_COOKIE['staff_id'])) {
        $staff_id   = $_COOKIE['staff_id'];
        $staff_name = $_COOKIE['staff_name'] ?? 'Staff';
        $staff_role = $_COOKIE['staff_role'] ?? '';

        // Renew cookies (extend lifetime)
        setcookie('staff_id',   $staff_id,   time() + 1200, "/", "", false, true);
        setcookie('staff_name', $staff_name, time() + 1200, "/", "", false, true);
        setcookie('staff_role', $staff_role, time() + 1200, "/", "", false, true);
    } else {
        header("Location: login.php");
        exit();
    }

    include '../connections/dbconn.php';

    $message = '';
    $error = '';

    // Get target customer ID from GET (from link) or POST (after form submit)
    $target_cid = isset($_GET['cid']) ? (int) $_GET['cid'] :
        (isset($_POST['target_cid']) ? (int) $_POST['target_cid'] : 0);

    if ($target_cid <= 0) {
        $error = "Invalid or missing customer ID.";
    } else {
        // Verify customer exists
        $check = $conn->prepare("SELECT cid, cname FROM Customers WHERE cid = ?");
        $check->bind_param("i", $target_cid);
        $check->execute();
        $result = $check->get_result();
        $customer = $result->fetch_assoc();
        $check->close();

        if (!$customer) {
            $error = "Customer not found.";
        } elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['new_password'])) {
            $new_password = trim($_POST['new_password'] ?? '');
            $confirm_password = trim($_POST['confirm_password'] ?? '');

            if (empty($new_password) || empty($confirm_password)) {
                $error = "Both password fields are required.";
            } elseif ($new_password !== $confirm_password) {
                $error = "Passwords do not match.";
            } elseif (strlen($new_password) < 6) {
                $error = "New password must be at least 6 characters.";
            } else {
                $hashed = password_hash($new_password, PASSWORD_ARGON2ID, [
                    'memory_cost' => 65536,
                    'time_cost' => 4,
                    'threads' => 1
                ]);

                $stmt = $conn->prepare("UPDATE Customers SET cpassword = ? WHERE cid = ?");
                $stmt->bind_param("si", $hashed, $target_cid);

                if ($stmt->execute()) {
                    $message = "Password for <strong>" . htmlspecialchars($customer['cname']) . "</strong> has been updated successfully.";
                } else {
                    $error = "Error updating password: " . $stmt->error;
                }
                $stmt->close();
            }
        }
    }

    mysqli_close($conn);
    ?>
    <header class="navbar">
        <div class="logo">
            <h2>Premium Living</h2>
            <small>Staff Area</small>
        </div>
        <nav class="nav-links">
            <a href="dashboard.php">Dashboard</a>
            <a href="furniture_add.php">Add Furniture</a>
            <a href="materials_add.php">Materials</a>
            <a href="orders_manage.php">Orders</a>
            <a href="report.php">Reports</a>
            <a href="staff_manage.php">Manage Staff</a>
            <a href="customers_view.php" class="active">Customers</a>
        </nav>
        <div class="nav-right">
            <span style="color:#ecf0f1; margin-right:1.2rem;">
                <?= htmlspecialchars($_COOKIE['staff_name'] ?? 'Admin') ?>
                (<?= htmlspecialchars(ucfirst($_COOKIE['staff_role'] ?? 'Staff')) ?>)
            </span>
            <a href="logout.php" class="btn-outline logout-btn">Logout</a>
        </div>
    </header>

    <div class="dashboard-container" style="max-width:500px; margin:3rem auto;">

        <h1 class="section-title">Change Customer Password</h1>

        <?php if ($message): ?>
            <div class="success-message">
                <?= $message ?>
                <p style="margin-top:1rem;">
                    <a href="customers_view.php" class="btn-outline">← Back to Customer List</a>
                </p>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="error-message">
                <?= htmlspecialchars($error) ?>
                <p style="margin-top:1rem;">
                    <a href="customers_view.php" class="btn-outline">← Back to Customer List</a>
                </p>
            </div>
        <?php endif; ?>

        <?php if (!$error && isset($customer)): ?>
            <p style="text-align:center; margin-bottom:2rem; font-size:1.1rem;">
                Resetting password for customer:
                <strong><?= htmlspecialchars($customer['cname']) ?></strong>
                (ID: <?= $target_cid ?>)
            </p>

            <div class="form-container"
                style="padding:2.5rem; background:white; border-radius:12px; box-shadow:0 4px 15px rgba(0,0,0,0.08);">
                <form method="POST" action="">
                    <input type="hidden" name="target_cid" value="<?= $target_cid ?>">

                    <div class="form-group">
                        <label>New Password</label>
                        <input type="password" name="new_password" required minlength="6" autocomplete="new-password">
                    </div>

                    <div class="form-group">
                        <label>Confirm New Password</label>
                        <input type="password" name="confirm_password" required minlength="6" autocomplete="new-password">
                    </div>

                    <button type="submit" class="btn-login" style="width:100%; margin-top:1.5rem;">
                        <i class="fas fa-key"></i> Reset Password
                    </button>
                </form>

                <p style="text-align:center; margin-top:1.5rem;">
                    <a href="customers_view.php">← Back to Customer List</a>
                </p>
            </div>
        <?php endif; ?>

    </div>
</body>

</html>