<?php require_once 'db.php'; ?><!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">

    <link rel="stylesheet" href="css/custom.css">

    <title><?php echo isset($page_title) ? $page_title . ' - ' . SITE_NAME : SITE_NAME; ?></title>
    <link rel="icon" href="img/logo.png" type="image/png">
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark bg-primary">
    <div class="container">
       <a class="navbar-brand d-flex align-items-center" href="index.php">
      <img src="img/logo.png" alt="Pusula Logo" width="100" class="me-2">

</a>
     <div class="d-flex">
    <?php
    if (isset($_SESSION['user_id'])) {
        echo '<span class="navbar-text me-3">Hoş Geldin, ' . htmlspecialchars($_SESSION['user_fullname']) . '!</span>';
        echo '<a href="my_tickets.php" class="btn btn-light me-2">Biletlerim</a>';
        echo '<a href="profile.php" class="btn btn-info me-2">Profilim</a>';

        if ($_SESSION['user_role'] == 'Admin' || $_SESSION['user_role'] == 'Firma Admin') {
            echo '<a href="admin_panel.php" class="btn btn-warning me-2">Admin Paneli</a>';
        }

        echo '<a href="logout.php" class="btn btn-danger">Çıkış Yap</a>';

    } else {
        echo '<a href="login.php" class="btn btn-light me-2">Giriş Yap</a>';
        echo '<a href="register.php" class="btn btn-warning">Kayıt Ol</a>';
    }
    ?>
</div>
    </div>
</nav>

<div class="container mt-4">
