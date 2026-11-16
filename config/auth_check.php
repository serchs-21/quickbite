<?php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function requerirLogin(): void {
    if (!isset($_SESSION['usuario_id'])) {
        header('Location: /auth/login.php');
        exit;
    }
}

function requerirRol(string $rolPermitido): void {
    requerirLogin();
    if (($_SESSION['rol_nombre'] ?? '') !== $rolPermitido) {
        header('Location: /index.php');
        exit;
    }
}

function e(string $valor): string {
    return htmlspecialchars($valor, ENT_QUOTES, 'UTF-8');
}