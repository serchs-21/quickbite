<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth_check.php';

if (isset($_SESSION['usuario_id'])) {
    $destino = ($_SESSION['rol_nombre'] ?? '') === 'Gerente'
        ? '/admin/dashboard.php'
        : '/cliente/menu.php';
    header('Location: ' . $destino);
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($email === '' || $password === '') {
        $error = 'Ingresa tu correo electrónico y contraseña.';
    } else {
        $stmt = $pdo->prepare(
            'SELECT u.id, u.nombre, u.password_hash, r.nombre AS rol_nombre
             FROM usuarios u
             INNER JOIN roles r ON u.rol_id = r.id
             WHERE u.email = ? LIMIT 1'
        );
        $stmt->execute([$email]);
        $usuario = $stmt->fetch();

        if ($usuario && password_verify($password, $usuario['password_hash'])) {
            session_regenerate_id(true);
            $_SESSION['usuario_id']     = (int) $usuario['id'];
            $_SESSION['usuario_nombre'] = $usuario['nombre'];
            $_SESSION['rol_nombre']     = $usuario['rol_nombre'];

            $destino = $usuario['rol_nombre'] === 'Gerente'
                ? '/admin/dashboard.php'
                : '/cliente/menu.php';
            header('Location: ' . $destino);
            exit;
        }
        $error = 'Correo o contraseña incorrectos.';
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión | QuickBite Coffee House</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="/assets/css/quickbite.css" rel="stylesheet">
</head>
<body class="auth-bg">
<div class="container">
    <div class="row justify-content-center">
        <div class="col-lg-9 col-xl-8">
            <div class="auth-card row g-0">
                <div class="col-md-5 auth-banner">
                    <div>
                        <span class="qb-pill-tag mb-3">Tostado Artesanal</span>
                        <h2 class="fw-bold mb-3">
                            <a href="/index.php" class="text-white text-decoration-none">☕ QuickBite</a>
                        </h2>
                        <p class="text-white-50 small">
                            El aroma del café recién molido, panadería horneada cada mañana y tu orden lista sin filas.
                        </p>
                    </div>
                    <div class="border-top border-white border-opacity-25 pt-3 small text-white-50">
                        <div>✨ Granos 100% seleccionados</div>
                        <div>🥐 Repostería artesanal del día</div>
                    </div>
                </div>

                <div class="col-md-7 p-4 p-md-5">
                    <h3 class="fw-bold mb-1">Bienvenido de vuelta</h3>
                    <p class="text-muted small mb-4">Inicia sesión para ordenar o gestionar la barra.</p>

                    <?php if (isset($_GET['registrado'])): ?>
                        <div class="alert alert-success py-2 small">
                            ¡Cuenta creada con éxito! Ya puedes iniciar sesión.
                        </div>
                    <?php endif; ?>

                    <?php if ($error !== ''): ?>
                        <div class="alert alert-danger py-2 small"><?= e($error) ?></div>
                    <?php endif; ?>

                    <form method="POST" action="/auth/login.php">
                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Correo electrónico</label>
                            <input type="email" name="email" class="form-control" placeholder="tu@correo.com" required>
                        </div>
                        <div class="mb-4">
                            <label class="form-label small fw-semibold">Contraseña</label>
                            <input type="password" name="password" class="form-control" placeholder="••••••••" required>
                        </div>
                        <button type="submit" class="btn btn-dark w-100 py-2">Entrar a QuickBite</button>
                    </form>

                    <div class="text-center mt-4 small">
                        <span class="text-muted">¿Aún no eres miembro?</span>
                        <a href="/auth/register.php" class="fw-bold text-decoration-none" style="color: #00754a;">
                            Regístrate aquí
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>