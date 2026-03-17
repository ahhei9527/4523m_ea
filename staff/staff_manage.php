<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Staff - Premium Living Furniture</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
</head>

<body>
    <?php
    // staff/staff_manage.php
    // Must be logged in AND admin (flexible check for 'admin' or 'Administrator')
    $role = isset($_COOKIE['staff_role']) ? strtolower(trim($_COOKIE['staff_role'])) : '';
    $is_admin = ($role === 'admin' || $role === 'administrator');

    if (!isset($_COOKIE['staff_id']) || !$is_admin) {
        header("Location: dashboard.php");
        exit();
    }

    include '../connections/dbconn.php';

    $staff_name = $_COOKIE['staff_name'] ?? 'Staff';
    $is_admin = (isset($_COOKIE['staff_role']) && $_COOKIE['staff_role'] == "admin");
    $message = '';
    $error = '';

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $action = $_POST['action'] ?? '';

        if ($action === 'add') {
            $sname = trim($_POST['sname'] ?? '');
            $spassword = trim($_POST['spassword'] ?? '');
            $srole = trim($_POST['srole'] ?? 'staff');
            $stel = trim($_POST['stel'] ?? '');

            if (empty($sname) || empty($spassword) || empty($srole)) {
                $error = "Name, password and role are required.";
            } elseif (strlen($spassword) < 6) {
                $error = "Password must be at least 6 characters.";
            } else {
                $hashed = password_hash($spassword, PASSWORD_DEFAULT);

                $stmt = $conn->prepare("
                INSERT INTO Staffs (sname, spassword, srole, stel)
                VALUES (?, ?, ?, ?)
            ");
                $stmt->bind_param("ssss", $sname, $hashed, $srole, $stel);

                if ($stmt->execute()) {
                    $message = "New staff member added successfully.";
                } else {
                    $error = "Error adding staff: " . $conn->error;
                }
                $stmt->close();
            }
        } elseif ($action === 'toggle_status') {
            $sid = (int) ($_POST['sid'] ?? 0);

            // Prevent changing your own account
            if ($sid === (int) $_COOKIE['staff_id']) {
                $error = "You are not allowed to change your own status.";
            } elseif ($sid > 0) {
                $stmt = $conn->prepare("SELECT sstatus FROM Staffs WHERE sid = ?");
                $stmt->bind_param("i", $sid);
                $stmt->execute();
                $result = $stmt->get_result();
                $row = $result->fetch_assoc();
                $stmt->close();

                if ($row) {
                    $current = (int) $row['sstatus'];
                    $new_status = $current ? 0 : 1;

                    $update = $conn->prepare("UPDATE Staffs SET sstatus = ? WHERE sid = ?");
                    $update->bind_param("ii", $new_status, $sid);
                    if ($update->execute()) {
                        $message = "Staff status updated successfully.";
                    } else {
                        $error = "Update failed: " . $conn->error;
                    }
                    $update->close();
                } else {
                    $error = "Staff member not found.";
                }
            } else {
                $error = "Invalid staff ID.";
            }
        }
    }

    // Load staff list
    $staff_list = [];
    $result = mysqli_query($conn, "
    SELECT sid, sname, srole, stel
    FROM Staffs 
    ORDER BY sname ASC
");

    while ($row = mysqli_fetch_assoc($result)) {
        $staff_list[] = $row;
    }

    mysqli_close($conn);
    ?>
    <header class="navbar">
        <div class="logo">
            <h2>Premium Living</h2>
            <small style="color:#bdc3c7; font-size:0.9rem;">Staff Area</small>
        </div>
        <nav class="nav-links">
            <a href="dashboard.php">Dashboard</a>
            <a href="furniture_add.php">Add Furniture</a>
            <a href="materials_add.php">Materials</a>
            <a href="orders_manage.php">Orders</a>
            <a href="report.php">Reports</a>
            <?php if ($is_admin): ?>
                <a href="staff_manage.php" class="active">Manage Staff</a>
                <a href="customers_view.php">Customers</a>
            <?php endif; ?>
        </nav>
        <div class="nav-right">
            <span style="color:#ecf0f1; margin-right:1.2rem;">
                <?= htmlspecialchars($staff_name) ?>
                <?php if ($is_admin): ?><small style="color:#e67e22;">(Administrator)</small><?php endif; ?>
            </span>
            <a href="logout.php" class="btn-outline logout-btn">Logout</a>
        </div>
    </header>

    <div class="dashboard-container">

        <h1 class="section-title" style="margin-top:1.5rem;">Staff Management</h1>

        <?php if ($message): ?>
            <div class="success-message"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="error-message"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <!-- Add New Staff Form -->
        <div class="form-container">
            <h2>Add New Staff Member</h2>
            <form method="POST">
                <input type="hidden" name="action" value="add">
                <div class="form-group">
                    <label>Full Name</label>
                    <input type="text" name="sname" required placeholder="e.g. Chan Tai Man">
                </div>
                <div class="form-group">
                    <label>Password</label>
                    <input type="password" name="spassword" required minlength="6">
                </div>
                <div class="form-group">
                    <label>Role</label>
                    <select name="srole" required>
                        <option value="staff">Staff</option>
                        <option value="admin">Admin</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Phone (optional)</label>
                    <input type="text" name="stel" placeholder="e.g. 9123 4567">
                </div>
                <button type="submit" class="btn-login" style="width:auto; padding:0.9rem 2rem;">
                    <i class="fas fa-user-plus"></i> Create Staff
                </button>
            </form>
        </div>

        <!-- Staff List -->
        <h2 style="margin: 3rem 0 1.2rem; color:#2c3e50;">Existing Staff Members</h2>

        <?php if (empty($staff_list)): ?>
            <p style="text-align:center; color:#777; padding:2rem;">No staff records found.</p>
        <?php else: ?>
            <div style="overflow-x:auto;">
                <table class="staff-table">
                    <thead>
                        <tr>
                            <th>SID</th>
                            <th>Name</th>
                            <th>Role</th>
                            <th>Phone</th>
                            <th>Password</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($staff_list as $staff): ?>
                            <tr class="<?= $staff['sid'] == $_COOKIE['staff_id'] ? 'current-user-row' : '' ?>">
                                <td><?= htmlspecialchars($staff['sid']) ?></td>
                                <td><?= htmlspecialchars($staff['sname']) ?></td>
                                <td><?= htmlspecialchars(ucfirst($staff['srole'])) ?></td>
                                <td><?= htmlspecialchars($staff['stel'] ?: '—') ?></td>
                                <td style="text-align:center;">
                                    <?php if ($is_admin): ?>
                                        <form method="POST" action="password_change.php" style="display:inline;">
                                            <input type="hidden" name="target_sid" value="<?= $staff['sid'] ?>">
                                            <button type="submit" class="btn-outline toggle-btn">
                                                Change Password
                                            </button>
                                        </form>
                                    <?php else: ?>
                                        —
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>

    </div>

</body>

</html>