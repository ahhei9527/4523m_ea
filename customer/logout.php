<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Logout</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
</head>

<body>
    <?php
    setcookie("customer_id", "", $expiry, "/", "", false, true);
    setcookie("customer_name", "", $expiry, "/", "", false, true);
    setcookie("customer_company", "", $expiry, "/", "", false, true);
    setcookie("customer_tel", "", $expiry, "/", "", false, true);
    header("Location: ../index.php");
    exit();
    ?>
</body>

</html>