<?php require __DIR__ . '/helpers.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WikiGun</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark bg-dark sticky-top">
    <div class="container">
        <a class="navbar-brand fw-bold" href="index.php">
            <i class="bi bi-bullseye"></i> WikiGun
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navMenu">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navMenu">
            <ul class="navbar-nav ms-auto">
                <li class="nav-item">
                    <a class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'index.php' ? 'active' : '' ?>"
                       href="index.php"><i class="bi bi-gun"></i> Firearms</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'ammo.php' ? 'active' : '' ?>"
                       href="ammo.php"><i class="bi bi-circle"></i> Ammo</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'manufacturer.php' ? 'active' : '' ?>"
                       href="manufacturer.php"><i class="bi bi-building"></i> Manufacturers</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'attachment.php' ? 'active' : '' ?>"
                       href="attachment.php"><i class="bi bi-tools"></i> Attachments</a>
                </li>
                <li class="nav-item ms-lg-3">
                    <a class="nav-link <?= str_contains($_SERVER['PHP_SELF'], '/admin/') ? 'active' : '' ?>"
                       href="admin/index.php"><i class="bi bi-gear-fill"></i> Admin</a>
                </li>
            </ul>
        </div>
    </div>
</nav>

<div class="container my-4">
