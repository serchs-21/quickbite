# QuickBite — Sistema de Gestión y Pedidos para Cafetería

Aplicación web desarrollada en PHP 8.3 nativo, MariaDB (PDO) y Bootstrap 5.
Autores: Sergio & Kenia — U.A. Cuichapa (Séptimo Semestre Escolarizado).

## Instalación y Ejecución Local
1. Clonar el repositorio y entrar a la carpeta quickbite.
2. Crear la base de datos e importar schema.sql:
   mysql -u root -p -e "CREATE DATABASE quickbite CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
   mysql -u root -p quickbite < schema.sql
3. Iniciar el servidor local:
   php -S localhost:8000
4. Abrir en el navegador: http://localhost:8000

## Credenciales de Prueba
- Gerente: gerente@quickbite.com / Contraseña: password
- Cliente: kenia@quickbite.com / Contraseña: password
(También se pueden registrar nuevos clientes desde el botón Registrarse en la portada).
