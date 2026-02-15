<?php
// staff/furniture_add.php

session_start();

// Must be logged in as staff
if (!isset($_SESSION['staff_id'])) {
    header("Location: login.php");
    exit();
}

// Prevent any accidental output before headers
ob_start();

include '../connections/dbconn.php';

$message = '';
$error = '';

// Fetch all materials for dropdown
$materials = [];
$result = $conn->query("SELECT mid, mname, munit FROM Materials ORDER BY mname ASC");
while ($row = $result->fetch_assoc()) {
    $materials[] = $row;
}

// Handle form submission - Add new furniture
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_furniture'])) {
    $fname = trim($_POST['fname'] ?? '');
    $fdesc = trim($_POST['fdesc'] ?? '');
    $fprice = (float)($_POST['fprice'] ?? 0);

    // Handle image upload
    $fimage = '';
    $upload_dir = '../images/furniture/';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }

    if (isset($_FILES['fimage']) && $_FILES['fimage']['error'] === UPLOAD_ERR_OK) {
        $file_name = $_FILES['fimage']['name'];
        $file_tmp  = $_FILES['fimage']['tmp_name'];
        $file_ext  = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        $allowed   = ['jpg', 'jpeg', 'png', 'gif'];

        if (!in_array($file_ext, $allowed)) {
            $error = "Only JPG, JPEG, PNG & GIF files are allowed.";
        } elseif ($_FILES['fimage']['size'] > 20 * 1024 * 1024) {
            $error = "Image file size must be less than 20MB.";
        } else {
            $new_name = time() . '_' . uniqid() . '.' . $file_ext;
            $target_path = $upload_dir . $new_name;

            if (move_uploaded_file($file_tmp, $target_path)) {
                $fimage = 'images/furniture/' . $new_name;
            } else {
                $error = "Failed to upload image. Check folder permissions (755/777).";
            }
        }
    }

    if (empty($fname) || empty($fdesc) || $fprice <= 0) {
        $error = "Name, description and valid price are required.";
    } elseif (empty($error)) {
        $stmt = $conn->prepare("
            INSERT INTO Furnitures (fname, fdesc, fprice, fimage)
            VALUES (?, ?, ?, ?)
        ");
        $stmt->bind_param("ssds", $fname, $fdesc, $fprice, $fimage);

        if ($stmt->execute()) {
            $fid = $conn->insert_id;

            // Materials
            if (!empty($_POST['materials']) && is_array($_POST['materials'])) {
                foreach ($_POST['materials'] as $index => $mid_str) {
                    $mid = (int)$mid_str;
                    $pmqty = (int)($_POST['pmqty'][$index] ?? 0);
                    if ($mid > 0 && $pmqty > 0) {
                        $mat_stmt = $conn->prepare("INSERT INTO FurnitureMaterials (fid, mid, pmqty) VALUES (?, ?, ?)");
                        $mat_stmt->bind_param("iii", $fid, $mid, $pmqty);
                        $mat_stmt->execute();
                        $mat_stmt->close();
                    }
                }
            }

            $message = "Furniture '$fname' added (ID: $fid).";
            if ($fimage) $message .= " Image: $fimage";

            // Redirect to prevent resubmit on refresh
            header("Location: furniture_add.php");
            exit();
        } else {
            $error = "Insert failed: " . $stmt->error;
        }
        $stmt->close();
    }
}

// Load existing furniture (connection still open)
$furniture_list = [];
$result = $conn->query("
    SELECT fid, fname, fdesc, fprice, fimage
    FROM Furnitures
    ORDER BY fname ASC
");
while ($row = $result->fetch_assoc()) {
    $furniture_list[] = $row;
}

// Close connection at the very end
mysqli_close($conn);

// Flush output buffer (safe now)
ob_end_flush();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Furniture - Premium Living Furniture</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>
</head>
<body>

<header class="navbar">
    <div class="logo">
        <h2>Premium Living</h2>
        <small>Staff Area</small>
    </div>
    <nav class="nav-links">
        <a href="dashboard.php">Dashboard</a>
        <a href="furniture_add.php" class="active">Add Furniture</a>
        <a href="materials_add.php">Materials</a>
        <a href="orders_manage.php">Manage Orders</a>
        <a href="report.php">Reports</a>
        <?php if (isset($_SESSION['staff_role']) && strtolower($_SESSION['staff_role']) === 'admin'): ?>
            <a href="staff_manage.php">Manage Staff</a>
            <a href="customers_view.php">Customers</a>
        <?php endif; ?>
    </nav>
    <div class="nav-right">
        <span style="color:#ecf0f1; margin-right:1.2rem;">
            <?= htmlspecialchars($_SESSION['staff_name'] ?? 'Staff') ?>
            (<?= htmlspecialchars(ucfirst($_SESSION['staff_role'] ?? 'Staff')) ?>)
        </span>
        <a href="logout.php" class="btn-outline logout-btn">Logout</a>
    </div>
</header>

<div class="dashboard-container">

    <h1 class="section-title" style="margin:2rem 0;">Add New Furniture</h1>

    <?php if ($message): ?>
        <div class="success-message"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="error-message"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <!-- Add Furniture Form -->
    <div class="form-container" style="background:white; padding:2rem; border-radius:12px; box-shadow:0 4px 15px rgba(0,0,0,0.08); margin-bottom:3rem;">
        <form method="POST" action="" enctype="multipart/form-data">
            <input type="hidden" name="add_furniture" value="1">

            <div class="form-group">
                <label>Furniture Name *</label>
                <input type="text" name="fname" required placeholder="e.g. Modern Leather Sofa">
            </div>

            <div class="form-group">
                <label>Description *</label>
                <textarea name="fdesc" required rows="4" placeholder="Describe the furniture..."></textarea>
            </div>

            <div class="form-group">
                <label>Price (HKD) *</label>
                <input type="number" name="fprice" required min="0" step="0.01" placeholder="e.g. 5999.00">
            </div>

            <div class="form-group">
                <label>Image (optional)</label>
                <input type="file" name="fimage" accept="image/*">
                <small style="color:#777;">JPG, JPEG, PNG, GIF only (max 5MB)</small>
            </div>

            <!-- Materials -->
            <div class="form-group">
                <label>Required Materials</label>
                <div id="materials-container">
                    <div class="material-row" style="display:flex; gap:1rem; margin-bottom:1rem; align-items:center;">
                        <select name="materials[]" style="flex:1; padding:0.6rem;">
                            <option value="">Select Material</option>
                            <?php foreach ($materials as $m): ?>
                                <option value="<?= $m['mid'] ?>">
                                    <?= htmlspecialchars($m['mname']) ?> (<?= htmlspecialchars($m['munit']) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <input type="number" name="pmqty[]" min="1" placeholder="Qty" style="width:100px; padding:0.6rem;">
                        <button type="button" class="btn-outline remove-material" style="color:#dc3545;">Remove</button>
                    </div>
                </div>

                <button type="button" id="add-material-btn" class="btn-outline" style="margin-top:1rem;">
                    <i class="fas fa-plus"></i> Add Material
                </button>
            </div>

            <button type="submit" class="btn-primary" style="margin-top:2rem; width:100%;">
                <i class="fas fa-plus-circle"></i> Add Furniture
            </button>
        </form>
    </div>

    <!-- Existing Furniture List -->
    <h2 style="margin:3rem 0 1rem; color:#2c3e50;">Current Furniture Items</h2>

    <?php if (empty($furniture_list)): ?>
        <p style="text-align:center; color:#777; padding:2rem;">No furniture items found.</p>
    <?php else: ?>
        <div style="overflow-x:auto;">
            <table class="staff-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Image</th>
                        <th>Name</th>
                        <th>Description</th>
                        <th>Price (HKD)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($furniture_list as $f): ?>
                        <tr>
                            <td><?= $f['fid'] ?></td>
                            <td>
                                <?php if (!empty($f['fimage'])): ?>
                                    <img src="/4523m_ea/<?= htmlspecialchars($f['fimage']) ?>"
                                         alt="<?= htmlspecialchars($f['fname']) ?>" 
                                         style="width:80px; height:80px; object-fit:cover; border-radius:6px;"
                                         onerror="this.src='/images/placeholder.jpg'; this.alt='No image';">
                                <?php else: ?>
                                    <span style="color:#999;">No image</span>
                                <?php endif; ?>
                            </td>
                            <td><?= htmlspecialchars($f['fname']) ?></td>
                            <td><?= htmlspecialchars(substr($f['fdesc'] ?? '', 0, 100)) ?>...</td>
                            <td>$<?= number_format($f['fprice'], 2) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>

</div>

<script>
// Dynamic material rows
document.getElementById('add-material-btn')?.addEventListener('click', function () {
    const container = document.getElementById('materials-container');
    const row = document.createElement('div');
    row.className = 'material-row';
    row.style.cssText = 'display:flex; gap:1rem; margin-bottom:1rem; align-items:center;';

    row.innerHTML = `
        <select name="materials[]" style="flex:1; padding:0.6rem;">
            <option value="">Select Material</option>
            <?php foreach ($materials as $m): ?>
                <option value="<?= $m['mid'] ?>"><?= addslashes(htmlspecialchars($m['mname'])) ?> (<?= addslashes(htmlspecialchars($m['munit'])) ?>)</option>
            <?php endforeach; ?>
        </select>
        <input type="number" name="pmqty[]" min="1" placeholder="Qty" style="width:100px; padding:0.6rem;">
        <button type="button" class="btn-outline remove-material" style="color:#dc3545;">Remove</button>
    `;

    container.appendChild(row);
});

document.addEventListener('click', function (e) {
    if (e.target.classList.contains('remove-material')) {
        e.target.closest('.material-row').remove();
    }
});
</script>

</body>
</html>