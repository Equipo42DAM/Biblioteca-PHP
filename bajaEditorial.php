<!DOCTYPE html>
<html lang="es">

<?php
// Cabecera global (Carga <head>, estilos CSS y Navbar)
include 'header.php';
?>

<!-- Contenido principal de la página -->
<div class="container my-4">
    <h4>Procesar la baja de editorial</h4>
    <a href="mostrarEditoriales.php" class="btn btn-secondary">Volver al listado</a>

    <form name="frmBajaEditorial" id="frmBajaEditorial" action="bajaEditorial.php" method="POST">
        <legend>Baja de editorial</legend>

        <div class="col-md-6 mb-3">
            <label for="id_editorial" class="form-label">Seleccione la editorial a eliminar</label>
            <select class="form-select" id="id_editorial" name="id_editorial" required>
                <option value="">-- Seleccione una editorial --</option>
                <?php

                $conexion = getMySQLiConnection();

                // Obtenemos las editoriales ordenadas alfabéticamente para mayor orden
                $sql = "SELECT id_editorial, nombre_comercial FROM editorial ORDER BY nombre_comercial ASC";
                $resultado = mysqli_query($conexion, $sql);

                if ($resultado) {
                    // Recorremos los resultados para crear las etiquetas <option>
                    while ($fila = mysqli_fetch_assoc($resultado)) {
                        echo "<option value='" . $fila['id_editorial'] . "'>" . $fila['nombre_comercial'] . "</option>";
                    }
                }
                ?>
            </select>
        </div>

        <!-- Casilla de confirmación obligatoria -->
        <div class="col-md-6 mb-3">
            <div class="form-check">
                <input class="form-check-input" type="checkbox" id="chkConfirmacion" name="chkConfirmacion" value="1" required />
                <label class="form-check-label text-danger" for="chkConfirmacion">Confirmo que deseo eliminar esta editorial de forma irreversible</label>
            </div>
        </div>

        <button type="submit" class="btn btn-danger">Eliminar Editorial</button>
    </form>

    <?php
    if ($_SERVER["REQUEST_METHOD"] == "POST") {

        $idEditorial = $_POST["id_editorial"];

        // Evitamos inyección SQL usando un prepared Statement, cambiando la variable por ?
        $sql = "DELETE FROM editorial WHERE id_editorial = ?";

        // Preparamos la consulta
        $sentencia = mysqli_prepare($conexion, $sql);

        if ($sentencia) {
            // i = Integer. Vinculamos la variable a la interrogación
            mysqli_stmt_bind_param($sentencia, "i", $idEditorial);

            mysqli_stmt_execute($sentencia);

            // Comprobación de errores en el preparedStatement
            if (mysqli_stmt_errno($sentencia) == 0) {
                // Comprobamos el número de filas afectadas
                if (mysqli_stmt_affected_rows($sentencia) > 0) {
                    echo "<div class='alert alert-success mt-4'>La editorial ha sido eliminada correctamente.</div>";
                } else {
                    echo "<div class='alert alert-warning mt-4'>No se encontró la editorial o ya había sido eliminada.</div>";
                }
            } else {
                $numerror = mysqli_stmt_errno($sentencia);
                $descrerror = mysqli_stmt_error($sentencia);

                // Error por restricción de FK
                if ($numerror == 1451) {
                    echo "<div class='alert alert-warning mt-4'>No se puede eliminar la editorial porque tiene libros u otros registros asociados en la base de datos.</div>";
                } else {
                    echo "<div class='alert alert-danger mt-4'>Se ha producido un error número $numerror: $descrerror</div>";
                }
            }

            // Cerramos el statement
            mysqli_stmt_close($sentencia);
        } else {
            echo "<div class='alert alert-danger mt-4'>Error preparando la consulta SQL.</div>";
        }
    }
    ?>

</div>

<?php
// Pie de página global (Carga scripts JS y cierra </body>/</html>)
include 'footer.php';
?>