<?php

$seccion_activa = 'socios';

include_once('config.php');

// Esta página procesa el formulario antes de cargar el header, así que comprobamos aquí el inicio de sesión
require_once 'auth.php';
requireLogin();

$conexion = getMySQLiConnection();

// Busca un socio por su id o por su DNI. $columna solo se usa con 'id_socio' o 'dni_nif'
function buscarSocio($conexion, $columna, $valor) {

    $sql = "SELECT id_socio, dni_nif, nombre, apellidos, email, edad,
            es_vip, url_imagen, fecha_alta, fecha_baja
            FROM socios_ricardo
            WHERE $columna = ?";

    $consulta = mysqli_prepare($conexion, $sql);

    mysqli_stmt_bind_param($consulta, "s", $valor);

    mysqli_stmt_execute($consulta);

    $resultado = mysqli_stmt_get_result($consulta);

    $fila = mysqli_fetch_assoc($resultado);

    mysqli_stmt_close($consulta);

    return $fila;
}

$mensaje = "";
$tipoMensaje = "alert-danger";

$socio = null;      // el socio tal y como está en la base de datos
$buscado = false;   // se ha buscado un DNI
$form = null;       // los datos que se enseñan en el formulario de edición

// ==========================================
// 1. Guardar los cambios (se ha pulsado Guardar)
// ==========================================
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['id_socio'])) {

    $buscado = true;
    $socio = buscarSocio($conexion, 'id_socio', $_POST['id_socio']);

    if ($socio && empty($socio['fecha_baja'])) {

        // Datos nuevos del formulario
        $dni = strtoupper(trim($_POST['txtDni']));
        $nombre = trim($_POST['txtNombre']);
        $apellidos = trim($_POST['txtApellidos']);
        $email = trim($_POST['txtEmail']);
        $edad = trim($_POST['numEdad']);
        $fechaAlta = $_POST['dateAlta'];

        $esVip = 0;
        if (isset($_POST['chkVip'])) {
            $esVip = 1;
        }

        // Los dejamos preparados por si hay que volver a enseñar el formulario con un error
        $form = $socio;
        $form['dni_nif'] = $dni;
        $form['nombre'] = $nombre;
        $form['apellidos'] = $apellidos;
        $form['email'] = $email;
        $form['edad'] = $edad;
        $form['es_vip'] = $esVip;
        $form['fecha_alta'] = $fechaAlta;

        // La foto es opcional: si no se sube ninguna, se queda la que tenía
        $hayFoto = ($_FILES['imgSocio']['error'] != UPLOAD_ERR_NO_FILE);
        $extension = strtolower(pathinfo($_FILES['imgSocio']['name'], PATHINFO_EXTENSION));
        $extensionesPermitidas = array('jpg', 'jpeg', 'png', 'webp');

        $partesFecha = explode('-', $fechaAlta);

        // Comprobaciones (se queda con la primera que falle)
        if ($dni == "" || $nombre == "" || $apellidos == "" || $email == "" || $edad == "" || $fechaAlta == "") {
            $mensaje = "Rellena todos los campos.";
        } elseif (strlen($dni) != 9) {
            $mensaje = "El DNI/NIE debe tener 9 caracteres.";
        } elseif (!is_numeric($edad) || $edad < 0 || $edad > 120) {
            $mensaje = "La edad tiene que ser un número entre 0 y 120.";
        } elseif (count($partesFecha) != 3 || !checkdate((int)$partesFecha[1], (int)$partesFecha[2], (int)$partesFecha[0])) {
            $mensaje = "La fecha de alta no es válida.";
        } elseif ($hayFoto && $_FILES['imgSocio']['error'] != 0) {
            $mensaje = "No se ha podido subir la foto.";
        } elseif ($hayFoto && !in_array($extension, $extensionesPermitidas)) {
            $mensaje = "La foto tiene que ser jpg, png o webp.";
        } else {

            // Si hay foto nueva la guardamos; si no, se queda la de antes
            $rutaFoto = $socio['url_imagen'];
            $fotoNueva = false;

            if ($hayFoto) {
                $rutaFoto = "img/socios/" . time() . "." . $extension;
                move_uploaded_file($_FILES['imgSocio']['tmp_name'], __DIR__ . "/" . $rutaFoto);
                $fotoNueva = true;
            }

            $sql = "UPDATE socios_ricardo
                    SET dni_nif = ?, nombre = ?, apellidos = ?, email = ?,
                        edad = ?, es_vip = ?, url_imagen = ?, fecha_alta = ?
                    WHERE id_socio = ?";

            try {

                $consulta = mysqli_prepare($conexion, $sql);

                $edadNumero = (int)$edad;
                $idSocio = (int)$socio['id_socio'];

                mysqli_stmt_bind_param($consulta, "ssssiissi",
                    $dni, $nombre, $apellidos, $email, $edadNumero, $esVip, $rutaFoto, $fechaAlta, $idSocio
                );

                mysqli_stmt_execute($consulta);

                mysqli_stmt_close($consulta);

                // Si se ha cambiado la foto, borramos la antigua para no dejar ficheros sueltos
                if ($fotoNueva && $socio['url_imagen'] != "" && file_exists(__DIR__ . "/" . $socio['url_imagen'])) {
                    unlink(__DIR__ . "/" . $socio['url_imagen']);
                }

                // Volvemos a cargar la página con el socio ya modificado
                header("Location: modificarSocio.php?dni_nif=" . urlencode($dni) . "&ok=1");
                exit;

            } catch (mysqli_sql_exception $e) {

                // Si ha fallado, borramos la foto nueva que acabamos de subir
                if ($fotoNueva) {
                    unlink(__DIR__ . "/" . $rutaFoto);
                }

                if ($e->getCode() == 1062) {
                    $mensaje = "Ya existe otro socio con ese DNI/NIE.";
                } else {
                    $mensaje = "Error al modificar el socio.";
                }

            }
        }
    }

// ==========================================
// 2. Buscar un socio por su DNI
// ==========================================
} elseif (isset($_GET['dni_nif'])) {

    $buscado = true;
    $socio = buscarSocio($conexion, 'dni_nif', trim($_GET['dni_nif']));
    $form = $socio;

    if (isset($_GET['ok'])) {
        $mensaje = "Los datos del socio se han modificado correctamente.";
        $tipoMensaje = "alert-success";
    }
}

include_once('header.php');

?>

<main class="flex-grow-1 overflow-auto">
<div class="container my-4">

    <h4 class="h3 mb-3">Modificar socio</h4>

    <!-- Formulario para buscar el socio que se quiere modificar -->
    <form action="modificarSocio.php" method="GET" class="mb-4">

        <div class="mb-3">

            <label for="dni_nif" class="form-label">DNI / NIE del socio</label>

            <input type="text" class="form-control"
                   id="dni_nif" name="dni_nif"
                   maxlength="9" placeholder="12345678Z" required>

        </div>

        <button type="submit" class="btn btn-primary">
            Buscar
        </button>

        <a href="socios.php" class="btn btn-secondary">
            Volver
        </a>

    </form>

    <?php
    if ($mensaje != "") {
        echo "<div class='alert " . $tipoMensaje . "'>";
        echo htmlspecialchars($mensaje);
        echo "</div>";
    }
    ?>

    <?php if ($buscado): ?>

        <?php if (!$socio): ?>

            <div class="alert alert-warning">
                No se ha encontrado ningún socio con ese DNI/NIE.
            </div>

        <?php elseif (!empty($socio['fecha_baja'])): ?>

            <div class="alert alert-warning">
                Este socio está dado de baja y no se puede modificar.
            </div>

        <?php else: ?>

            <!-- Formulario de edición, con los datos actuales del socio -->
            <form name="frmModificarSocio" id="frmModificarSocio"
                  action="modificarSocio.php" method="POST" enctype="multipart/form-data" class="row g-3">

                <!-- Campo oculto: así sabemos qué socio estamos modificando -->
                <input type="hidden" name="id_socio" value="<?php echo (int)$socio['id_socio']; ?>">

                <div class="col-md-4">
                    <label for="txtDni" class="form-label">DNI / NIE</label>
                    <input type="text" class="form-control" id="txtDni"
                           name="txtDni" maxlength="9"
                           value="<?php echo htmlspecialchars($form['dni_nif']); ?>" required>
                </div>

                <div class="col-md-4">
                    <label for="txtNombre" class="form-label">Nombre</label>
                    <input type="text" class="form-control" id="txtNombre"
                           name="txtNombre" maxlength="50"
                           value="<?php echo htmlspecialchars($form['nombre']); ?>" required>
                </div>

                <div class="col-md-4">
                    <label for="txtApellidos" class="form-label">Apellidos</label>
                    <input type="text" class="form-control" id="txtApellidos"
                           name="txtApellidos" maxlength="100"
                           value="<?php echo htmlspecialchars($form['apellidos']); ?>" required>
                </div>

                <div class="col-md-6">
                    <label for="txtEmail" class="form-label">Email</label>
                    <input type="email" class="form-control" id="txtEmail"
                           name="txtEmail" maxlength="100"
                           value="<?php echo htmlspecialchars((string)$form['email']); ?>" required>
                </div>

                <div class="col-md-2">
                    <label for="numEdad" class="form-label">Edad</label>
                    <input type="number" class="form-control" id="numEdad"
                           name="numEdad" min="0" max="120"
                           value="<?php echo htmlspecialchars((string)$form['edad']); ?>" required>
                </div>

                <div class="col-md-4">
                    <label for="dateAlta" class="form-label">Fecha de alta</label>
                    <input type="date" class="form-control" id="dateAlta"
                           name="dateAlta"
                           value="<?php echo htmlspecialchars($form['fecha_alta']); ?>" required>
                </div>

                <div class="col-md-6">
                    <label for="imgSocio" class="form-label">Cambiar la foto (opcional)</label>
                    <input type="file" class="form-control" id="imgSocio"
                           name="imgSocio" accept="image/jpeg,image/png,image/webp">
                </div>

                <div class="col-md-3">
                    <label class="form-label d-block">Foto actual</label>
                    <?php
                    $foto = $socio['url_imagen'];
                    if ($foto != "" && file_exists(__DIR__ . "/" . $foto)) {
                        echo "<img src='" . htmlspecialchars($foto) . "' alt='Foto del socio' width='80' height='80' class='rounded-circle object-fit-cover'>";
                    } else {
                        echo "<i class='bi bi-person-circle fs-1 text-secondary'></i>";
                    }
                    ?>
                </div>

                <div class="col-md-3 d-flex align-items-end">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="chkVip"
                               name="chkVip" value="1" <?php if ($form['es_vip'] == 1) echo "checked"; ?>>
                        <label class="form-check-label" for="chkVip">Socio VIP</label>
                    </div>
                </div>

                <div class="col-12">
                    <button type="submit" class="btn btn-primary">
                        Guardar cambios
                    </button>
                    <a href="modificarSocio.php" class="btn btn-secondary">
                        Cancelar
                    </a>
                </div>

            </form>

        <?php endif; ?>

    <?php endif; ?>

</div>
</main>

<?php
include_once('footer.php');
?>