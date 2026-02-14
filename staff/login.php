<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staff Login - Premium Living Furniture</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
</head>

<body>
    <?php
    session_start();
    // staff/login.php
    if (isset($_SESSION['staff_id']) && isset($_SESSION['staff_role'])) {
        header("Location: dashboard.php");
        exit();
    }

    $error = '';
    $input_sid = '';

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $sid = trim($_POST['sid'] ?? '');
        $spassword = trim($_POST['spassword'] ?? '');

        $input_sid = $sid; // keep for form repopulation
    
        if (empty($sid) || empty($spassword)) {
            $error = "Please enter both Staff ID and Password.";
        } else {
            include '../connections/dbconn.php';

            // Use prepared statement to prevent SQL injection
            $stmt = $conn->prepare("
            SELECT sname, spassword, srole 
            FROM Staffs 
            WHERE sname = ?
        ");

            $stmt->bind_param("s", $sid);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows === 0) {
                // No staff with this ID
                $error = "Staff ID does not exist.";
            } else {
                $row = $result->fetch_assoc();

                // Compare password (plain text as per your current DB design)
                // In real system: use password_verify($spassword, $row['spassword'])
                if ($spassword === $row['spassword']) {
                    // Login successful
                    $_SESSION['staff_id'] = $row['sid'];
                    $_SESSION['staff_name'] = $row['sname'];
                    $_SESSION['staff_role'] = $row['srole'];

                    header("Location: dashboard.php");
                    exit();
                } else {
                    // Wrong password
                    $error = "Incorrect password.";
                }
            }

            $stmt->close();
            mysqli_close($conn);
        }
    }
    ?>
    <div class="login-container">
        <div class="login-box">
            <div class="login-header">
                <h1>Staff Login</h1>
                <p>Access the management system</p>
            </div>

            <?php if (!empty($error)): ?>
                <div class="error-message">
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="form-group">
                    <label for="sid">Staff ID</label>
                    <input type="text" id="sid" name="sid" required 
                           placeholder="Enter your Staff ID" 
                           value="<?= htmlspecialchars($input_sid) ?>">
                </div>

                <div class="form-group">
                    <label for="spassword">Password</label>
                    <input type="password" id="spassword" name="spassword" required 
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