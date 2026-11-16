<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth_check.php';
require_once __DIR__ . '/../config/upload.php';

requerirRol('Gerente');

$errores = [];
$mensaje = match ($_GET['ok'] ?? '') {
    'creado'     => 'Producto registrado correctamente.',
    'actualizado'=> 'Producto actualizado correctamente.',
    'eliminado'  => 'Producto eliminado del inventario.',
    default      => '',
};

// Eliminar producto
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['accion'] ?? '') === 'eliminar') {
    $idEliminar = (int) ($_POST['id'] ?? 0);
    if ($idEliminar > 0) {
        try {
            $stmtImg = $pdo->prepare('SELECT imagen FROM productos WHERE id = ?');
            $stmtImg->execute([$idEliminar]);
            $prod = $stmtImg->fetch();

            $del = $pdo->prepare('DELETE FROM productos WHERE id = ?');
            $del->execute([$idEliminar]);

            if ($prod) {
                eliminarImagenArchivo($prod['imagen']);
            }
            header('Location: /admin/productos.php?ok=eliminado');
            exit;
        } catch (PDOException $e) {
            $errores[] = 'No se puede eliminar este producto porque ya forma parte de pedidos registrados. Edítalo y desmarca "Disponible en menú".';
        }
    }
}

// Crear o Actualizar producto
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['accion'] ?? '') === 'guardar') {
    $id          = (int) ($_POST['id'] ?? 0);
    $nombre      = trim($_POST['nombre'] ?? '');
    $descripcion = trim($_POST['descripcion'] ?? '');
    $precio      = (float) ($_POST['precio'] ?? 0);
    $stock       = (int) ($_POST['stock'] ?? 0);
    $categoriaId = (int) ($_POST['categoria_id'] ?? 0);
    $activo      = isset($_POST['activo']) ? 1 : 0;

    if ($nombre === '') {
        $errores[] = 'El nombre del producto es obligatorio.';
    }
    if ($precio <= 0) {
        $errores[] = 'El precio debe ser mayor a $0.00.';
    }
    if ($stock < 0) {
        $errores[] = 'El stock no puede ser negativo.';
    }
    if ($categoriaId <= 0) {
        $errores[] = 'Selecciona una categoría válida.';
    }

    $imagenActual = null;
    if ($id > 0) {
        $stmtActual = $pdo->prepare('SELECT imagen FROM productos WHERE id = ?');
        $stmtActual->execute([$id]);
        $fila = $stmtActual->fetch();
        $imagenActual = $fila['imagen'] ?? null;
    }

    $nombreImagen = $imagenActual;
    if (empty($errores) && isset($_FILES['imagen'])) {
        try {
            $subida = subirImagenProducto($_FILES['imagen'], $imagenActual);
            $nombreImagen = $subida !== '' ? $subida : $imagenActual;
        } catch (RuntimeException $e) {
            $errores[] = $e->getMessage();
        }
    }

    if (empty($errores)) {
        if ($id > 0) {
            $sql = 'UPDATE productos
                    SET nombre = ?, descripcion = ?, precio = ?, stock = ?, imagen = ?, categoria_id = ?, activo = ?
                    WHERE id = ?';
            $pdo->prepare($sql)->execute([$nombre, $descripcion, $precio, $stock, $nombreImagen, $categoriaId, $activo, $id]);
            header('Location: /admin/productos.php?ok=actualizado');
            exit;
        } else {
            $sql = 'INSERT INTO productos (nombre, descripcion, precio, stock, imagen, categoria_id, activo)
                    VALUES (?, ?, ?, ?, ?, ?, ?)';
            $pdo->prepare($sql)->execute([$nombre, $descripcion, $precio, $stock, $nombreImagen, $categoriaId, $activo]);
            header('Location: /admin/productos.php?ok=creado');
            exit;
        }
    }
}

// Cargar producto si se presionó "Editar"
$productoEditar = null;
if (isset($_GET['editar'])) {
    $stmtEdit = $pdo->prepare('SELECT * FROM productos WHERE id = ? LIMIT 1');
    $stmtEdit->execute([(int) $_GET['editar']]);
    $productoEditar = $stmtEdit->fetch() ?: null;
}

// Consultas para formulario y tabla
$categorias = $pdo->query('SELECT id, nombre FROM categorias ORDER BY id')->fetchAll();
$productos = $pdo->query(
    'SELECT p.*, c.nombre AS categoria_nombre
     FROM productos p
     INNER JOIN categorias c ON p.categoria_id = c.id
     ORDER BY p.id DESC'
)->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventario de Productos | QuickBite</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="/assets/css/quickbite.css" rel="stylesheet">
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark px-4">
    <a class="navbar-brand fw-bold" href="/admin/dashboard.php">☕ QuickBite — Gerencia</a>
    <div class="navbar-nav me-auto">
        <a class="nav-link" href="/admin/dashboard.php">Resumen</a>
        <a class="nav-link active fw-semibold" href="/admin/productos.php">Productos e Inventario</a>
        <a class="nav-link" href="/admin/pedidos.php">Pedidos</a>
    </div>
    <div class="d-flex align-items-center gap-3 text-white">
        <small><?= e($_SESSION['usuario_nombre']) ?></small>
        <a href="/auth/logout.php" class="btn btn-outline-light btn-sm">Cerrar sesión</a>
    </div>
</nav>

<div class="container py-4">
    <?php if ($mensaje !== ''): ?>
        <div class="alert alert-success"><?= e($mensaje) ?></div>
    <?php endif; ?>

    <?php if (!empty($errores)): ?>
        <div class="alert alert-danger">
            <ul class="mb-0 ps-3">
                <?php foreach ($errores as $err): ?>
                    <li><?= e($err) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <div class="row g-4">
        <!-- Formulario Crear / Editar -->
        <div class="col-lg-4">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-dark text-white fw-semibold">
                    <?= $productoEditar ? '✏️ Editar Producto #' . (int) $productoEditar['id'] : '➕ Nuevo Producto' ?>
                </div>
                <div class="card-body">
                    <form method="POST" action="/admin/productos.php" enctype="multipart/form-data">
                        <input type="hidden" name="accion" value="guardar">
                        <input type="hidden" name="id" value="<?= (int) ($productoEditar['id'] ?? 0) ?>">

                        <div class="mb-3">
                            <label class="form-label">Nombre del producto</label>
                            <input type="text" name="nombre" class="form-control"
                                   value="<?= e($productoEditar['nombre'] ?? '') ?>" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Categoría</label>
                            <select name="categoria_id" class="form-select" required>
                                <option value="">Selecciona...</option>
                                <?php foreach ($categorias as $cat): ?>
                                    <option value="<?= (int) $cat['id'] ?>"
                                        <?= ((int) ($productoEditar['categoria_id'] ?? 0) === (int) $cat['id']) ? 'selected' : '' ?>>
                                        <?= e($cat['nombre']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="row g-2 mb-3">
                            <div class="col-6">
                                <label class="form-label">Precio ($ MXN)</label>
                                <input type="number" step="0.01" min="0.50" name="precio" class="form-control"
                                       value="<?= e((string) ($productoEditar['precio'] ?? '')) ?>" required>
                            </div>
                            <div class="col-6">
                                <label class="form-label">Stock inicial</label>
                                <input type="number" min="0" name="stock" class="form-control"
                                       value="<?= e((string) ($productoEditar['stock'] ?? '10')) ?>" required>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Descripción breve</label>
                            <textarea name="descripcion" rows="2" class="form-control"><?= e($productoEditar['descripcion'] ?? '') ?></textarea>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Fotografía (JPG, PNG, WEBP - máx 2MB)</label>
                            <input type="file" name="imagen" accept=".jpg,.jpeg,.png,.webp" class="form-control">
                            <?php if (!empty($productoEditar['imagen'])): ?>
                                <div class="mt-2">
                                    <img src="/uploads/<?= e($productoEditar['imagen']) ?>" alt="Actual" class="rounded border" style="height: 60px;">
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" name="activo" id="activoCheck"
                                <?= (!isset($productoEditar) || (int) $productoEditar['activo'] === 1) ? 'checked' : '' ?>>
                            <label class="form-check-label" for="activoCheck">Disponible en el menú</label>
                        </div>

                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-dark">
                                <?= $productoEditar ? 'Guardar Cambios' : 'Registrar Producto' ?>
                            </button>
                            <?php if ($productoEditar): ?>
                                <a href="/admin/productos.php" class="btn btn-outline-secondary btn-sm">Cancelar edición</a>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Tabla de Inventario -->
        <div class="col-lg-8">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white fw-semibold d-flex justify-content-between align-items-center">
                    <span>Catálogo e Inventario Actual</span>
                    <span class="badge bg-secondary"><?= count($productos) ?> productos</span>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Foto</th>
                                <th>Producto</th>
                                <th>Categoría</th>
                                <th>Precio</th>
                                <th>Stock</th>
                                <th>Estado</th>
                                <th class="text-end">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php if (empty($productos)): ?>
                            <tr>
                                <td colspan="7" class="text-center text-muted py-4">
                                    Aún no hay productos registrados. Agrega el primero desde el formulario.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($productos as $p): ?>
                                <tr>
                                    <td>
                                        <?php if (!empty($p['imagen'])): ?>
                                            <img src="/uploads/<?= e($p['imagen']) ?>" alt="<?= e($p['nombre']) ?>"
                                                 class="rounded object-fit-cover" style="width: 48px; height: 48px;">
                                        <?php else: ?>
                                            <div class="bg-secondary-subtle text-secondary rounded d-flex align-items-center justify-content-center"
                                                 style="width: 48px; height: 48px; font-size: 1.2rem;">☕</div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="fw-semibold"><?= e($p['nombre']) ?></div>
                                        <small class="text-muted"><?= e($p['descripcion'] ?? '') ?></small>
                                    </td>
                                    <td><span class="badge bg-light text-dark border"><?= e($p['categoria_nombre']) ?></span></td>
                                    <td class="fw-semibold">$<?= number_format((float) $p['precio'], 2) ?></td>
                                    <td>
                                        <?php if ((int) $p['stock'] <= 5): ?>
                                            <span class="badge bg-danger"><?= (int) $p['stock'] ?> unid.</span>
                                        <?php else: ?>
                                            <span class="badge bg-success-subtle text-success-emphasis"><?= (int) $p['stock'] ?> unid.</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?= (int) $p['activo'] === 1
                                            ? '<span class="badge bg-primary">Activo</span>'
                                            : '<span class="badge bg-secondary">Oculto</span>' ?>
                                    </td>
                                    <td class="text-end">
                                        <a href="/admin/productos.php?editar=<?= (int) $p['id'] ?>"
                                           class="btn btn-sm btn-outline-primary">Editar</a>
                                        <form method="POST" action="/admin/productos.php" class="d-inline"
                                              onsubmit="return confirm('¿Seguro que deseas eliminar este producto?');">
                                            <input type="hidden" name="accion" value="eliminar">
                                            <input type="hidden" name="id" value="<?= (int) $p['id'] ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-danger">Borrar</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>