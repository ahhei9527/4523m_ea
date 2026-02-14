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
// staff/login.php

session_start();

// If already logged in, go to dashboard
if (isset($_SESSION['staff_id']) && isset($_SESSION['staff_role'])) {
    header("Location: dashboard.php");
    exit();
}

$error = '';
$input_sname = '';  // for repopulating the form

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $sname     = trim($_POST['sname'] ?? '');
    $spassword = trim($_POST['spassword'] ?? '');

    $input_sname = $sname;

    if (empty($sname) || empty($spassword)) {
        $error = "Please enter both Username and Password.";
    } else {
        include '../connections/dbconn.php';

        // Query by sname (username)
        $stmt = $conn->prepare("
            SELECT sid, sname, spassword, srole 
            FROM Staffs 
            WHERE sname = ?
        ");

        $stmt->bind_param("s", $sname);   // "s" = string
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 0) {
            $error = "Username does not exist.";
        } else {
            $row = $result->fetch_assoc();

            // Plain text comparison (temporary — upgrade to hashed later)
            if ($spassword === $row['spassword']) {
                // Success
                $_SESSION['staff_id']   = $row['sid'];
                $_SESSION['staff_name'] = $row['sname'];
                $_SESSION['staff_role'] = $row['srole'];

                header("Location: dashboard.php");
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
                    <label for="sname">Username</label>
                    <input type="text" id="sname" name="sname" required 
                           placeholder="Enter your username" 
                           value="<?= htmlspecialchars($input_sname) ?>">
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