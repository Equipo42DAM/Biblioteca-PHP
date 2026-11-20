<?php

$seccion_activa = 'socios';

include_once('config.php');

$conexion = getMySQLiConnection();

$mensaje = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    // Recogemos los datos del formulario
    $dni = $_POST['txtDni'];
    $nombre = $_POST['txtNombre'];
    $apellidos = $_POST['txtApellidos'];
    $email = $_POST['txtEmail'];
    $edad = $_POST['numEdad'];
    $fechaAlta = $_POST['dateAlta'];

    // Insertamos los datos en la tabla
    $sql = "INSERT INTO socios_ricardo
            (dni_nif, nombre, apellidos, email, edad, fecha_alta)
            VALUES (?, ?, ?, ?, ?, ?)";

    try {

        $consulta = mysqli_prepare($conexion, $sql);

        mysqli_stmt_bind_param($consulta, "ssssis",
            $dni, $nombre, $apellidos, $email, $edad, $fechaAlta
        );

        mysqli_stmt_execute($consulta);

        mysqli_stmt_close($consulta);

        // Volvemos a cargar la página
        header("Location: altaSocio.php?ok=1");
        exit;

    } catch (mysqli_sql_exception $e) {

        if ($e->getCode() == 1062) {
            $mensaje = "Ese DNI ya está registrado.";
        } else {
            $mensaje = "Error al añadir el socio.";
        }

    }
}

// Comprobamos si el alta se ha realizado correctamente
if (isset($_GET['ok'])) {
    $mensaje = "Socio añadido correctamente.";
}

include_once('header.php');

?>

<div class="container my-4">

    <h4 class="h3 mb-3">Alta de socio</h4>

    <?php
    if ($mensaje != "") {
        echo "<div class='alert alert-info'>";
        echo htmlspecialchars($mensaje);
        echo "</div>";
    }
    ?>

    <form name="frmAltaSocio" id="frmAltaSocio"
          action="" method="POST" class="row g-3">

        <div class="col-12">
            <label for="txtDni" class="form-label">DNI / NIE</label>
            <input type="text" class="form-control" id="txtDni"
                   name="txtDni" maxlength="9"
                   placeholder="12345678Z" required>
        </div>

        <div class="col-12">
            <label for="txtNombre" class="form-label">Nombre</label>
            <input type="text" class="form-control" id="txtNombre"
                   name="txtNombre" maxlength="50" required>
        </div>

        <div class="col-12">
            <label for="txtApellidos" class="form-label">Apellidos</label>
            <input type="text" class="form-control" id="txtApellidos"
                   name="txtApellidos" maxlength="100" required>
        </div>

        <div class="col-12">
            <label for="txtEmail" class="form-label">Email</label>
            <input type="email" class="form-control" id="txtEmail"
                   name="txtEmail" maxlength="100" required>
        </div>

        <div class="col-12">
            <label for="numEdad" class="form-label">Edad</label>
            <input type="number" class="form-control" id="numEdad"
                   name="numEdad" min="0" max="120" required>
        </div>

        <div class="col-12">
            <label for="dateAlta" class="form-label">Fecha de alta</label>
            <input type="date" class="form-control" id="dateAlta"
                   name="dateAlta"
                   value="<?php echo date('Y-m-d'); ?>" required>
        </div>

        <div class="col-12">
            <button type="submit" class="btn btn-primary">
                Dar de alta
            </button>
        </div>

    </form>

</div>

<?php
include_once('footer.php');
?>
