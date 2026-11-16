<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth_check.php';

requerirRol('Gerente');

$totalProductos = (int) $pdo->query('SELECT COUNT(*) FROM productos')->fetchColumn();
$stockBajo      = (int) $pdo->query('SELECT COUNT(*) FROM productos WHERE stock <= 5')->fetchColumn();
$pedidosActivos = (int) $pdo->query("SELECT COUNT(*) FROM pedidos WHERE estado != 'Entregado'")->fetchColumn();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel Gerente | QuickBite</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="/assets/css/quickbite.css" rel="stylesheet">
</head>
<body class="bg-light">
<nav class="navbar navbar-expand-lg navbar-dark bg-dark px-4">
    <a class="navbar-brand fw-bold" href="/admin/dashboard.php">☕ QuickBite — Gerencia</a>
    <div class="navbar-nav me-auto">
        <a class="nav-link active fw-semibold" href="/admin/dashboard.php">Resumen</a>
        <a class="nav-link" href="/admin/productos.php">Productos e Inventario</a>
        <a class="nav-link" href="/admin/pedidos.php">Pedidos</a>
    </div>
    <div class="d-flex align-items-center gap-3 text-white">
        <small>Hola, <?= e($_SESSION['usuario_nombre']) ?></small>
        <a href="/auth/logout.php" class="btn btn-outline-light btn-sm">Cerrar sesión</a>
    </div>
</nav>

<div class="container py-4">
    <h4 class="fw-bold mb-4">Panel de Control</h4>

    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="text-muted small">Productos en Catálogo</div>
                    <div class="fs-2 fw-bold"><?= $totalProductos ?></div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="text-muted small">Alertas de Stock Bajo (≤ 5)</div>
                    <div class="fs-2 fw-bold text-danger"><?= $stockBajo ?></div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="text-muted small">Pedidos en Curso</div>
                    <div class="fs-2 fw-bold text-primary"><?= $pedidosActivos ?></div>
                </div>
            </div>
        </div>
    </div>

    <div class="d-flex flex-wrap gap-2">
        <a href="/admin/pedidos.php" class="btn btn-primary">Gestionar Pedidos en Curso →</a>
        <a href="/admin/productos.php" class="btn btn-dark">Administrar Productos e Inventario →</a>
    </div>
</div>
</body>
</html>