<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth_check.php';

requerirRol('Cliente');

if (!isset($_SESSION['carrito'])) {
    $_SESSION['carrito'] = [];
}

$errores = [];

// Acciones sobre el carrito (actualizar cantidad, quitar o vaciar)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = $_POST['accion'] ?? '';

    if ($accion === 'vaciar') {
        $_SESSION['carrito'] = [];
        header('Location: /cliente/carrito.php');
        exit;
    }

    if ($accion === 'actualizar') {
        $id = (int) ($_POST['producto_id'] ?? 0);
        $cantidad = (int) ($_POST['cantidad'] ?? 0);

        if ($cantidad <= 0) {
            unset($_SESSION['carrito'][$id]);
        } else {
            $stmt = $pdo->prepare('SELECT stock FROM productos WHERE id = ? AND activo = 1');
            $stmt->execute([$id]);
            $prod = $stmt->fetch();
            if ($prod) {
                $_SESSION['carrito'][$id] = min($cantidad, (int) $prod['stock']);
            }
        }
        header('Location: /cliente/carrito.php');
        exit;
    }

    // Confirmar pedido con transacción atómica
    if ($accion === 'confirmar' && !empty($_SESSION['carrito'])) {
        try {
            $pdo->beginTransaction();

            $totalPedido = 0.0;
            $lineasValidadas = [];

            $stmtLock = $pdo->prepare(
                'SELECT id, nombre, precio, stock FROM productos WHERE id = ? AND activo = 1 FOR UPDATE'
            );

            foreach ($_SESSION['carrito'] as $prodId => $cantSolicitada) {
                $stmtLock->execute([(int) $prodId]);
                $prod = $stmtLock->fetch();

                if (!$prod || (int) $prod['stock'] < (int) $cantSolicitada) {
                    $nombreProd = $prod['nombre'] ?? 'Producto #' . $prodId;
                    throw new RuntimeException("Stock insuficiente para: {$nombreProd}. Ajusta tu carrito.");
                }

                $subtotal = (float) $prod['precio'] * (int) $cantSolicitada;
                $totalPedido += $subtotal;

                $lineasValidadas[] = [
                    'producto_id'     => (int) $prod['id'],
                    'cantidad'        => (int) $cantSolicitada,
                    'precio_unitario' => (float) $prod['precio'],
                ];
            }

            // 1. Insertar cabecera del pedido
            $stmtPedido = $pdo->prepare(
                "INSERT INTO pedidos (usuario_id, total, estado) VALUES (?, ?, 'Pendiente')"
            );
            $stmtPedido->execute([$_SESSION['usuario_id'], $totalPedido]);
            $pedidoId = (int) $pdo->lastInsertId();

            // 2. Insertar detalles y descontar stock
            $stmtDetalle = $pdo->prepare(
                'INSERT INTO detalle_pedidos (pedido_id, producto_id, cantidad, precio_unitario)
                 VALUES (?, ?, ?, ?)'
            );
            $stmtDescontar = $pdo->prepare(
                'UPDATE productos SET stock = stock - ? WHERE id = ?'
            );

            foreach ($lineasValidadas as $linea) {
                $stmtDetalle->execute([
                    $pedidoId,
                    $linea['producto_id'],
                    $linea['cantidad'],
                    $linea['precio_unitario'],
                ]);
                $stmtDescontar->execute([
                    $linea['cantidad'],
                    $linea['producto_id'],
                ]);
            }

            $pdo->commit();
            $_SESSION['carrito'] = [];

            header('Location: /cliente/mis_pedidos.php?nuevo=' . $pedidoId);
            exit;
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $errores[] = $e->getMessage();
        }
    }
}

// Cargar información actualizada de los productos en carrito
$items = [];
$totalGeneral = 0.0;

if (!empty($_SESSION['carrito'])) {
    $ids = array_keys($_SESSION['carrito']);
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $stmt = $pdo->prepare("SELECT id, nombre, precio, stock, imagen FROM productos WHERE id IN ($placeholders)");
    $stmt->execute($ids);
    $productosBD = $stmt->fetchAll();

    foreach ($productosBD as $p) {
        $id = (int) $p['id'];
        $cant = $_SESSION['carrito'][$id] ?? 0;
        if ($cant > 0) {
            $subtotal = (float) $p['precio'] * $cant;
            $totalGeneral += $subtotal;
            $items[] = [
                'id'       => $id,
                'nombre'   => $p['nombre'],
                'precio'   => (float) $p['precio'],
                'stock'    => (int) $p['stock'],
                'imagen'   => $p['imagen'],
                'cantidad' => $cant,
                'subtotal' => $subtotal,
            ];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mi Carrito | QuickBite</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="/assets/css/quickbite.css" rel="stylesheet">
</head>
<body class="bg-light">
<nav class="navbar navbar-expand-lg navbar-dark bg-warning-emphasis px-4">
    <a class="navbar-brand fw-bold" href="/cliente/menu.php">☕ QuickBite</a>
    <div class="navbar-nav me-auto">
        <a class="nav-link" href="/cliente/menu.php">Menú</a>
        <a class="nav-link" href="/cliente/mis_pedidos.php">Mis Pedidos</a>
    </div>
    <div class="d-flex align-items-center gap-3 text-white">
        <small><?= e($_SESSION['usuario_nombre']) ?></small>
        <a href="/auth/logout.php" class="btn btn-outline-light btn-sm">Salir</a>
    </div>
</nav>

<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="fw-bold mb-0">🛒 Tu Carrito de Compras</h4>
        <a href="/cliente/menu.php" class="btn btn-outline-dark btn-sm">← Seguir pidiendo</a>
    </div>

    <?php if (!empty($errores)): ?>
        <div class="alert alert-danger">
            <?php foreach ($errores as $err): ?>
                <div><?= e($err) ?></div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <?php if (empty($items)): ?>
        <div class="card border-0 shadow-sm p-5 text-center">
            <p class="text-muted mb-3">Tu carrito está vacío.</p>
            <div><a href="/cliente/menu.php" class="btn btn-dark">Explorar el Menú</a></div>
        </div>
    <?php else: ?>
        <div class="row g-4">
            <div class="col-lg-8">
                <div class="card border-0 shadow-sm">
                    <div class="table-responsive">
                        <table class="table align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Producto</th>
                                    <th>Precio</th>
                                    <th style="width: 150px;">Cantidad</th>
                                    <th>Subtotal</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($items as $item): ?>
                                    <tr>
                                        <td class="fw-semibold"><?= e($item['nombre']) ?></td>
                                        <td>$<?= number_format($item['precio'], 2) ?></td>
                                        <td>
                                            <form method="POST" action="/cliente/carrito.php" class="d-flex gap-1">
                                                <input type="hidden" name="accion" value="actualizar">
                                                <input type="hidden" name="producto_id" value="<?= $item['id'] ?>">
                                                <input type="number" name="cantidad" min="1" max="<?= $item['stock'] ?>"
                                                       value="<?= $item['cantidad'] ?>" class="form-control form-control-sm">
                                                <button type="submit" class="btn btn-sm btn-outline-secondary">✓</button>
                                            </form>
                                        </td>
                                        <td class="fw-bold">$<?= number_format($item['subtotal'], 2) ?></td>
                                        <td class="text-end">
                                            <form method="POST" action="/cliente/carrito.php">
                                                <input type="hidden" name="accion" value="actualizar">
                                                <input type="hidden" name="producto_id" value="<?= $item['id'] ?>">
                                                <input type="hidden" name="cantidad" value="0">
                                                <button type="submit" class="btn btn-sm btn-outline-danger">✕</button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <h5 class="fw-bold mb-3">Resumen del Pedido</h5>
                        <div class="d-flex justify-content-between mb-3 fs-5">
                            <span>Total a pagar:</span>
                            <span class="fw-bold text-success">$<?= number_format($totalGeneral, 2) ?></span>
                        </div>

                        <form method="POST" action="/cliente/carrito.php" class="d-grid gap-2">
                            <input type="hidden" name="accion" value="confirmar">
                            <button type="submit" class="btn btn-dark btn-lg">Confirmar Pedido</button>
                        </form>

                        <form method="POST" action="/cliente/carrito.php" class="mt-2 text-center">
                            <input type="hidden" name="accion" value="vaciar">
                            <button type="submit" class="btn btn-link btn-sm text-danger text-decoration-none">
                                Vaciar carrito
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>
</body>
</html>