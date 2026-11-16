<?php
declare(strict_types=1);

function subirImagenProducto(array $archivo, ?string $imagenAnterior = null): string
{
    if (($archivo['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return $imagenAnterior ?? '';
    }

    if ($archivo['error'] !== UPLOAD_ERR_OK) {
        throw new RuntimeException('Error al subir el archivo al servidor.');
    }

    if ($archivo['size'] > 2 * 1024 * 1024) {
        throw new RuntimeException('La imagen no debe pesar más de 2 MB.');
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($archivo['tmp_name']);

    $permitidos = [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/webp' => 'webp',
    ];

    if (!isset($permitidos[$mime])) {
        throw new RuntimeException('Formato inválido. Solo se permiten imágenes JPG, PNG o WEBP.');
    }

    $directorio = __DIR__ . '/../uploads';
    if (!is_dir($directorio)) {
        mkdir($directorio, 0755, true);
    }

    $nombreNuevo = bin2hex(random_bytes(8)) . '.' . $permitidos[$mime];
    $rutaDestino = $directorio . '/' . $nombreNuevo;

    if (!move_uploaded_file($archivo['tmp_name'], $rutaDestino)) {
        throw new RuntimeException('No se pudo guardar la imagen en la carpeta uploads.');
    }

    eliminarImagenArchivo($imagenAnterior);

    return $nombreNuevo;
}

function eliminarImagenArchivo(?string $nombreArchivo): void
{
    if ($nombreArchivo !== null && $nombreArchivo !== '') {
        $ruta = __DIR__ . '/../uploads/' . basename($nombreArchivo);
        if (is_file($ruta)) {
            unlink($ruta);
        }
    }
}