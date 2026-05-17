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
    setcookie("customer_id", $row['cid'], time() - 86400);
    setcookie("customer_name", $row['cname'], time() - 86400);
    setcookie("customer_company", $row['company'], time() - 86400);
    header("Location: ../index.php");
    exit();
    ?>
</body>

</html>