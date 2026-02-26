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
    
    session_start();

    // Must be logged in as staff
    if (!isset($_SESSION['staff_id'])) {
        header("Location: login.php");
        exit();
    }

    // Prevent any accidental output before headers
    ob_start();

    include '../connections/dbconn.php';

    $staff_name = $_SESSION['staff_name'] ?? 'Staff';
    $is_admin = (isset($_SESSION['staff_role']) && $_SESSION['staff_role'] == "Administrator");

    $message = '';
    $error = '';

    // Fetch all materials for dropdown
    $materials = [];
    $result = $conn->query("SELECT mid, mname, munit FROM Materials ORDER BY mname ASC");
    while ($row = $result->fetch_assoc()) {
        $materials[] = $row;
    }

    // Handle form submission - Add new furniture
    // Handle image upload
    $fimage = '';
    $upload_dir = '../images/furniture/';  // relative to web root — good
    $public_path_prefix = 'images/furniture/';  // what you store in DB
    
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
        // Optional: place .htaccess to deny script execution
        $htaccess = $upload_dir . '.htaccess';
        if (!file_exists($htaccess)) {
            file_put_contents($htaccess, "<FilesMatch \"\.(?i:php|php[0-9]|phtml|phps|phar|inc|sh|pl|cgi|py|asp|aspx|jsp|jspx|cfm|cfc)\">\nOrder Deny,Allow\nDeny from all\n</FilesMatch>\nAddHandler text/plain .php .php3 .php4 .php5 .phtml .phar");
        }
    }

    if (isset($_FILES['fimage']) && $_FILES['fimage']['error'] === UPLOAD_ERR_OK) {
        $tmp_name = $_FILES['fimage']['tmp_name'];
        $orig_name = $_FILES['fimage']['name'];
        $file_size = $_FILES['fimage']['size'];
        $mime = mime_content_type($tmp_name);  // real MIME, not client-supplied
    
        $allowed_mimes = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/gif' => 'gif',
            // 'image/webp' => 'webp',   // add if you want
        ];

        if (!array_key_exists($mime, $allowed_mimes)) {
            $error = "Invalid image type. Only JPEG, PNG, GIF allowed.";
        } elseif ($file_size > 5 * 1024 * 1024) {   // lowered to 5MB (your form says 5MB)
            $error = "Image too large (max 5MB).";
        } elseif (!is_uploaded_file($tmp_name)) {
            $error = "Invalid upload source.";
        } else {
            // Generate safe, unique name — no original name kept
            $ext = $allowed_mimes[$mime];
            $new_name = time() . '_' . bin2hex(random_bytes(8)) . '.' . $ext;
            $target_path = $upload_dir . $new_name;

            if (move_uploaded_file($tmp_name, $target_path)) {
                chmod($target_path, 0644);  // safe permissions
                $fimage = $public_path_prefix . $new_name;
            } else {
                $error = "Failed to save image (permissions?).";
            }
        }
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_furniture'])) {
        $fname = trim($_POST['fname'] ?? '');
        $fdesc = trim($_POST['fdesc'] ?? '');
        $fprice = filter_var($_POST['fprice'] ?? 0, FILTER_VALIDATE_FLOAT);

        if (empty($fname) || empty($fdesc) || $fprice === false || $fprice <= 0) {
            $error = "Name, description and valid price are required.";
        } elseif (empty($error)) {
            $stmt = $conn->prepare("
            INSERT INTO Furnitures (fname, fdesc, fprice, fimage)
            VALUES (?, ?, ?, ?)
        ");
            $stmt->bind_param("ssds", $fname, $fdesc, $fprice, $fimage);

            if ($stmt->execute()) {
                $fid = $conn->insert_id;
                // ... materials insert code remains the same ...
    
                $message = "Furniture '$fname' added (ID: $fid).";
                if ($fimage)
                    $message .= " Image: $fimage";

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
    SELECT fid, fname, fdesc, fprice
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

        <?php if ($message): ?>
            <div class="success-message"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="error-message"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <!-- Add Furniture Form -->
        <div class="form-container"
            style="background:white; padding:2rem; border-radius:12px; box-shadow:0 4px 15px rgba(0,0,0,0.08); margin-bottom:3rem;">
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
                            <th>Stock</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($furniture_list as $f): ?>
                            <tr>
                                <td><?= $f['fid'] ?></td>
                                <td>
                                    <?php if (!empty($f['fname'])): ?>
                                        <img src="../images/<?= htmlspecialchars($f['fname']) ?>.png"
                                            alt="<?= htmlspecialchars($f['fname']) ?>"
                                            style="width:80px; height:80px; object-fit:cover; border-radius:6px;"
                                            onerror="this.onerror=null; this.src='/images/placeholder.jpg'; this.alt='Image not found';">
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

                                    $max_units = PHP_INT_MAX;  // very large number as starting point
                            
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
                                        // No materials defined → either unlimited or 0 (choose business rule)
                                        $stock = 0;  // most common: treat as not producible
                                    } else {
                                        while ($row = $result->fetch_assoc()) {
                                            $required_per_unit = (int) $row['pmqty'];
                                            $available = (int) $row['mqty'];

                                            if ($required_per_unit <= 0)
                                                continue;

                                            $can_make_with_this = floor($available / $required_per_unit);
                                            $max_units = min($max_units, $can_make_with_this);
                                        }

                                        $stock = ($max_units === PHP_INT_MAX) ? 0 : $max_units;
                                    }

                                    $stmt->close();
                                    mysqli_close($conn);

                                    // Visual feedback (optional but very useful)
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