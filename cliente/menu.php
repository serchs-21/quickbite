<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth_check.php';

requerirRol('Cliente');

if (!isset($_SESSION['carrito'])) {
    $_SESSION['carrito'] = [];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['agregar_id'])) {
    $prodId = (int) $_POST['agregar_id'];
    $catRedir = (int) ($_POST['categoria_actual'] ?? 0);

    $stmtProd = $pdo->prepare('SELECT id, stock FROM productos WHERE id = ? AND activo = 1 LIMIT 1');
    $stmtProd->execute([$prodId]);
    $producto = $stmtProd->fetch();

    if ($producto && (int) $producto['stock'] > 0) {
        $cantidadActual = $_SESSION['carrito'][$prodId] ?? 0;
        if ($cantidadActual < (int) $producto['stock']) {
            $_SESSION['carrito'][$prodId] = $cantidadActual + 1;
        }
    }

    $query = $catRedir > 0 ? "?categoria={$catRedir}&agregado=1" : '?agregado=1';
    header('Location: /cliente/menu.php' . $query);
    exit;
}

$totalItemsCarrito = array_sum($_SESSION['carrito']);
$categoriaSeleccionada = (int) ($_GET['categoria'] ?? 0);
$categorias = $pdo->query('SELECT id, nombre FROM categorias ORDER BY id')->fetchAll();

if ($categoriaSeleccionada > 0) {
    $stmt = $pdo->prepare(
        'SELECT p.*, c.nombre AS categoria_nombre
         FROM productos p
         INNER JOIN categorias c ON p.categoria_id = c.id
         WHERE p.activo = 1 AND p.categoria_id = ?
         ORDER BY p.nombre'
    );
    $stmt->execute([$categoriaSeleccionada]);
    $productos = $stmt->fetchAll();
} else {
    $productos = $pdo->query(
        'SELECT p.*, c.nombre AS categoria_nombre
         FROM productos p
         INNER JOIN categorias c ON p.categoria_id = c.id
         WHERE p.activo = 1
         ORDER BY c.id, p.nombre'
    )->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Menú Artesanal | QuickBite</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="/assets/css/quickbite.css" rel="stylesheet">
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark px-4">
    <a class="navbar-brand fw-bold" href="/cliente/menu.php">☕ QuickBite</a>
    <div class="navbar-nav me-auto">
        <a class="nav-link active fw-semibold" href="/cliente/menu.php">Menú</a>
        <a class="nav-link" href="/cliente/mis_pedidos.php">Mis Pedidos</a>
    </div>
    <div class="d-flex align-items-center gap-3 text-white">
        <a href="/cliente/carrito.php" class="btn btn-warning btn-sm fw-bold shadow-sm">
            🛒 Mi Orden (<?= $totalItemsCarrito ?>)
        </a>
        <small class="d-none d-md-inline text-white-50">Hola, <?= e($_SESSION['usuario_nombre']) ?></small>
        <a href="/auth/logout.php" class="btn btn-outline-light btn-sm">Salir</a>
    </div>
</nav>

<div class="container py-4">
    <!-- Hero Banner Sensorial -->
    <div class="qb-hero mb-4">
        <span class="qb-pill-tag mb-2">✨ Recién tostado & horneado hoy</span>
        <h2 class="fw-bold mb-2">Despierta tus sentidos, <?= e(explode(' ', $_SESSION['usuario_nombre'])[0]) ?></h2>
        <p class="mb-0 text-white-50" style="max-width: 560px;">
            Notas de cacao, canela y grano seleccionado. Elige tu bebida o especialidad favorita y la preparamos al momento.
        </p>
    </div>

    <?php if (isset($_GET['agregado'])): ?>
        <div class="alert alert-success border-0 shadow-sm d-flex justify-content-between align-items-center py-2 px-3 rounded-4">
            <span>☕ <strong>¡Excelente elección!</strong> Agregado a tu orden.</span>
            <a href="/cliente/carrito.php" class="btn btn-sm btn-dark">Ir a pagar →</a>
        </div>
    <?php endif; ?>

    <!-- Filtros por Categoría -->
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
        <h4 class="fw-bold mb-0">Nuestra Carta</h4>
        <div class="d-flex flex-wrap gap-2">
            <a href="/cliente/menu.php"
               class="btn btn-sm <?= $categoriaSeleccionada === 0 ? 'btn-dark' : 'btn-outline-dark' ?>">Todas</a>
            <?php foreach ($categorias as $cat): ?>
                <a href="/cliente/menu.php?categoria=<?= (int) $cat['id'] ?>"
                   class="btn btn-sm <?= $categoriaSeleccionada === (int) $cat['id'] ? 'btn-dark' : 'btn-outline-dark' ?>">
                    <?= e($cat['nombre']) ?>
                </a>
            <?php endforeach; ?>
        </div>
    </div>

    <?php if (empty($productos)): ?>
        <div class="alert alert-secondary rounded-4">No hay productos disponibles en esta categoría por el momento.</div>
    <?php else: ?>
        <div class="row g-4">
            <?php foreach ($productos as $p): ?>
                <?php
                    $stock = (int) $p['stock'];
                    $enCarrito = $_SESSION['carrito'][(int) $p['id']] ?? 0;
                ?>
                <div class="col-sm-6 col-md-4 col-lg-3">
                    <div class="card product-card h-100">
                        <div class="product-img-wrap">
                            <?php if (!empty($p['imagen'])): ?>
                                <img src="/uploads/<?= e($p['imagen']) ?>" alt="<?= e($p['nombre']) ?>">
                            <?php else: ?>
                                <div class="w-100 h-100 d-flex align-items-center justify-content-center" style="font-size: 3.2rem;">
                                    ☕
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="card-body d-flex flex-column p-3">
                            <span class="small fw-semibold text-uppercase mb-1" style="color: #c88a4b; font-size: 0.72rem; letter-spacing: 0.5px;">
                                <?= e($p['categoria_nombre']) ?>
                            </span>
                            <h5 class="font-serif fw-bold mb-1"><?= e($p['nombre']) ?></h5>
                            <p class="card-text text-muted small flex-grow-1"><?= e($p['descripcion'] ?? '') ?></p>

                            <div class="d-flex justify-content-between align-items-center pt-2 border-top mb-3">
                                <span class="price-tag">$<?= number_format((float) $p['precio'], 2) ?></span>
                                <?php if ($stock > 0): ?>
                                    <span class="badge rounded-pill" style="background-color: #d4e9e2; color: #1e3932;">
                                        <?= $stock ?> disp.
                                    </span>
                                <?php else: ?>
                                    <span class="badge rounded-pill bg-danger">Agotado</span>
                                <?php endif; ?>
                            </div>

                            <form method="POST" action="/cliente/menu.php">
                                <input type="hidden" name="agregar_id" value="<?= (int) $p['id'] ?>">
                                <input type="hidden" name="categoria_actual" value="<?= $categoriaSeleccionada ?>">
                                <?php if ($stock <= 0): ?>
                                    <button type="button" class="btn btn-secondary btn-sm w-100" disabled>Sin stock</button>
                                <?php elseif ($enCarrito >= $stock): ?>
                                    <button type="button" class="btn btn-outline-dark btn-sm w-100" disabled>
                                        Máximo en orden (<?= $enCarrito ?>)
                                    </button>
                                <?php else: ?>
                                    <button type="submit" class="btn btn-dark btn-sm w-100">
                                        + Ordenar <?= $enCarrito > 0 ? "({$enCarrito})" : '' ?>
                                    </button>
                                <?php endif; ?>
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