<!DOCTYPE html>
<html lang="es">

<?php
// Cabecera global
include 'header.php';
?>

<!-- Contenido principal de la página -->
<div class="container my-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4>Modificar editorial</h4>
        <a href="mostrarEditoriales.php" class="btn btn-secondary">Volver al listado</a>
    </div>

    <?php
    $conexion = getMySQLiConnection();

    // 1. Recogemos el ID, ya sea que venga por GET o por POST
    if (isset($_GET['id_editorial'])) {
        $id_editorial = $_GET['id_editorial'];
    } else if (isset($_POST['id_editorial'])) {
        $id_editorial = $_POST['id_editorial'];
    } else {
        $id_editorial = null;
    }

    if (!$id_editorial) {
        echo "<div class='alert alert-danger'>Error: No se ha especificado qué editorial modificar.</div>";
        include 'footer.php';
        exit; // Matamos la ejecución posterior
    }

    // UPDATE
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $nombre = $_POST["txtNombre"];
        $contacto = $_POST["txtContacto"];
        $pais = $_POST["txtPais"];
        $fecha = $_POST["dateOrigen"];

        // El checkbox tiene que estar marcado
        if (isset($_POST["checkPais"])) {
            $editaEspanol =  1;
        } else {
            $editaEspanol =  0;
        }

        // Recogemos la imagen que ya tenía antes por si no sube una nueva
        $imgGuardada = $_POST["imgActual"];

        // Comprobamos si el usuario ha subido un nuevo archivo
        if (isset($_FILES["imgEditorial"]["name"]) && $_FILES["imgEditorial"]["name"] != "") {

            $sqlId = "SELECT id_editorial FROM editorial";

            // CASO 1: hay foto nueva (E + id + extensión)
            $imgFinal = "E" . $sqlId . ".jpg";

            // Hacemos el UPDATE incluyendo la columna 'imagen_editorial'
            $sql = "UPDATE editorial 
                    SET id_pais = ?, nombre_comercial = ?, contacto = ?, edita_espanol = ?, anio_origen = ?, imagen_editorial = ?
                    WHERE id_editorial = ?";

            move_uploaded_file($_FILES["imgEditorial"][$imgFinal], "img/editoriales" . $imgFinal); // La guardamos

        } else {
            // CASO 2: el campo estaba vacío y no hay foto nueva (UPDATE + borrar registro imagen)
            $sql = "UPDATE editorial 
                    SET id_pais = ?, nombre_comercial = ?, contacto = ?, edita_espanol = ?, anio_origen = ?
                    WHERE id_editorial = ?";
        }

        // Actualizamos
        $sql = "UPDATE editorial 
                SET id_pais = (SELECT id_pais FROM paises WHERE nombre_pais = ? LIMIT 1), nombre_comercial = ?, contacto = ?, imagen_editorial = ?, edita_espanol = ?, anio_origen = ? 
                WHERE id_editorial = ?";

        $sentencia = mysqli_prepare($conexion, $sql);

        if ($sentencia) {
            // Vinculamos variables (s = String / i = Integer)
            mysqli_stmt_bind_param($sentencia, "ssssisi", $pais, $nombre, $contacto, $imgFinal, $editaEspanol, $fecha, $id_editorial);

            mysqli_stmt_execute($sentencia);

            if (mysqli_stmt_errno($sentencia) == 0) {
                echo "<div class='alert alert-success'>Los datos de la editorial se han actualizado correctamente.</div>";
            } else {
                echo "<div class='alert alert-danger'>Error al actualizar: " . mysqli_stmt_error($sentencia) . "</div>";
            }
            mysqli_stmt_close($sentencia);
        }
    }

    // Hacemos un JOIN con la tabla países para obtener el nombre del país y mostrarlo en el input
    $sqlSelect = "SELECT e.*, p.nombre_pais 
                  FROM editorial e 
                  LEFT JOIN paises p ON e.id_pais = p.id_pais 
                  WHERE e.id_editorial = ?";

    $stmtSelect = mysqli_prepare($conexion, $sqlSelect);

    mysqli_stmt_bind_param($stmtSelect, "i", $id_editorial);
    mysqli_stmt_execute($stmtSelect);

    $resultado = mysqli_stmt_get_result($stmtSelect);
    $datosActuales = mysqli_fetch_assoc($resultado);

    mysqli_stmt_close($stmtSelect);

    // Si el ID no existe en la BBDD
    if (!$datosActuales) {
        echo "<div class='alert alert-warning'>No se encontró la editorial en la base de datos.</div>";
    } else {
    ?>

        <form name="frmModificarEditorial" id="frmModificarEditorial" action="modificarEditoriales.php" method="POST" enctype="multipart/form-data">

            <!-- Campos ocultos para enviar el ID y el nombre de la imagen actual -->
            <input type="hidden" name="id_editorial" value="<?php echo $datosActuales['id_editorial']; ?>">
            <input type="hidden" name="imgActual" value="<?php echo $datosActuales['imagen_editorial']; ?>">

            <legend>Datos de la editorial</legend>

            <div class="col-md-6 mb-3">
                <label for="txtNombre" class="form-label">Nombre</label>
                <!-- Usamos atributo value="" y htmlspecialchars() para evitar que se rompa el HTML si el texto tiene comillas -->
                <input type="text" class="form-control" id="txtNombre" name="txtNombre" maxlength="45" required
                    value="<?php echo htmlspecialchars($datosActuales['nombre_comercial']); ?>" />
            </div>

            <div class="col-md-6 mb-3">
                <label for="txtContacto" class="form-label">Contacto</label>
                <input type="text" class="form-control" id="txtContacto" name="txtContacto" maxlength="45"
                    value="<?php echo htmlspecialchars($datosActuales['contacto'] ?? ''); ?>" /> // Predeterminado es ''
            </div>

            <div class="col-md-6 mb-3">
                <label for="txtPais" class="form-label">País</label>
                <input type="text" class="form-control" id="txtPais" name="txtPais" maxlength="45"
                    value="<?php echo htmlspecialchars($datosActuales['nombre_pais'] ?? ''); ?>" />
            </div>

            <div class="col-md-6 mb-3">
                <label for="dateOrigen" class="form-label">Año de origen</label>
                <input type="date" class="form-control" id="dateOrigen" name="dateOrigen"
                    value="<?php echo htmlspecialchars($datosActuales['anio_origen']); ?>" />
            </div>

            <div class="col-md-6 mb-3">
                <div class="form-check">
                    <!-- Usamos código PHP dentro de la etiqueta para añadir 'checked' si el valor es 1 -->
                    <input class="form-check-input" type="checkbox" id="checkPais" name="checkPais" value="1"
                        <?php
                        if ($datosActuales['edita_espanol'] == 1) {
                            echo 'checked';
                        }
                        ?> />
                    <label class="form-check-label" for="checkPais">Edita en español</label>
                </div>
            </div>

            <div class="col-md-6 mb-3">
                <label for="imgEditorial" class="form-label">Imagen editorial (Dejar vacío para conservar la actual)</label>
                <input type="file" class="form-control mb-2" id="imgEditorial" name="imgEditorial" accept="image/*" />

                <?php
                if (!empty($datosActuales['imagen_editorial'])) { ?>
                    <small class="text-muted">Imagen actual: <?php echo htmlspecialchars($datosActuales['imagen_editorial']); ?></small>
                <?php 
                } 
                ?>
            </div>

            <button type="submit" class="btn btn-warning">Guardar Cambios</button>
        </form>

    <?php
    }
    ?>

</div>

<?php
// Pie de página global
include 'footer.php';
?>