<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth_check.php';

requerirRol('Gerente');

$estadosValidos = ['Pendiente', 'En preparación', 'Listo', 'Entregado'];

// Actualizar estado de un pedido
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['pedido_id'], $_POST['nuevo_estado'])) {
    $pedidoId = (int) $_POST['pedido_id'];
    $nuevoEstado = $_POST['nuevo_estado'];

    if ($pedidoId > 0 && in_array($nuevoEstado, $estadosValidos, true)) {
        $stmtUpd = $pdo->prepare('UPDATE pedidos SET estado = ? WHERE id = ?');
        $stmtUpd->execute([$nuevoEstado, $pedidoId]);
        header('Location: /admin/pedidos.php?actualizado=' . $pedidoId);
        exit;
    }
}

$filtro = $_GET['filtro'] ?? 'activos';
if ($filtro === 'todos') {
    $pedidos = $pdo->query(
        'SELECT p.*, u.nombre AS cliente_nombre, u.email AS cliente_email
         FROM pedidos p
         INNER JOIN usuarios u ON p.usuario_id = u.id
         ORDER BY p.id DESC'
    )->fetchAll();
} else {
    $pedidos = $pdo->query(
        "SELECT p.*, u.nombre AS cliente_nombre, u.email AS cliente_email
         FROM pedidos p
         INNER JOIN usuarios u ON p.usuario_id = u.id
         WHERE p.estado != 'Entregado'
         ORDER BY p.id ASC"
    )->fetchAll();
}

$stmtDetalles = $pdo->prepare(
    'SELECT dp.cantidad, dp.precio_unitario, pr.nombre
     FROM detalle_pedidos dp
     INNER JOIN productos pr ON dp.producto_id = pr.id
     WHERE dp.pedido_id = ?'
);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Pedidos | QuickBite</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="/assets/css/quickbite.css" rel="stylesheet">
</head>
<body class="bg-light">
<nav class="navbar navbar-expand-lg navbar-dark bg-dark px-4">
    <a class="navbar-brand fw-bold" href="/admin/dashboard.php">☕ QuickBite — Gerencia</a>
    <div class="navbar-nav me-auto">
        <a class="nav-link" href="/admin/dashboard.php">Resumen</a>
        <a class="nav-link" href="/admin/productos.php">Productos e Inventario</a>
        <a class="nav-link active fw-semibold" href="/admin/pedidos.php">Pedidos</a>
    </div>
    <div class="d-flex align-items-center gap-3 text-white">
        <small><?= e($_SESSION['usuario_nombre']) ?></small>
        <a href="/auth/logout.php" class="btn btn-outline-light btn-sm">Cerrar sesión</a>
    </div>
</nav>

<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="fw-bold mb-0">Control de Pedidos en Cocina</h4>
        <div class="btn-group">
            <a href="/admin/pedidos.php?filtro=activos"
               class="btn btn-sm <?= $filtro !== 'todos' ? 'btn-dark' : 'btn-outline-dark' ?>">En Curso</a>
            <a href="/admin/pedidos.php?filtro=todos"
               class="btn btn-sm <?= $filtro === 'todos' ? 'btn-dark' : 'btn-outline-dark' ?>">Historial Completo</a>
        </div>
    </div>

    <?php if (isset($_GET['actualizado'])): ?>
        <div class="alert alert-success py-2">
            Estado del <strong>Pedido #<?= (int) $_GET['actualizado'] ?></strong> actualizado correctamente.
        </div>
    <?php endif; ?>

    <?php if (empty($pedidos)): ?>
        <div class="alert alert-secondary">No hay pedidos pendientes en esta vista.</div>
    <?php else: ?>
        <div class="row g-3">
            <?php foreach ($pedidos as $ped): ?>
                <?php
                    $stmtDetalles->execute([(int) $ped['id']]);
                    $detalles = $stmtDetalles->fetchAll();
                ?>
                <div class="col-md-6 col-lg-4">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-header bg-white d-flex justify-content-between align-items-center">
                            <span class="fw-bold">Pedido #<?= (int) $ped['id'] ?></span>
                            <span class="badge bg-dark">$<?= number_format((float) $ped['total'], 2) ?></span>
                        </div>
                        <div class="card-body d-flex flex-column">
                            <div class="mb-2">
                                <strong>Cliente:</strong> <?= e($ped['cliente_nombre']) ?><br>
                                <small class="text-muted"><?= e($ped['creado_en']) ?></small>
                            </div>

                            <ul class="list-group list-group-flush mb-3 flex-grow-1">
                                <?php foreach ($detalles as $d): ?>
                                    <li class="list-group-item px-0 py-1 d-flex justify-content-between">
                                        <span><strong><?= (int) $d['cantidad'] ?>x</strong> <?= e($d['nombre']) ?></span>
                                        <span class="text-muted">$<?= number_format((float) $d['precio_unitario'] * (int) $d['cantidad'], 2) ?></span>
                                    </li>
                                <?php endforeach; ?>
                            </ul>

                            <form method="POST" action="/admin/pedidos.php?filtro=<?= e($filtro) ?>" class="d-flex gap-2">
                                <input type="hidden" name="pedido_id" value="<?= (int) $ped['id'] ?>">
                                <select name="nuevo_estado" class="form-select form-select-sm">
                                    <?php foreach ($estadosValidos as $est): ?>
                                        <option value="<?= e($est) ?>" <?= $ped['estado'] === $est ? 'selected' : '' ?>>
                                            <?= e($est) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <button type="submit" class="btn btn-primary btn-sm">Guardar</button>
                            </form>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
</body>
</html>