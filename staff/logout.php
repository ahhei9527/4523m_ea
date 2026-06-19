<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
</head>

<body>
    <?php
    // staff/logout.php
    
    // Delete the cookies properly
    setcookie('staff_id',   '', time() - 3600, "/", "", false, true);
    setcookie('staff_name', '', time() - 3600, "/", "", false, true);
    setcookie('staff_role', '', time() - 3600, "/", "", false, true);

    unset($_COOKIE['staff_id']);
    unset($_COOKIE['staff_name']);
    unset($_COOKIE['staff_role']);

    // Destroy session
    session_start();
    session_unset();
    session_destroy();

    // Redirect
    header("Location: ../index.php");
    exit();
    ?>
</body>

</html>