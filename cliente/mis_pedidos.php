<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth_check.php';

requerirRol('Cliente');

$stmtPedidos = $pdo->prepare(
    'SELECT id, total, estado, creado_en
     FROM pedidos
     WHERE usuario_id = ?
     ORDER BY id DESC'
);
$stmtPedidos->execute([$_SESSION['usuario_id']]);
$pedidos = $stmtPedidos->fetchAll();

$stmtDetalles = $pdo->prepare(
    'SELECT dp.cantidad, dp.precio_unitario, p.nombre
     FROM detalle_pedidos dp
     INNER JOIN productos p ON dp.producto_id = p.id
     WHERE dp.pedido_id = ?'
);

function badgeEstado(string $estado): string {
    return match ($estado) {
        'Pendiente'      => '<span class="badge bg-warning text-dark">⏳ Pendiente</span>',
        'En preparación' => '<span class="badge bg-info text-dark">👨‍🍳 En preparación</span>',
        'Listo'          => '<span class="badge bg-success">✅ Listo para recoger</span>',
        'Entregado'      => '<span class="badge bg-secondary">📦 Entregado</span>',
        default          => '<span class="badge bg-light text-dark">' . e($estado) . '</span>',
    };
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mis Pedidos | QuickBite</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="/assets/css/quickbite.css" rel="stylesheet">
</head>
<body class="bg-light">
<nav class="navbar navbar-expand-lg navbar-dark bg-warning-emphasis px-4">
    <a class="navbar-brand fw-bold" href="/cliente/menu.php">☕ QuickBite</a>
    <div class="navbar-nav me-auto">
        <a class="nav-link" href="/cliente/menu.php">Menú</a>
        <a class="nav-link active fw-semibold" href="/cliente/mis_pedidos.php">Mis Pedidos</a>
    </div>
    <div class="d-flex align-items-center gap-3 text-white">
        <a href="/cliente/carrito.php" class="btn btn-warning btn-sm fw-bold">
            🛒 Carrito (<?= array_sum($_SESSION['carrito'] ?? []) ?>)
        </a>
        <a href="/auth/logout.php" class="btn btn-outline-light btn-sm">Salir</a>
    </div>
</nav>

<div class="container py-4">
    <h4 class="fw-bold mb-4">Mis Pedidos</h4>

    <?php if (isset($_GET['nuevo'])): ?>
        <div class="alert alert-success">
            <strong>¡Pedido #<?= (int) $_GET['nuevo'] ?> enviado a cocina!</strong> El stock ha sido reservado y puedes seguir su estado aquí.
        </div>
    <?php endif; ?>

    <?php if (empty($pedidos)): ?>
        <div class="alert alert-secondary">Aún no has realizado ningún pedido. <a href="/cliente/menu.php">Ir al menú</a></div>
    <?php else: ?>
        <div class="row g-3">
            <?php foreach ($pedidos as $ped): ?>
                <?php
                    $stmtDetalles->execute([(int) $ped['id']]);
                    $detalles = $stmtDetalles->fetchAll();
                ?>
                <div class="col-md-6">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-header bg-white d-flex justify-content-between align-items-center">
                            <span class="fw-bold">Pedido #<?= (int) $ped['id'] ?></span>
                            <?= badgeEstado($ped['estado']) ?>
                        </div>
                        <div class="card-body">
                            <small class="text-muted d-block mb-2">Fecha: <?= e($ped['creado_en']) ?></small>
                            <ul class="list-group list-group-flush mb-3">
                                <?php foreach ($detalles as $d): ?>
                                    <li class="list-group-item px-0 d-flex justify-content-between">
                                        <span><?= (int) $d['cantidad'] ?>x <?= e($d['nombre']) ?></span>
                                        <span>$<?= number_format((float) $d['precio_unitario'] * (int) $d['cantidad'], 2) ?></span>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                            <div class="d-flex justify-content-between fw-bold fs-6">
                                <span>Total pagado:</span>
                                <span>$<?= number_format((float) $ped['total'], 2) ?></span>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
</body>
</html>