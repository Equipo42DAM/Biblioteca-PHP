<?php

$seccion_activa = 'socios';

include_once('config.php');

// Esta página procesa el formulario antes de cargar el header, así que comprobamos aquí el inicio de sesión
require_once 'auth.php';
requireLogin();

$conexion = getMySQLiConnection();

$mensaje = "";
$tipoMensaje = "alert-danger";

// Valores iniciales del formulario (así no se vacía si hay un error)
$dni = "";
$nombre = "";
$apellidos = "";
$email = "";
$edad = "";
$esVip = 0;
$fechaAlta = date('Y-m-d');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    // Recogemos los datos del formulario
    $dni = strtoupper(trim($_POST['txtDni']));
    $nombre = trim($_POST['txtNombre']);
    $apellidos = trim($_POST['txtApellidos']);
    $email = trim($_POST['txtEmail']);
    $edad = trim($_POST['numEdad']);
    $fechaAlta = $_POST['dateAlta'];

    // El checkbox solo llega en el POST si está marcado
    $esVip = 0;
    if (isset($_POST['chkVip'])) {
        $esVip = 1;
    }

    // Datos de la foto
    $extension = strtolower(pathinfo($_FILES['imgSocio']['name'], PATHINFO_EXTENSION));
    $extensionesPermitidas = array('jpg', 'jpeg', 'png', 'webp');

    // Fecha: la partimos en año, mes y día para comprobar que existe
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
    } elseif ($_FILES['imgSocio']['error'] != 0) {
        $mensaje = "Tienes que subir una foto del socio.";
    } elseif (!in_array($extension, $extensionesPermitidas)) {
        $mensaje = "La foto tiene que ser jpg, png o webp.";
    } else {

        // Guardamos la foto con un nombre que no se repita
        $rutaFoto = "img/socios/" . time() . "." . $extension;
        move_uploaded_file($_FILES['imgSocio']['tmp_name'], __DIR__ . "/" . $rutaFoto);

        // Insertamos los datos en la tabla
        $sql = "INSERT INTO socios_ricardo
                (dni_nif, nombre, apellidos, email, edad, es_vip, url_imagen, fecha_alta)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)";

        try {

            $consulta = mysqli_prepare($conexion, $sql);

            $edadNumero = (int)$edad;

            mysqli_stmt_bind_param($consulta, "ssssiiss",
                $dni, $nombre, $apellidos, $email, $edadNumero, $esVip, $rutaFoto, $fechaAlta
            );

            mysqli_stmt_execute($consulta);

            mysqli_stmt_close($consulta);

            // Volvemos a cargar la página
            header("Location: altaSocio.php?ok=1");
            exit;

        } catch (mysqli_sql_exception $e) {

            // Si ha fallado, borramos la foto que acabamos de subir
            unlink(__DIR__ . "/" . $rutaFoto);

            if ($e->getCode() == 1062) {
                $mensaje = "Ese DNI ya está registrado.";
            } else {
                $mensaje = "Error al añadir el socio.";
            }

        }
    }
}

// Comprobamos si el alta se ha realizado correctamente
if (isset($_GET['ok'])) {
    $mensaje = "Socio añadido correctamente.";
    $tipoMensaje = "alert-success";
}

include_once('header.php');

?>

<main class="flex-grow-1 overflow-auto">
<div class="container my-4">

    <h4 class="h3 mb-3">Alta de socio</h4>

    <?php
    if ($mensaje != "") {
        echo "<div class='alert " . $tipoMensaje . "'>";
        echo htmlspecialchars($mensaje);
        echo "</div>";
    }
    ?>

    <!-- enctype es necesario para poder subir la foto -->
    <form name="frmAltaSocio" id="frmAltaSocio"
          action="altaSocio.php" method="POST" enctype="multipart/form-data" class="row g-3">

        <div class="col-md-4">
            <label for="txtDni" class="form-label">DNI / NIE</label>
            <input type="text" class="form-control" id="txtDni"
                   name="txtDni" maxlength="9"
                   placeholder="12345678Z"
                   value="<?php echo htmlspecialchars($dni); ?>" required>
        </div>

        <div class="col-md-4">
            <label for="txtNombre" class="form-label">Nombre</label>
            <input type="text" class="form-control" id="txtNombre"
                   name="txtNombre" maxlength="50"
                   value="<?php echo htmlspecialchars($nombre); ?>" required>
        </div>

        <div class="col-md-4">
            <label for="txtApellidos" class="form-label">Apellidos</label>
            <input type="text" class="form-control" id="txtApellidos"
                   name="txtApellidos" maxlength="100"
                   value="<?php echo htmlspecialchars($apellidos); ?>" required>
        </div>

        <div class="col-md-6">
            <label for="txtEmail" class="form-label">Email</label>
            <input type="email" class="form-control" id="txtEmail"
                   name="txtEmail" maxlength="100"
                   value="<?php echo htmlspecialchars($email); ?>" required>
        </div>

        <div class="col-md-2">
            <label for="numEdad" class="form-label">Edad</label>
            <input type="number" class="form-control" id="numEdad"
                   name="numEdad" min="0" max="120"
                   value="<?php echo htmlspecialchars($edad); ?>" required>
        </div>

        <div class="col-md-4">
            <label for="dateAlta" class="form-label">Fecha de alta</label>
            <input type="date" class="form-control" id="dateAlta"
                   name="dateAlta"
                   value="<?php echo htmlspecialchars($fechaAlta); ?>" required>
        </div>

        <div class="col-md-6">
            <label for="imgSocio" class="form-label">Foto del socio (jpg, png o webp)</label>
            <input type="file" class="form-control" id="imgSocio"
                   name="imgSocio" accept="image/jpeg,image/png,image/webp" required>
        </div>

        <div class="col-md-6 d-flex align-items-end">
            <div class="form-check">
                <input class="form-check-input" type="checkbox" id="chkVip"
                       name="chkVip" value="1" <?php if ($esVip == 1) echo "checked"; ?>>
                <label class="form-check-label" for="chkVip">Socio VIP</label>
            </div>
        </div>

        <div class="col-12">
            <button type="submit" class="btn btn-primary">
                Dar de alta
            </button>
            <a href="socios.php" class="btn btn-secondary">
                Volver
            </a>
        </div>

    </form>

</div>
</main>

<?php
include_once('footer.php');
?>