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
    setcookie("staff_id", $row['sid'], time() - 120);
    setcookie("staff_name", $row['sname'], time() - 120);
    setcookie("staff_role", $row['srole'], time() - 120);
    header("Location: ../index.php");
    exit();
    ?>
</body>

</html>