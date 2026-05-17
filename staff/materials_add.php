<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Materials - Premium Living Furniture</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
</head>

<body>
    <?php
    // staff/materials_add.php
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
    
    $staff_name = $_COOKIE['staff_name'] ?? 'Staff';
    $is_admin = (isset($_COOKIE['staff_role']) && $_COOKIE['staff_role'] == "Administrator");
    
    include '../connections/dbconn.php';

    $role = isset($_COOKIE['staff_role']) ? strtolower(trim($_COOKIE['staff_role'])) : '';
    $is_admin = ($role === 'admin' || $role === 'administrator');
    $message = '';
    $error = '';

    // Handle form submission - Add new material
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_material'])) {
        $mname = trim($_POST['mname'] ?? '');
        $mqty = (int) ($_POST['mqty'] ?? 0);
        $munit = trim($_POST['munit'] ?? '');

        if (empty($mname) || empty($munit)) {
            $error = "Material name and unit are required.";
        } elseif ($mqty < 0) {
            $error = "Quantity cannot be negative.";
        } else {
            $stmt = $conn->prepare("
            INSERT INTO Materials (mname, mqty, munit)
            VALUES (?, ?, ?)
        ");
            $stmt->bind_param("sis", $mname, $mqty, $munit);

            if ($stmt->execute()) {
                $message = "Material '$mname' added successfully.";
            } else {
                $error = "Error adding material: " . $conn->error;
            }
            $stmt->close();
        }
    }

    // Handle stock update
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_stock'])) {
        $mid = (int) $_POST['mid'];
        $new_qty = (int) $_POST['mqty'];

        if ($new_qty < 0) {
            $error = "Stock quantity cannot be negative.";
        } else {
            $stmt = $conn->prepare("
            UPDATE Materials 
            SET mqty = ? 
            WHERE mid = ?
        ");
            $stmt->bind_param("ii", $new_qty, $mid);

            if ($stmt->execute()) {
                $message = "Stock updated successfully.";
            } else {
                $error = "Error updating stock: " . $conn->error;
            }
            $stmt->close();
        }
    }

    // Handle delete material
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_material'])) {
        $mid = (int) $_POST['mid'];

        // Check if material is used in any furniture (safety)
        $check = $conn->prepare("SELECT COUNT(*) AS cnt FROM FurnitureMaterials WHERE mid = ?");
        $check->bind_param("i", $mid);
        $check->execute();
        $result = $check->get_result();
        $row = $result->fetch_assoc();
        $check->close();

        if ($row['cnt'] > 0) {
            $error = "Cannot delete this material — it is used in one or more furniture products.";
        } else {
            $stmt = $conn->prepare("DELETE FROM Materials WHERE mid = ?");
            $stmt->bind_param("i", $mid);

            if ($stmt->execute()) {
                $message = "Material deleted successfully.";
            } else {
                $error = "Error deleting material: " . $conn->error;
            }
            $stmt->close();
        }
    }

    // Load all materials
    $materials = [];
    $result = $conn->query("
    SELECT mid, mname, mqty, munit
    FROM Materials
    ORDER BY mname ASC
");
    while ($row = $result->fetch_assoc()) {
        $materials[] = $row;
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
            <a href="materials_add.php" class="active">Materials</a>
            <a href="orders_manage.php">Orders</a>
            <a href="report.php">Reports</a>
            <?php if ($is_admin): ?>
                <a href="staff_manage.php">Manage Staff</a>
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

        <h1 class="section-title" style="margin:2rem 0;">Manage Materials</h1>

        <?php if ($message): ?>
            <div class="success-message"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="error-message"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <!-- Add New Material Form -->
        <div class="form-container"
            style="background:white; padding:2rem; border-radius:12px; box-shadow:0 4px 15px rgba(0,0,0,0.08); margin-bottom:3rem;">
            <h2>Add New Material</h2>
            <form method="POST" action="">
                <input type="hidden" name="add_material" value="1">

                <div class="form-group">
                    <label>Material Name *</label>
                    <input type="text" name="mname" required placeholder="e.g. Oak Wood, Stainless Steel">
                </div>

                <div class="form-group">
                    <label>Initial Quantity *</label>
                    <input type="number" name="mqty" required min="0" value="0" step="1">
                </div>

                <div class="form-group">
                    <label>Unit *</label>
                    <input type="text" name="munit" required placeholder="e.g. kg, m, pieces">
                </div>

                <button type="submit" class="btn-primary" style="margin-top:1rem;">
                    <i class="fas fa-plus"></i> Add Material
                </button>
            </form>
        </div>

        <!-- Existing Materials List -->
        <h2 style="margin:2rem 0 1rem; color:#2c3e50;">Current Materials Inventory</h2>

        <?php if (empty($materials)): ?>
            <p style="text-align:center; color:#777; padding:2rem;">No materials found.</p>
        <?php else: ?>
            <div style="overflow-x:auto;">
                <table class="staff-table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Current Quantity</th>
                            <th>Unit</th>
                            <th>Update Stock</th>
                            <th>Delete</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($materials as $material): ?>
                            <tr>
                                <td><?= htmlspecialchars($material['mname']) ?></td>
                                <td><?= htmlspecialchars($material['mqty']) ?></td>
                                <td><?= htmlspecialchars($material['munit']) ?></td>

                                <!-- Update Stock -->
                                <td>
                                    <form method="POST" action="" style="display:flex; gap:0.5rem; align-items:center;">
                                        <input type="hidden" name="mid" value="<?= $material['mid'] ?>">
                                        <input type="hidden" name="update_stock" value="1">
                                        <input type="number" name="mqty" value="<?= $material['mqty'] ?>" min="0"
                                            style="width:90px; padding:0.4rem;">
                                        <button type="submit" class="btn-outline" style="padding:0.4rem 0.8rem;">
                                            Update
                                        </button>
                                    </form>
                                </td>

                                <!-- Delete -->
                                <td>
                                    <form method="POST" action=""
                                        onsubmit="return confirm('Delete material <?= htmlspecialchars(addslashes($material['mname'])) ?>? This cannot be undone.');">
                                        <input type="hidden" name="mid" value="<?= $material['mid'] ?>">
                                        <input type="hidden" name="delete_material" value="1">
                                        <button type="submit" class="btn-outline" style="color:#dc3545; border-color:#dc3545;">
                                            Delete
                                        </button>
                                    </form>
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