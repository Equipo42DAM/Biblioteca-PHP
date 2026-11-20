<?php

$seccion_activa = 'socios';

include_once('config.php');

$conexion = getMySQLiConnection();

$mensaje = "";
$tipoMensaje = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    $dni = trim($_POST['dni_nif']);

    $sql = "SELECT id_socio, fecha_baja
            FROM socios_ricardo
            WHERE dni_nif = ?";

    $consulta = mysqli_prepare($conexion, $sql);

    mysqli_stmt_bind_param($consulta, "s", $dni);

    mysqli_stmt_execute($consulta);

    $resultado = mysqli_stmt_get_result($consulta);

    $socio = mysqli_fetch_assoc($resultado);

    mysqli_stmt_close($consulta);

    if (!$socio) {

        $mensaje = "No existe ningún socio con ese DNI/NIE.";
        $tipoMensaje = "alert-warning";

    } else if (!empty($socio['fecha_baja'])) {

        $mensaje = "Este socio ya está dado de baja.";
        $tipoMensaje = "alert-warning";

    } else {

        $sql = "UPDATE socios_ricardo
                SET fecha_baja = NOW()
                WHERE id_socio = ?";

        $consulta = mysqli_prepare($conexion, $sql);

        mysqli_stmt_bind_param($consulta, "i", $socio['id_socio']);

        mysqli_stmt_execute($consulta);

        if (mysqli_stmt_affected_rows($consulta) > 0) {

            $mensaje = "El socio ha sido dado de baja correctamente.";
            $tipoMensaje = "alert-success";

        } else {

            $mensaje = "No se ha podido dar de baja al socio.";
            $tipoMensaje = "alert-danger";

        }

        mysqli_stmt_close($consulta);
    }
}

include_once('header.php');

?>

<div class="container my-4">

    <h4 class="h3 mb-3">Dar de baja un socio</h4>

    <form action="bajaSocio.php" method="POST"
          onsubmit="return confirm('¿Estás seguro de que quieres dar de baja a este socio?');">

        <div class="mb-3">

            <label for="dni_nif" class="form-label">DNI / NIE</label>

            <input type="text" class="form-control"
                   id="dni_nif" name="dni_nif"
                   maxlength="20" placeholder="12345678Z" required>

        </div>

        <button type="submit" class="btn btn-danger">
            Dar de baja
        </button>

        <a href="socios.php" class="btn btn-secondary">
            Volver
        </a>

    </form>

    <?php if ($mensaje != ""): ?>

        <div class="alert <?php echo $tipoMensaje; ?> mt-3">
            <?php echo $mensaje; ?>
        </div>

    <?php endif; ?>

</div>

<?php

include_once('footer.php');

?>
