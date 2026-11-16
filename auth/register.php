<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth_check.php';

if (isset($_SESSION['usuario_id'])) {
    header('Location: /index.php');
    exit;
}

$errores = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre   = trim($_POST['nombre'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['confirmar_password'] ?? '';

    if ($nombre === '') {
        $errores[] = 'Escribe tu nombre completo.';
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errores[] = 'Ingresa un correo electrónico válido.';
    }
    if (strlen($password) < 6) {
        $errores[] = 'La contraseña debe tener al menos 6 caracteres.';
    }
    if ($password !== $confirm) {
        $errores[] = 'Las contraseñas no coinciden.';
    }

    if (empty($errores)) {
        $stmtCheck = $pdo->prepare('SELECT id FROM usuarios WHERE email = ? LIMIT 1');
        $stmtCheck->execute([$email]);
        if ($stmtCheck->fetch()) {
            $errores[] = 'Este correo ya está registrado.';
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmtIns = $pdo->prepare(
                'INSERT INTO usuarios (nombre, email, password_hash, rol_id) VALUES (?, ?, ?, 2)'
            );
            $stmtIns->execute([$nombre, $email, $hash]);
            header('Location: /auth/login.php?registrado=1');
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Crear Cuenta | QuickBite Coffee House</title>
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
                        <span class="qb-pill-tag mb-3">QuickBite Rewards</span>
                        <h2 class="fw-bold mb-3">☕ Únete al Club</h2>
                        <p class="text-white-50 small">
                            Personaliza tus bebidas favoritas, arma tu pedido en segundos y recógelo recién preparado.
                        </p>
                    </div>
                    <div class="border-top border-white border-opacity-25 pt-3 small text-white-50">
                        <div>🌿 Ingredientes frescos</div>
                        <div>⚡ Pedido exprés en barra</div>
                    </div>
                </div>

                <div class="col-md-7 p-4 p-md-5">
                    <h3 class="fw-bold mb-1">Crea tu cuenta</h3>
                    <p class="text-muted small mb-3">Disfruta la experiencia QuickBite.</p>

                    <?php if (!empty($errores)): ?>
                        <div class="alert alert-danger py-2 small">
                            <ul class="mb-0 ps-3">
                                <?php foreach ($errores as $err): ?>
                                    <li><?= e($err) ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="/auth/register.php">
                        <div class="mb-2">
                            <label class="form-label small fw-semibold">Nombre completo</label>
                            <input type="text" name="nombre" class="form-control" value="<?= e($_POST['nombre'] ?? '') ?>" required>
                        </div>
                        <div class="mb-2">
                            <label class="form-label small fw-semibold">Correo electrónico</label>
                            <input type="email" name="email" class="form-control" value="<?= e($_POST['email'] ?? '') ?>" required>
                        </div>
                        <div class="row g-2 mb-4">
                            <div class="col-6">
                                <label class="form-label small fw-semibold">Contraseña</label>
                                <input type="password" name="password" class="form-control" required>
                            </div>
                            <div class="col-6">
                                <label class="form-label small fw-semibold">Confirmar</label>
                                <input type="password" name="confirmar_password" class="form-control" required>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-dark w-100 py-2">Registrarme</button>
                    </form>

                    <div class="text-center mt-3 small">
                        <span class="text-muted">¿Ya tienes cuenta?</span>
                        <a href="/auth/login.php" class="fw-bold text-decoration-none" style="color: #00754a;">
                            Inicia sesión
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>