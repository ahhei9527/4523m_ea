<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Furniture - Premium Living Furniture</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
</head>

<body>
    <?php
    // staff/furniture_add.php
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

    ob_start();
    include '../connections/dbconn.php';

    $staff_name = $_COOKIE['staff_name'] ?? 'Staff';
    $is_admin = (isset($_COOKIE['staff_role']) && $_COOKIE['staff_role'] == "Administrator");   

    $message = '';
    $error = '';

    // Fetch materials
    $materials = [];
    $result = $conn->query("SELECT mid, mname, munit FROM Materials ORDER BY mname ASC");
    while ($row = $result->fetch_assoc()) {
        $materials[] = $row;
    }

    $upload_dir = '../images/';
    $public_path_prefix = 'images/';

    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
        $htaccess = $upload_dir . '.htaccess';
        if (!file_exists($htaccess)) {
            file_put_contents($htaccess, "<FilesMatch \"\.(?i:php|php[0-9]|phtml|phps|phar|inc|sh|pl|cgi|py|asp|aspx|jsp|jspx|cfm|cfc)\">\nOrder Deny,Allow\nDeny from all\n</FilesMatch>\nAddHandler text/plain .php .php3 .php4 .php5 .phtml .phar");
        }
    }

    function sanitizeFilename($name) {
        $name = strtolower(trim($name));
        $name = preg_replace('/[^a-z0-9\s-]/', '', $name);
        $name = preg_replace('/[\s-]+/', '-', $name);
        return $name;
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_furniture'])) {
        $fname   = trim($_POST['fname'] ?? '');
        $fdesc   = trim($_POST['fdesc'] ?? '');
        $fprice  = filter_var($_POST['fprice'] ?? 0, FILTER_VALIDATE_FLOAT);

        if (empty($fname) || empty($fdesc) || $fprice === false || $fprice <= 0) {
            $error = "Name, description and valid price are required.";
        } else {
            // Handle PNG Image Upload - Name same as Furniture Name
            if (isset($_FILES['fimage']) && $_FILES['fimage']['error'] === UPLOAD_ERR_OK) {
                $tmp_name = $_FILES['fimage']['tmp_name'];
                $mime = mime_content_type($tmp_name);
                $file_size = $_FILES['fimage']['size'];

                if ($mime !== 'image/png') {
                    $error = "Only PNG images are allowed.";
                } elseif ($file_size > 5 * 1024 * 1024) {
                    $error = "Image too large (max 5MB).";
                } else {
                    $base_name = sanitizeFilename($fname);
                    $new_name = $base_name . '.png';
                    $target_path = $upload_dir . $new_name;

                    if (file_exists($target_path)) {
                        $error = "Image with this furniture name already exists. Please choose a different name.";
                    } else {
                        if (move_uploaded_file($tmp_name, $target_path)) {
                            chmod($target_path, 0644);
                        } else {
                            $error = "Failed to save image.";
                        }
                    }
                }
            }

            if (empty($error)) {
                $stmt = $conn->prepare("
                    INSERT INTO Furnitures (fname, fdesc, fprice)
                    VALUES (?, ?, ?)
                ");
                $stmt->bind_param("ssd", $fname, $fdesc, $fprice);

                if ($stmt->execute()) {
                    header("Location: furniture_add.php?success=1");
                    exit();
                } else {
                    $error = "Insert failed: " . $stmt->error;
                }
                $stmt->close();
            }
        }
    }

    // Load furniture list
    $furniture_list = [];
    $result = $conn->query("SELECT fid, fname, fdesc, fprice FROM Furnitures ORDER BY fname ASC");
    while ($row = $result->fetch_assoc()) {
        $furniture_list[] = $row;
    }

    mysqli_close($conn);
    ob_end_flush();
    ?>

    <header class="navbar">
        <div class="logo">
            <h2>Premium Living</h2>
            <small style="color:#bdc3c7; font-size:0.9rem;">Staff Area</small>
        </div>
        <nav class="nav-links">
            <a href="dashboard.php">Dashboard</a>
            <a href="furniture_add.php" class="active">Add Furniture</a>
            <a href="materials_add.php">Materials</a>
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
        <h1 class="section-title" style="margin:2rem 0;">Add New Furniture</h1>

        <?php if (isset($_GET['success'])): ?>
            <div class="success-message">Furniture added successfully!</div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="error-message"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <!-- Form -->
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
                    <label>Image (PNG only)</label>
                    <input type="file" name="fimage" accept="image/png">
                    <small style="color:#777;">
                        Only PNG allowed.<br>
                        Image will be saved as: <strong>[Furniture Name].png</strong> in <strong>images/</strong> folder
                    </small>
                </div>

                <div class="form-group">
                    <label>Required Materials</label>
                    <div id="materials-container">
                        <div class="material-row"
                            style="display:flex; gap:1rem; margin-bottom:1rem; align-items:center;">
                            <select name="materials[]" style="flex:1; padding:0.6rem;">
                                <option value="">Select Material</option>
                                <?php foreach ($materials as $m): ?>
                                    <option value="<?= $m['mid'] ?>">
                                        <?= htmlspecialchars($m['mname']) ?> (<?= htmlspecialchars($m['munit']) ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <input type="number" name="pmqty[]" min="1" placeholder="Qty"
                                style="width:100px; padding:0.6rem;">
                            <button type="button" class="btn-outline remove-material"
                                style="color:#dc3545;">Remove</button>
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

        <!-- Furniture List -->
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
                            <th>Stock</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($furniture_list as $f): ?>
                            <tr>
                                <td><?= $f['fid'] ?></td>
                                <td>
                                    <?php 
                                    $img_name = $f['fname'];
                                    $img_full_path = '../images/' . $img_name . '.png';   // ← Path changed to ../images/
                                    
                                    if (file_exists($img_full_path)): 
                                    ?>
                                        <img src="../images/<?= htmlspecialchars($img_name) ?>.png"
                                             alt="<?= htmlspecialchars($f['fname']) ?>"
                                             style="width:80px; height:80px; object-fit:cover; border-radius:6px;">
                                    <?php else: ?>
                                        <span style="color:#999;">No image</span>
                                    <?php endif; ?>
                                </td>
                                <td><?= htmlspecialchars($f['fname']) ?></td>
                                <td><?= htmlspecialchars(substr($f['fdesc'] ?? '', 0, 100)) ?>...</td>
                                <td>$<?= number_format($f['fprice'], 2) ?></td>
                                <td style="text-align: center;">
                                    <?php
                                    include '../connections/dbconn.php';

                                    $max_units = PHP_INT_MAX;
                                    $stmt = $conn->prepare("
                                        SELECT fm.pmqty, m.mqty
                                        FROM FurnitureMaterials fm
                                        JOIN Materials m ON fm.mid = m.mid
                                        WHERE fm.fid = ?
                                        AND fm.pmqty > 0
                                    ");
                                    $stmt->bind_param("i", $f['fid']);
                                    $stmt->execute();
                                    $result = $stmt->get_result();

                                    $has_materials = $result->num_rows > 0;

                                    if (!$has_materials) {
                                        $stock = 0;
                                    } else {
                                        while ($row = $result->fetch_assoc()) {
                                            $required = (int)$row['pmqty'];
                                            $available = (int)$row['mqty'];
                                            if ($required > 0) {
                                                $max_units = min($max_units, floor($available / $required));
                                            }
                                        }
                                        $stock = ($max_units === PHP_INT_MAX) ? 0 : $max_units;
                                    }
                                    $stmt->close();
                                    mysqli_close($conn);

                                    if ($stock > 10) {
                                        echo '<span style="color:#27ae60; font-weight:bold;">' . number_format($stock) . '</span>';
                                    } elseif ($stock > 0) {
                                        echo '<span style="color:#f39c12; font-weight:bold;">' . number_format($stock) . '</span>';
                                    } else {
                                        echo '<span style="color:#c0392b;">0</span>';
                                    }
                                    ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

    <script src="../js/main.js"></script>
</body>
</html>