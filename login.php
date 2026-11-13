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
                $stmt = $conexion->prepare(
                    'SELECT id_usuario, username, password, nombre_completo, rol
                     FROM usuarios
                     WHERE username = ? AND fecha_baja IS NULL
                     LIMIT 1'
                );
                $stmt->bind_param('s', $username);
                $stmt->execute();
                $usuario = $stmt->get_result()->fetch_assoc();
                $stmt->close();

                if ($usuario && password_verify($password, $usuario['password'])) {
                    session_regenerate_id(true);
                    $_SESSION['usuario'] = [
                        'id_usuario' => $usuario['id_usuario'],
                        'username' => $usuario['username'],
                        'nombre_completo' => $usuario['nombre_completo'],
                        'rol' => $usuario['rol'],
                    ];
                    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

                    header('Location: index.php');
                    exit;
                }

                $error = 'Usuario o contraseña incorrectos.';
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
        <div class="card border-0 shadow w-100 overflow-hidden" style="max-width: 440px;">
            <div class="bg-dark text-white text-center px-4 py-4">
                <i class="bi bi-book-half text-success fs-1" aria-hidden="true"></i>
                <h1 class="h4 fw-bold mt-2 mb-1">Biblioteca Ohara</h1>
                <p class="text-white-50 mb-0">Panel de administración</p>
            </div>
            <div class="card-body p-4 p-sm-5">
                <h2 class="h4 mb-2">Iniciar sesión</h2>
                <p class="text-secondary mb-4">Introduce tus datos para acceder.</p>

                <?php if ($error !== ''): ?>
                    <div class="alert alert-danger d-flex align-items-start gap-2" role="alert">
                        <i class="bi bi-exclamation-circle-fill" aria-hidden="true"></i>
                        <span><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></span>
                    </div>
                <?php endif; ?>

                <form method="post" action="login.php">
                    <input type="hidden" name="csrf_token"
                           value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">
                    <div class="mb-3">
                        <label for="username" class="form-label fw-semibold">Usuario</label>
                        <input id="username" name="username" type="text" class="form-control"
                               value="<?= htmlspecialchars($username, ENT_QUOTES, 'UTF-8') ?>"
                               required maxlength="50" autocomplete="username" autofocus>
                    </div>
                    <div class="mb-4">
                        <label for="password" class="form-label fw-semibold">Contraseña</label>
                        <input id="password" name="password" type="password" class="form-control"
                               required autocomplete="current-password">
                    </div>
                    <button type="submit" class="btn btn-success w-100 py-2">
                        <i class="bi bi-box-arrow-in-right me-2" aria-hidden="true"></i>Iniciar sesión
                    </button>
                </form>
            </div>
        </div>
    </main>
</body>
</html>