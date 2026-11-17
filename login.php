<?php
require_once 'auth.php';

if (!empty($_SESSION['usuario']['id_usuario'])) {
    header('Location: index.php');
    exit;
}

require_once 'config.php';

$error = '';
$username = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';

    if (!is_string($token) || !hash_equals($_SESSION['csrf_token'], $token)) {
        $error = 'No se pudo validar el formulario. Inténtalo de nuevo.';
    } else {
        $username = $_POST['username'] ?? '';
        $password = $_POST['password'] ?? '';

        if (!is_string($username) || !is_string($password)) {
            $username = '';
            $error = 'Usuario o contraseña incorrectos.';
        } else {
            $username = trim($username);

            try {
                $conexion = getMySQLiConnection();
                // Buscamos al usuario sin filtrar la baja todavía para poder identificarla
                $stmt = $conexion->prepare(
                    'SELECT id_usuario, username, password, nombre_completo, rol, fecha_baja, url_imagen
                     FROM usuarios
                     WHERE username = ?
                     LIMIT 1'
                );
                $stmt->bind_param('s', $username);
                $stmt->execute();
                $usuario = $stmt->get_result()->fetch_assoc();
                $stmt->close();

                if (!$usuario) {
                    $error = 'El usuario no existe.';
                } elseif (!empty($usuario['fecha_baja']) && $usuario['fecha_baja'] !== '0000-00-00 00:00:00') {
                    $error = 'Este usuario está dado de baja y no puede acceder.';
                } elseif (!password_verify($password, $usuario['password'])) {
                    $error = 'Usuario o contraseña incorrectos.';
                } else {
                    session_regenerate_id(true);
                    $_SESSION['usuario'] = [
                        'id_usuario' => $usuario['id_usuario'],
                        'username' => $usuario['username'],
                        'nombre_completo' => $usuario['nombre_completo'],
                        'rol' => $usuario['rol'],
                        'imagen' => $usuario['url_imagen']
                    ];
                    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

                    header('Location: index.php');
                    exit;
                }
            } catch (mysqli_sql_exception $e) {
                error_log('Error al iniciar sesión: ' . $e->getMessage());
                http_response_code(503);
                $error = 'No se puede conectar con la base de datos. Contacta con el administrador.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar sesión - Biblioteca Ohara</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
</head>

<body class="bg-light">
    <main class="container min-vh-100 d-flex align-items-center justify-content-center py-4">
        <div class="card border-0 shadow-lg w-100 overflow-hidden" style="max-width: 440px;">
            <div class="bg-dark text-white text-center px-4 py-4 border-bottom border-success border-3">
                <i class="bi bi-book-half text-success fs-1" aria-hidden="true"></i>
                <h1 class="h4 fw-bold mt-2 mb-1">Biblioteca Ohara</h1>
                <p class="text-white-50 mb-0 small">Panel de administración y gestión</p>
            </div>

            <div class="card-body bg-body p-4 p-sm-5">
                <h2 class="h5 fw-bold mb-2 text-dark">Iniciar sesión</h2>
                <p class="text-muted small mb-4">Introduce tus datos para acceder al sistema.</p>

                <?php if ($error !== ''): ?>
                    <div class="alert alert-danger d-flex align-items-start gap-2 py-2 small" role="alert">
                        <i class="bi bi-exclamation-circle-fill mt-1" aria-hidden="true"></i>
                        <span><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></span>
                    </div>
                <?php endif; ?>

                <form method="post" action="login.php">
                    <input type="hidden" name="csrf_token"
                        value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">

                    <div class="mb-3">
                        <label for="username" class="form-label fw-semibold small text-secondary">Usuario</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light border-end-0"><i class="bi bi-person text-muted"></i></span>
                            <input id="username" name="username" type="text" class="form-control border-start-0 ps-0 shadow-none"
                                value="<?= htmlspecialchars($username, ENT_QUOTES, 'UTF-8') ?>"
                                required maxlength="50" autocomplete="username" autofocus>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label for="password" class="form-label fw-semibold small text-secondary">Contraseña</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light border-end-0"><i class="bi bi-lock text-muted"></i></span>
                            <input id="password" name="password" type="password" class="form-control border-start-0 ps-0 shadow-none"
                                required autocomplete="current-password">
                        </div>
                    </div>

                    <button type="submit" class="btn btn-success w-100 py-2 fw-semibold shadow-sm mb-4">
                        <i class="bi bi-box-arrow-in-right me-2" aria-hidden="true"></i>Iniciar sesión
                    </button>
                </form>

                <!-- Caja de credenciales -->
                <div class="card bg-success bg-opacity-10 border-success border-opacity-25 rounded-3 p-3">
                    <div class="d-flex align-items-center mb-2">
                        <i class="bi bi-info-circle-fill text-success me-2 fs-6"></i>
                        <span class="small fw-bold text-success">Acceso rápido para corrección:</span>
                    </div>
                    <div class="small text-secondary bg-white p-2 rounded border border-success-subtle font-monospace">
                        <div class="mb-1 small"><strong>Admin:</strong> <span class="text-dark">admin</span> / <span class="text-dark">admin123</span></div>
                        <div class="small"><strong>Usuario:</strong> <span class="text-dark">usuario1</span> / <span class="text-dark">user123</span></div>
                        <div class="small"><strong>Usuario:</strong> <span class="text-dark">usuario_inactivo</span> / <span class="text-dark">user123</span></div>
                    </div>
                </div>

            </div>
        </div>
    </main>
</body>

</html>