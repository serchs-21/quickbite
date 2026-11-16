<?php
declare(strict_types=1);
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/auth_check.php';

// Cargar hasta 4 productos estrella activos desde MariaDB
$productosEstrella = $pdo->query(
    'SELECT p.*, c.nombre AS categoria_nombre
     FROM productos p
     INNER JOIN categorias c ON p.categoria_id = c.id
     WHERE p.activo = 1
     ORDER BY p.id ASC
     LIMIT 4'
)->fetchAll();

$estaLogueado = isset($_SESSION['usuario_id']);
$rutaPanel = ($_SESSION['rol_nombre'] ?? '') === 'Gerente'
    ? '/admin/dashboard.php'
    : '/cliente/menu.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>QuickBite Coffee House | Tostado Artesanal</title>
    <link rel="icon" type="image/png" href="/assets/img/isc.png?v=2">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="/assets/css/quickbite.css" rel="stylesheet">
    <style>
        /* Estilos exclusivos de la portada editorial */
        .qb-top-nav {
            background-color: #ffffff;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.06);
            padding: 0.85rem 0;
        }
        .qb-nav-link {
            color: #1f2421;
            font-weight: 700;
            font-size: 0.82rem;
            letter-spacing: 1.2px;
            text-transform: uppercase;
            text-decoration: none;
            transition: color 0.2s ease;
        }
        .qb-nav-link:hover {
            color: #00754a;
        }
        .btn-qb-outline {
            border: 1.5px solid #1f2421;
            color: #1f2421;
            background: transparent;
            border-radius: 50px;
            font-weight: 600;
            font-size: 0.88rem;
            padding: 0.42rem 1.15rem;
            text-decoration: none;
            transition: all 0.2s ease;
        }
        .btn-qb-outline:hover {
            background-color: rgba(31, 36, 33, 0.08);
            color: #1f2421;
        }
        .btn-qb-solid {
            border: 1.5px solid #1f2421;
            background-color: #1f2421;
            color: #ffffff;
            border-radius: 50px;
            font-weight: 600;
            font-size: 0.88rem;
            padding: 0.42rem 1.2rem;
            text-decoration: none;
            transition: all 0.2s ease;
        }
        .btn-qb-solid:hover {
            background-color: #1e3932;
            border-color: #1e3932;
            color: #ffffff;
        }
        .qb-split-section {
            border-radius: 6px;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(30, 57, 50, 0.08);
        }
        .qb-split-green {
            background-color: #006241;
            color: #ffffff;
        }
        .qb-split-crimson {
            background-color: #a62c32;
            color: #ffffff;
        }
        .qb-split-visual {
            min-height: 360px;
            background-size: cover;
            background-position: center;
            position: relative;
        }
        .qb-banner-btn {
            border: 1.5px solid #ffffff;
            color: #ffffff;
            border-radius: 50px;
            padding: 0.5rem 1.5rem;
            font-weight: 600;
            text-decoration: none;
            display: inline-block;
            transition: all 0.2s ease;
        }
        .qb-banner-btn:hover {
            background-color: rgba(255, 255, 255, 0.16);
            color: #ffffff;
        }
        .qb-footer {
            background-color: #ffffff;
            border-top: 1px solid #e5dfd5;
            color: #5c554e;
        }
    </style>
</head>
<body style="background-color: #faf8f5;">

<!-- Barra Superior Blanca Estilo Boutique -->
<header class="qb-top-nav sticky-top">
    <div class="container d-flex justify-content-between align-items-center">
        <div class="d-flex align-items-center gap-4">
            <!-- Logo Emblemático QuickBite -->
            <a href="/index.php" class="d-flex align-items-center gap-2 text-decoration-none">
                <svg width="46" height="46" viewBox="0 0 64 64" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <circle cx="32" cy="32" r="30" fill="#00754a"/>
                    <circle cx="32" cy="32" r="26" stroke="#d4e9e2" stroke-width="1.5" stroke-dasharray="3 2"/>
                    <path d="M21 26H41V36C41 41.5228 36.5228 46 31 46C25.4772 46 21 41.5228 21 36V26Z" fill="#ffffff"/>
                    <path d="M41 29H44C46.2091 29 48 30.7909 48 33C48 35.2091 46.2091 37 44 37H41" stroke="#ffffff" stroke-width="3" stroke-linecap="round"/>
                    <path d="M26 20C26 18 28 17 28 15M32 20C32 18 34 17 34 15M38 20C38 18 40 17 40 15" stroke="#d4e9e2" stroke-width="2" stroke-linecap="round"/>
                </svg>
                <span class="font-serif fw-bold fs-4" style="color: #1e3932;">QuickBite</span>
            </a>

            <nav class="d-none d-md-flex align-items-center gap-4">
                <a href="#productos-estrella" class="qb-nav-link">Menú Estrella</a>
                <a href="#experiencia" class="qb-nav-link">Nuestra Barra</a>
            </nav>
        </div>

        <!-- Botones Derecha (Iniciar sesión / Registrarse) -->
        <div class="d-flex align-items-center gap-2">
            <?php if ($estaLogueado): ?>
                <a href="<?= e($rutaPanel) ?>" class="btn-qb-solid">Ir a mi Panel →</a>
                <a href="/auth/logout.php" class="btn-qb-outline">Salir</a>
            <?php else: ?>
                <a href="/auth/login.php" class="btn-qb-outline">Iniciar sesión</a>
                <a href="/auth/register.php" class="btn-qb-solid">Registrarse</a>
            <?php endif; ?>
        </div>
    </div>
</header>

<main class="container my-4">
    <!-- Bloque 1: Banner Dividido Verde Bosque -->
    <section class="qb-split-section row g-0 mb-4">
        <div class="col-md-6 qb-split-visual d-flex align-items-center justify-content-center"
             style="background-image: linear-gradient(rgba(30,57,50,0.22), rgba(30,57,50,0.22)), url('https://images.unsplash.com/photo-1509042239860-f550ce710b93?auto=format&fit=crop&w=900&q=80');">
        </div>
        <div class="col-md-6 qb-split-green d-flex flex-column justify-content-center align-items-center text-center p-4 p-lg-5">
            <span class="qb-pill-tag mb-3">Edición Artesanal</span>
            <h1 class="fw-bold text-uppercase mb-3" style="letter-spacing: 1px;">El aroma es compartir</h1>
            <p class="fs-5 mb-4 opacity-90" style="max-width: 420px;">
                Granos seleccionados de altura, tostado medio y notas dulces de cacao listos para acompañarte hoy.
            </p>
            <a href="<?= $estaLogueado ? e($rutaPanel) : '/auth/login.php' ?>" class="qb-banner-btn">
                Ordenar en línea
            </a>
        </div>
    </section>

    <!-- Bloque 2: Banner Dividido Rojo Caramelo / Especias -->
    <section id="experiencia" class="qb-split-section row g-0 mb-5">
        <div class="col-md-6 qb-split-crimson d-flex flex-column justify-content-center align-items-center text-center p-4 p-lg-5 order-2 order-md-1">
            <span class="qb-pill-tag mb-3" style="background: rgba(255,255,255,0.15); color: #fff;">Favoritos de Barra</span>
            <h2 class="fw-bold text-uppercase mb-3" style="letter-spacing: 1px;">Con toque de canela & caramelo</h2>
            <p class="fs-5 mb-4 opacity-90" style="max-width: 420px;">
                Desde un Capuchino cremoso hasta un Cold Brew infusionado por 16 horas y panadería tibia del día.
            </p>
            <a href="<?= $estaLogueado ? e($rutaPanel) : '/auth/register.php' ?>" class="qb-banner-btn">
                Únete y pide sin filas
            </a>
        </div>
        <div class="col-md-6 qb-split-visual order-1 order-md-2"
             style="background-image: linear-gradient(rgba(44,30,22,0.18), rgba(44,30,22,0.18)), url('https://images.unsplash.com/photo-1495474472287-4d71bcdd2085?auto=format&fit=crop&w=900&q=80');">
        </div>
    </section>

    <!-- Sección Productos Estrella -->
    <section id="productos-estrella" class="py-3 mb-5">
        <div class="text-center mb-4">
            <span class="small fw-bold text-uppercase" style="color: #00754a; letter-spacing: 1.5px;">
                Selección del Barista
            </span>
            <h2 class="fw-bold mt-1" style="color: #1e3932;">Nuestros Productos Estrella</h2>
            <p class="text-muted mx-auto" style="max-width: 540px;">
                Los más pedidos de nuestra barra. Inicia sesión para armar tu orden y recogerla recién hecha.
            </p>
        </div>

        <div class="row g-4">
            <?php foreach ($productosEstrella as $p): ?>
                <div class="col-sm-6 col-lg-3">
                    <div class="card product-card h-100">
                        <div class="product-img-wrap">
                            <?php if (!empty($p['imagen'])): ?>
                                <img src="/uploads/<?= e($p['imagen']) ?>" alt="<?= e($p['nombre']) ?>">
                            <?php else: ?>
                                <div class="w-100 h-100 d-flex align-items-center justify-content-center" style="font-size: 3.4rem;">
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
                            <div class="d-flex justify-content-between align-items-center pt-2 border-top mt-2">
                                <span class="price-tag">$<?= number_format((float) $p['precio'], 2) ?></span>
                                <a href="<?= $estaLogueado ? e($rutaPanel) : '/auth/login.php' ?>" class="btn btn-dark btn-sm">
                                    Pedir ahora
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </section>
</main>

<!-- Pie de Página Oficial -->
<footer class="qb-footer py-4 mt-5">
    <div class="container">
        <div class="row align-items-center gy-3">
            <div class="col-md-6 text-center text-md-start">
                <div class="fw-bold" style="color: #1e3932;">☕ QuickBite Coffee House</div>
                <small class="d-block mt-1">
                    © <?= date('Y') ?> QuickBite. Todos los derechos reservados.
                </small>
            </div>
            <div class="col-md-6 text-center text-md-end">
                <div class="d-inline-flex align-items-center justify-content-center justify-content-md-end gap-2">
                    <span class="fw-semibold" style="color: #1e3932;">
                        Sergio &amp; Kenia
                    </span>
                    <img src="/assets/img/isc.png" alt="Logo ISC" style="height: 38px; width: auto; object-fit: contain;">
                </div>
                <small class="text-muted d-block mt-1">
                    U.A. Cuichapa — Séptimo Semestre Escolarizado
                </small>
            </div>
        </div>
    </div>
</footer>

</body>
</html>