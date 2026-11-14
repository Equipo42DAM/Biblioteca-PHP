<?php
// Iniciar sesión si no está iniciada ya
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Genera un token HMAC firmado para un ID de préstamo
function generarTokenPrestamo($id_prestamo) {
    // La clave secreta es única por usuario y sesión activa
    $clave_secreta = session_id() . "_BibliotecaOhara_2026";
    return hash_hmac('sha256', (string)$id_prestamo, $clave_secreta);
}

// Verifica si el token recibido por POST coincide con el ID
function validarTokenPrestamo($id_prestamo, $token_recibido) {
    if (empty($token_recibido)) return false;
    $token_esperado = generarTokenPrestamo($id_prestamo);
    // hash_equals evita ataques de tiempos (timing attacks)
    return hash_equals($token_esperado, $token_recibido);
}

// Funciones que interactúan con la BD
function obtenerTodosLosPrestamos($conn, $busqueda = '', $incluir_bajas = false) {
    if (!$conn) return [];

    $query = "SELECT p.*, 
                     l.titulo AS titulo_libro, 
                     CONCAT(s.nombre, ' ', COALESCE(s.apellidos, '')) AS nombre_socio
              FROM prestamos_juanjorpa p
              INNER JOIN ejemplares_juanjo e ON p.id_ejemplar = e.id_ejemplar
              INNER JOIN libros_manuel l ON e.id_libro = l.id_libro
              INNER JOIN socios_ricardo s ON p.id_socio = s.id_socio
              WHERE 1=1";

    if (!$incluir_bajas) {
        $query .= " AND p.fecha_baja IS NULL";
    }

    $busqueda = trim($busqueda);

    if ($busqueda !== '') {
        $query .= " AND (l.titulo LIKE ? 
                      OR s.nombre LIKE ? 
                      OR s.apellidos LIKE ?
                      OR p.observaciones LIKE ? 
                      OR p.id_prestamo LIKE ?)";
    }

    $query .= " ORDER BY p.id_prestamo ASC";

    $stmt = $conn->prepare($query);
    if (!$stmt) return [];

    if ($busqueda !== '') {
        $param = "%{$busqueda}%";
        $stmt->bind_param("sssss", $param, $param, $param, $param, $param);
    }

    $stmt->execute();
    $resultado = $stmt->get_result();
    $prestamos = $resultado ? $resultado->fetch_all(MYSQLI_ASSOC) : [];
    $stmt->close();

    return $prestamos;
}

function obtenerPrestamoPorId($conn, $id, $permitir_baja = false) {
    if (!$conn) return false;

    $id = (int)$id;
    $query = "SELECT p.*, 
                     l.titulo AS titulo_libro, 
                     CONCAT(s.nombre, ' ', COALESCE(s.apellidos, '')) AS nombre_socio
              FROM prestamos_juanjorpa p
              JOIN ejemplares_juanjo e ON p.id_ejemplar = e.id_ejemplar
              JOIN libros_manuel l ON e.id_libro = l.id_libro
              JOIN socios_ricardo s ON p.id_socio = s.id_socio
              WHERE p.id_prestamo = ?";

    if (!$permitir_baja) {
        $query .= " AND p.fecha_baja IS NULL";
    }

    $stmt = $conn->prepare($query);
    if ($stmt) {
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $res = $stmt->get_result();
        $datos = $res->fetch_assoc();
        $stmt->close();
        return $datos;
    }
    return null;
}

function alternarEstadoPrestamo($conn, $id) {
    if (!$conn) return false;

    $id = (int)$id;
    $query = "UPDATE prestamos_juanjorpa 
              SET fecha_baja = IF(fecha_baja IS NOT NULL, NULL, NOW()) 
              WHERE id_prestamo = ?";
    $stmt = $conn->prepare($query);

    if ($stmt) {
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $filas_afectadas = $stmt->affected_rows;
        $stmt->close();
        return $filas_afectadas > 0;
    }
    return false;
}

function crearPrestamo($conn, $datos) {
    if (!$conn) return false;

    $query = "INSERT INTO prestamos_juanjorpa 
              (id_ejemplar, id_socio, fecha_prestamo, fecha_limite, fecha_devolucion, tiene_fianza, importe_fianza, url_imagen, observaciones) 
              VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($query);

    if ($stmt) {
        $stmt->bind_param(
            "iisssidss",
            $datos['id_ejemplar'],
            $datos['id_socio'],
            $datos['fecha_prestamo'],
            $datos['fecha_limite'],
            $datos['fecha_devolucion'],
            $datos['tiene_fianza'],
            $datos['importe_fianza'],
            $datos['url_imagen'],
            $datos['observaciones']
        );

        if ($stmt->execute()) {
            $nuevo_id = $conn->insert_id;
            $stmt->close();
            return $nuevo_id;
        }
        $stmt->close();
    }
    return false;
}

function obtenerEjemplaresParaSelect($conn) {
    if (!$conn) return [];

    $query = "SELECT e.id_ejemplar, l.titulo AS titulo_libro 
              FROM ejemplares_juanjo e
              INNER JOIN libros_manuel l ON e.id_libro = l.id_libro
              WHERE e.fecha_baja IS NULL
              ORDER BY l.titulo ASC, e.id_ejemplar ASC";
    $resultado = $conn->query($query);

    return $resultado ? $resultado->fetch_all(MYSQLI_ASSOC) : [];
}

function obtenerSociosParaSelect($conn) {
    if (!$conn) return [];

    $query = "SELECT id_socio, CONCAT(nombre, ' ', COALESCE(apellidos, '')) AS nombre_socio 
              FROM socios_ricardo 
              WHERE fecha_baja IS NULL
              ORDER BY nombre ASC, apellidos ASC";
    $resultado = $conn->query($query);

    return $resultado ? $resultado->fetch_all(MYSQLI_ASSOC) : [];
}

function editarPrestamo($conn, $id_prestamo, $datos) {
    if (!$conn) return false;

    $query = "UPDATE prestamos_juanjorpa 
              SET id_ejemplar = ?, 
                  id_socio = ?, 
                  fecha_prestamo = ?, 
                  fecha_limite = ?, 
                  fecha_devolucion = ?, 
                  tiene_fianza = ?, 
                  importe_fianza = ?, 
                  url_imagen = ?, 
                  observaciones = ?
              WHERE id_prestamo = ? 
                AND fecha_baja IS NULL";
    $stmt = $conn->prepare($query);

    if ($stmt) {
        $stmt->bind_param(
            "iisssidssi",
            $datos['id_ejemplar'],
            $datos['id_socio'],
            $datos['fecha_prestamo'],
            $datos['fecha_limite'],
            $datos['fecha_devolucion'],
            $datos['tiene_fianza'],
            $datos['importe_fianza'],
            $datos['url_imagen'],
            $datos['observaciones'],
            $id_prestamo
        );

        $resultado = $stmt->execute();
        $stmt->close();
        return $resultado;
    }
    return false;
}

function devolverPrestamo($conn, $id_prestamo, $fecha_devolucion = null) {
    $fecha = !empty($fecha_devolucion) ? $fecha_devolucion : date('Y-m-d');
    $sql = "UPDATE prestamos_juanjorpa 
            SET fecha_devolucion = ? 
            WHERE id_prestamo = ? AND fecha_baja IS NULL";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "si", $fecha, $id_prestamo);
    $ok = mysqli_stmt_execute($stmt);
    $filas = mysqli_stmt_affected_rows($stmt);
    mysqli_stmt_close($stmt);

    return $ok && $filas > 0;
}

function actualizarImagenPrestamo($conn, $id_prestamo, $url_imagen) {
    if (!$conn) return false;

    $id_prestamo = (int)$id_prestamo;
    $query = "UPDATE prestamos_juanjorpa SET url_imagen = ? WHERE id_prestamo = ?";
    $stmt = $conn->prepare($query);

    if ($stmt) {
        $stmt->bind_param("si", $url_imagen, $id_prestamo);
        $resultado = $stmt->execute();
        $stmt->close();
        return $resultado;
    }
    return false;
}

function guardarImagenPrestamo($id_prestamo, $archivo, $imagen_anterior = null) {
    // Si no se subió ningún archivo nuevo, conservamos la ruta que ya tenía
    if (!isset($archivo) || $archivo['error'] !== UPLOAD_ERR_OK) {
        return $imagen_anterior;
    }

    $permitidos = ['image/jpeg', 'image/png', 'image/webp'];
    $tipo_archivo = mime_content_type($archivo['tmp_name']);

    if (!in_array($tipo_archivo, $permitidos)) {
        return $imagen_anterior;
    }

    $directorio_destino = 'img/prestamos/';
    if (!is_dir($directorio_destino)) {
        mkdir($directorio_destino, 0755, true);
    }

    // Si existía una imagen previa en disco (por ejemplo al editar), la borramos
    if (!empty($imagen_anterior) && file_exists($imagen_anterior)) {
        unlink($imagen_anterior);
    }

    $extension = strtolower(pathinfo($archivo['name'], PATHINFO_EXTENSION));
    $ruta_completa = $directorio_destino . 'P' . (int)$id_prestamo . '.' . $extension;

    if (move_uploaded_file($archivo['tmp_name'], $ruta_completa)) {
        return $ruta_completa;
    }

    return $imagen_anterior;
}