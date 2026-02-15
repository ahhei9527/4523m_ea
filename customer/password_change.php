<?php
session_start();

// Must be logged in as staff
if (!isset($_SESSION['customer_id'])) {
    header("Location: login.php");
    exit();
}

include '../connections/dbconn.php';

$message = '';
$error   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $old_password     = trim($_POST['old_password'] ?? '');
    $new_password     = trim($_POST['new_password'] ?? '');
    $confirm_password = trim($_POST['confirm_password'] ?? '');

    if (empty($old_password) || empty($new_password) || empty($confirm_password)) {
        $error = "All fields are required.";
    } elseif ($new_password !== $confirm_password) {
        $error = "New passwords do not match.";
    } elseif (strlen($new_password) < 8) {  // stronger minimum
        $error = "New password must be at least 8 characters.";
    } else {
        // Fetch current hashed password
        $stmt = $conn->prepare("SELECT cpassword FROM Customers WHERE cid = ?");
        $stmt->bind_param("i", $_SESSION['customer_id']);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();

        if (!$row || !password_verify($old_password, $row['cpassword'])) {
            $error = "Current password is incorrect.";
        } else {
            // Hash new password
            $hashed = password_hash($new_password, PASSWORD_DEFAULT);

            // Update
            $update = $conn->prepare("UPDATE Customers SET cpassword = ? WHERE cid = ?");
            $update->bind_param("si", $hashed, $_SESSION['customer_id']);

            if ($update->execute()) {
                $message = "Password updated successfully. Please log in again.";
                // Optional: force logout after change
                session_destroy();
                header("Location: login.php");
                exit();
            } else {
                $error = "Error updating password: " . $conn->error;
            }
            $update->close();
        }
    }
}

mysqli_close($conn);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Change Password - Premium Living Furniture</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
</head>
<body>

<header class="navbar">
    <div class="logo">
        <h2>Premium Living</h2>
        <small>Staff Area</small>
    </div>
    <nav class="nav-links">
        <a href="dashboard.php">Dashboard</a>
        <a href="staff_manage.php">Manage Staff</a>
        <!-- Add other links -->
    </nav>
    <div class="nav-right">
        <a href="logout.php" class="btn-outline logout-btn">Logout</a>
    </div>
</header>

<div class="dashboard-container" style="max-width:500px; margin:3rem auto;">

    <h1 class="section-title">Change Your Password</h1>

    <?php if ($message): ?>
        <div class="success-message">
            <?= htmlspecialchars($message) ?>
        </div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="error-message">
            <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <div class="form-container" style="padding:2.5rem;">
        <form method="POST" action="">
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

            <button type="submit" class="btn-login" style="width:100%; margin-top:1.5rem;">
                <i class="fas fa-key"></i> Update Password
            </button>
        </form>

        <p style="text-align:center; margin-top:1.5rem; color:#777;">
            <a href="index.php">← Back to Home</a>
        </p>
    </div>

</div>

</body>
</html>