<?php
// Iniciar sesión si no está iniciada ya
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Genera un token HMAC firmado para un ID de préstamo
function generarTokenPrestamo($id_prestamo) {
    $clave_secreta = session_id() . "_BibliotecaOhara_2026";
    return hash_hmac('sha256', (string)$id_prestamo, $clave_secreta);
}

// Verifica si el token recibido por POST coincide con el ID
function validarTokenPrestamo($id_prestamo, $token_recibido) {
    if (empty($token_recibido)) return false;
    $token_esperado = generarTokenPrestamo($id_prestamo);
    return hash_equals($token_esperado, $token_recibido);
}

// Comprueba si quedan ejemplares disponibles para el libro del ejemplar seleccionado
function libroTieneEjemplaresDisponibles($conn, $id_ejemplar, $id_prestamo_excluir = 0) {
    if (!$conn) return false;

    // 1. Obtener el id_libro al que pertenece el ejemplar
    $stmt = $conn->prepare("SELECT id_libro FROM ejemplares_juanjo WHERE id_ejemplar = ? AND fecha_baja IS NULL");
    $stmt->bind_param("i", $id_ejemplar);
    $stmt->execute();
    $res = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$res) return false;
    $id_libro = (int)$res['id_libro'];

    // 2. Consulta para comparar el total de ejemplares frente a los prestados
    $query = "SELECT 
                (SELECT COUNT(*) FROM ejemplares_juanjo WHERE id_libro = ? AND fecha_baja IS NULL) AS total,
                (SELECT COUNT(*) FROM prestamos_juanjorpa p 
                 INNER JOIN ejemplares_juanjo ej ON p.id_ejemplar = ej.id_ejemplar 
                 WHERE ej.id_libro = ? 
                   AND p.fecha_devolucion IS NULL 
                   AND p.fecha_baja IS NULL " . ($id_prestamo_excluir > 0 ? "AND p.id_prestamo != ?" : "") . ") AS prestados";

    $stmt = $conn->prepare($query);
    if ($id_prestamo_excluir > 0) {
        $stmt->bind_param("iii", $id_libro, $id_libro, $id_prestamo_excluir);
    } else {
        $stmt->bind_param("ii", $id_libro, $id_libro);
    }
    $stmt->execute();
    $data = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    return ((int)$data['prestados'] < (int)$data['total']);
}

// Obtener listado de préstamos con buscador opcional
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

// Obtener un préstamo por su ID
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

// Cambiar estado de baja lógica / reactivación
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

// Crear un nuevo préstamo
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

// Obtener ejemplares y stock disponible para el select
function obtenerEjemplaresParaSelect($conn, $id_ejemplar_actual = null) {
    if (!$conn) return [];

    $param_ejemplar = $id_ejemplar_actual ? (int)$id_ejemplar_actual : 0;

    $query = "SELECT 
                l.id_libro,
                l.titulo AS titulo_libro,
                MIN(e.id_ejemplar) AS id_ejemplar,
                COUNT(e.id_ejemplar) AS disponibles
              FROM libros_manuel l
              INNER JOIN ejemplares_juanjo e ON l.id_libro = e.id_libro
              WHERE l.fecha_baja IS NULL 
                AND e.fecha_baja IS NULL
                AND (
                    e.id_ejemplar NOT IN (
                        SELECT p.id_ejemplar 
                        FROM prestamos_juanjorpa p 
                        WHERE p.fecha_devolucion IS NULL 
                          AND p.fecha_baja IS NULL
                    )
                    OR e.id_ejemplar = ?
                )
              GROUP BY l.id_libro, l.titulo
              ORDER BY l.titulo ASC";

    $stmt = $conn->prepare($query);
    if (!$stmt) return [];

    $stmt->bind_param("i", $param_ejemplar);
    $stmt->execute();
    $resultado = $stmt->get_result();
    $libros = $resultado ? $resultado->fetch_all(MYSQLI_ASSOC) : [];
    $stmt->close();

    return $libros;
}

// Obtener socios activos para el select
function obtenerSociosParaSelect($conn) {
    if (!$conn) return [];

    $query = "SELECT id_socio, CONCAT(nombre, ' ', COALESCE(apellidos, '')) AS nombre_socio 
              FROM socios_ricardo 
              WHERE fecha_baja IS NULL
              ORDER BY nombre ASC, apellidos ASC";
    $resultado = $conn->query($query);

    return $resultado ? $resultado->fetch_all(MYSQLI_ASSOC) : [];
}

// Editar préstamo existente
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

// Registrar la devolución de un préstamo
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

// Actualizar la ruta de la imagen del préstamo
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

// Guardar o reemplazar archivo de imagen en disco
function guardarImagenPrestamo($id_prestamo, $archivo, $imagen_anterior = null) {
    // 1. Validar que el archivo exista y no tenga errores
    if (empty($archivo) || $archivo['error'] !== UPLOAD_ERR_OK) {
        return $imagen_anterior;
    }

    // 2. Validar formato por extensión
    $extension = strtolower(pathinfo($archivo['name'], PATHINFO_EXTENSION));
    $extensiones_permitidas = ['jpg', 'jpeg', 'png', 'webp'];
    
    if (!in_array($extension, $extensiones_permitidas)) {
        return $imagen_anterior;
    }

    // 3. Asegurar que el directorio de destino existe
    $dir_destino = __DIR__ . '/img/prestamos/';
    if (!is_dir($dir_destino)) {
        mkdir($dir_destino, 0755, true);
    }

    // 4. Eliminar la imagen anterior si existía
    if (!empty($imagen_anterior)) {
        $ruta_anterior = __DIR__ . '/' . ltrim($imagen_anterior, '/');
        if (file_exists($ruta_anterior)) {
            @unlink($ruta_anterior);
        }
    }

    // 5. Definir rutas y mover el nuevo archivo
    $nombre_archivo = 'P' . (int)$id_prestamo . '.' . $extension;
    $ruta_absoluta  = $dir_destino . $nombre_archivo;
    $ruta_relativa  = 'img/prestamos/' . $nombre_archivo;

    return @move_uploaded_file($archivo['tmp_name'], $ruta_absoluta) 
        ? $ruta_relativa 
        : $imagen_anterior;
}