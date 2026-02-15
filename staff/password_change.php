<?php
// staff/password_change.php

session_start();

// Must be logged in as admin
$role = isset($_SESSION['staff_role']) ? strtolower(trim($_SESSION['staff_role'])) : '';
$is_admin = ($role === 'admin' || $role === 'administrator');

if (!isset($_SESSION['staff_id']) || !$is_admin) {
    header("Location: login.php");
    exit();
}

include '../connections/dbconn.php';

$message = '';
$error = '';

// Get target staff ID from URL
$target_sid = isset($_POST['target_sid']) && (int) $_POST['target_sid'] > 0
    ? (int) $_POST['target_sid']
    : $_SESSION['staff_id'];

if ($target_sid <= 0) {
    $error = "Invalid staff ID.";
} else {
    // Verify staff exists
    $check = $conn->prepare("SELECT sname, srole FROM Staffs WHERE sid = ?");
    $check->bind_param("i", $target_sid);
    $check->execute();
    $result = $check->get_result();
    $staff = $result->fetch_assoc();
    $check->close();

    if (!$staff) {
        $error = "Staff member not found.";
    } elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['new_password'])) {
        $new_password = trim($_POST['new_password'] ?? '');
        $confirm_password = trim($_POST['confirm_password'] ?? '');

        if (empty($new_password) || empty($confirm_password)) {
            $error = "Both password fields are required.";
        } elseif ($new_password !== $confirm_password) {
            $error = "Passwords do not match.";
        } elseif (strlen($new_password) < 8) {
            $error = "New password must be at least 8 characters.";
        } else {
            $hashed = password_hash($new_password, PASSWORD_DEFAULT);

            $stmt = $conn->prepare("UPDATE Staffs SET spassword = ? WHERE sid = ?");
            $stmt->bind_param("si", $hashed, $target_sid);

            if ($stmt->execute()) {
                $message = "Password for <strong>" . htmlspecialchars($staff['sname']) . "</strong> has been updated successfully.";
            } else {
                $error = "Error updating password: " . $conn->error;
            }
            $stmt->close();
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
    <title>Change Staff Password - Premium Living Furniture</title>
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
        </nav>
        <div class="nav-right">
            <a href="logout.php" class="btn-outline logout-btn">Logout</a>
        </div>
    </header>

    <div class="dashboard-container" style="max-width:500px; margin:3rem auto;">

        <h1 class="section-title">Change Password</h1>

        <?php if ($message): ?>
            <div class="success-message">
                <?= $message ?>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="error-message">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <?php if (!$error && isset($staff)): ?>
            <p style="text-align:center; margin-bottom:2rem;">
                Resetting password for: <strong><?= htmlspecialchars($staff['sname']) ?></strong>
                <br><small>(Role: <?= htmlspecialchars(ucfirst($staff['srole'])) ?>)</small>
            </p>

            <div class="form-container" style="padding:2.5rem;">
                <form method="POST" action="">
                    <div class="form-group">
                        <label>New Password</label>
                        <input type="password" name="new_password" required minlength="8" autocomplete="new-password">
                    </div>

                    <div class="form-group">
                        <label>Confirm New Password</label>
                        <input type="password" name="confirm_password" required minlength="8" autocomplete="new-password">
                    </div>

                    <button type="submit" class="btn-login" style="width:100%; margin-top:1.5rem;">
                        <i class="fas fa-key"></i> Reset Password
                    </button>
                </form>

                <p style="text-align:center; margin-top:1.5rem;">
                    <a href="staff_manage.php">← Back to Staff List</a>
                </p>
            </div>
        <?php endif; ?>

    </div>

</body>

</html>