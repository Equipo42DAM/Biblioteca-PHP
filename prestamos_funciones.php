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

//Verifica si el token recibido por POST coincide con el ID
function validarTokenPrestamo($id_prestamo, $token_recibido) {
    if (empty($token_recibido)) return false;
    $token_esperado = generarTokenPrestamo($id_prestamo);
    // hash_equals evita ataques de tiempos (timing attacks)
    return hash_equals($token_esperado, $token_recibido);
}


// Contiene únicamente las funciones que interactúan con la BD
function obtenerTodosLosPrestamos($conn) {
    if (!$conn) return false;

    $query = "SELECT p.*, l.titulo AS titulo_libro, CONCAT(s.nombre, ' ', s.apellidos) AS nombre_socio
              FROM prestamos_juanjorpa p
              JOIN ejemplares_juanjo e ON p.id_ejemplar = e.id_ejemplar
              JOIN libros_manuel l ON e.id_libro = l.id_libro
              JOIN socios_ricardo s ON p.id_socio = s.id_socio";
    $resultado = $conn->query($query);
    return $resultado ? $resultado->fetch_all(MYSQLI_ASSOC) : [];
}

function obtenerPrestamoPorId($conn, $id) {
    if (!$conn) return false;

    $id = (int)$id;
    $query = "SELECT p.*, l.titulo AS titulo_libro, CONCAT(s.nombre, ' ', s.apellidos) AS nombre_socio
              FROM prestamos_juanjorpa p
              JOIN ejemplares_juanjo e ON p.id_ejemplar = e.id_ejemplar
              JOIN libros_manuel l ON e.id_libro = l.id_libro
              JOIN socios_ricardo s ON p.id_socio = s.id_socio
              WHERE p.id_prestamo = ?";
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

function darDeBajaPrestamo($conn, $id) {
    if (!$conn) return false;

    $id = (int)$id;
    $fecha_actual = date('Y-m-d H:i:s');

    $query = "UPDATE prestamos_juanjorpa SET fecha_baja = ? WHERE id_prestamo = ? AND fecha_baja IS NULL";
    $stmt = $conn->prepare($query);

    if ($stmt) {
        $stmt->bind_param("si", $fecha_actual, $id);
        $resultado = $stmt->execute();
        $stmt->close();
        return $resultado;
    }
    return false;
}
