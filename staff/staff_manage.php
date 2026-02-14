<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Staff - Premium Living Furniture</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
</head>

<body>
    <?php
    // staff/staff_manage.php
    
    session_start();

    // Must be logged in AND admin
    if (isset($_SESSION['staff_role']) && strtolower($_SESSION['staff_role']) === 'administrator'
    ) {
        // User is logged in and is an administrator
    } else {
        header("Location: dashboard.php");
        exit();
    }

    include '../connections/dbconn.php';

    $message = '';
    $error = '';

    // =======================================
// HANDLE FORM SUBMISSIONS
// =======================================
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $action = $_POST['action'] ?? '';

        if ($action === 'add') {
            $sname = trim($_POST['sname'] ?? '');
            $spassword = trim($_POST['spassword'] ?? '');
            $srole = trim($_POST['srole'] ?? 'staff');
            $stel = trim($_POST['stel'] ?? '');

            if (empty($sname) || empty($spassword) || empty($srole)) {
                $error = "Name, password and role are required.";
            } else {
                $hashed = password_hash($spassword, PASSWORD_DEFAULT);

                $stmt = $conn->prepare("
                INSERT INTO Staffs (sname, spassword, srole, stel, sstatus)
                VALUES (?, ?, ?, ?, 1)
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
            $current = (int) ($_POST['current_status'] ?? 1);
            $new_status = $current ? 0 : 1;

            if ($sid > 0 && $sid !== $_SESSION['staff_id']) {  // prevent self-deactivation
                $stmt = $conn->prepare("UPDATE Staffs SET sstatus = ? WHERE sid = ?");
                $stmt->bind_param("ii", $new_status, $sid);
                if ($stmt->execute()) {
                    $message = "Staff status updated.";
                } else {
                    $error = "Update failed.";
                }
                $stmt->close();
            } else {
                $error = "Cannot modify your own account this way.";
            }
        }
    }

    // =======================================
// LOAD ALL STAFF (except perhaps current user highlight)
// =======================================
    
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
            <small style="color:#bdc3c7;">Staff Area</small>
        </div>
        <nav class="nav-links">
            <a href="dashboard.php">Dashboard</a>
            <a href="furniture_add.php">Add Furniture</a>
            <a href="materials_add.php">Materials</a>
            <a href="orders_manage.php">Orders</a>
            <a href="report.php">Reports</a>
            <a href="staff_manage.php" class="active">Manage Staff</a>
        </nav>
        <div class="nav-right">
            <span style="color:#ecf0f1; margin-right:1.2rem;">
                <?= htmlspecialchars($_SESSION['staff_name'] ?? 'Admin') ?> (Admin)
            </span>
            <a href="logout.php" class="btn-outline logout-btn">Logout</a>
        </div>
    </header>

    <div class="dashboard-container">

        <h1 class="section-title" style="margin-top:1.5rem;">Staff Management</h1>

        <?php if ($message): ?>
            <div
                style="background:#e8f5e9; color:#2e7d32; padding:1rem; border-radius:8px; margin:1rem 0; text-align:center;">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div
                style="background:#ffebee; color:#c62828; padding:1rem; border-radius:8px; margin:1rem 0; text-align:center;">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <!-- Add New Staff Form -->
        <div
            style="background:white; padding:2rem; border-radius:12px; box-shadow:0 4px 15px rgba(0,0,0,0.08); margin-bottom:2.5rem;">
            <h2 style="margin-top:0; color:#2c3e50;">Add New Staff Member</h2>
            <form method="POST" action="">
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
                        <option value="Administrator">Admin</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Phone Number (optional)</label>
                    <input type="text" name="stel" placeholder="e.g. 9123 4567">
                </div>

                <button type="submit" class="btn-login" style="width:auto; padding:0.9rem 2rem;">
                    <i class="fas fa-user-plus"></i> Create Staff
                </button>
            </form>
        </div>

        <!-- Staff List -->
        <h2 style="color:#2c3e50; margin:2.5rem 0 1rem;">Current Staff Members</h2>

        <?php if (empty($staff_list)): ?>
            <p style="text-align:center; color:#777;">No staff records found.</p>
        <?php else: ?>
            <div style="overflow-x:auto;">
                <table
                    style="width:100%; border-collapse:collapse; background:white; box-shadow:0 4px 15px rgba(0,0,0,0.08); border-radius:8px; overflow:hidden;">
                    <thead>
                        <tr style="background:#2c3e50; color:white;">
                            <th style="padding:1rem;">Name</th>
                            <th style="padding:1rem;">Role</th>
                            <th style="padding:1rem;">Phone</th>
                            <th style="padding:1rem;">Status</th>
                            <th style="padding:1rem;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($staff_list as $staff): ?>
                            <tr style="border-bottom:1px solid #eee;">
                                <td style="padding:1rem;"><?= htmlspecialchars($staff['sname']) ?></td>
                                <td style="padding:1rem;"><?= htmlspecialchars(ucfirst($staff['srole'])) ?></td>
                                <td style="padding:1rem;"><?= htmlspecialchars($staff['stel'] ?: '—') ?></td>
                                <td style="padding:1rem;">
                                    <span style="color: <?= $staff['sstatus'] ? '#2e7d32' : '#c62828' ?>; font-weight:bold;">
                                        <?= $staff['sstatus'] ? 'Active' : 'Inactive' ?>
                                    </span>
                                </td>
                                <td style="padding:1rem; text-align:center;">
                                    <form method="POST" action="" style="display:inline;">
                                        <input type="hidden" name="action" value="toggle_status">
                                        <input type="hidden" name="sid" value="<?= $staff['sid'] ?>">
                                        <input type="hidden" name="current_status" value="<?= $staff['sstatus'] ?>">
                                        <button type="submit"
                                            style="background:none; border:none; color:<?= $staff['sstatus'] ? '#d32f2f' : '#388e3c' ?>; cursor:pointer; font-weight:bold;">
                                            <?= $staff['sstatus'] ? 'Deactivate' : 'Activate' ?>
                                        </button>
                                    </form>

                                    <?php if ($staff['sid'] === $_SESSION['staff_id']): ?>
                                        <span style="color:#999; font-size:0.9rem;">(You)</span>
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