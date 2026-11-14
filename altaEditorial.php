<!DOCTYPE html>
<html lang="es">

<?php
// Cabecera global (Carga <head>, estilos CSS y Navbar)
include 'header.php';
?>

<!-- Contenido principal de la página -->
<div class="container my-4">
    <h4>Procesar el alta de editorial</h4>
    <a href="mostrarEditoriales.php" class="btn btn-secondary">Volver al listado</a>

    <form name="frmAltaEditorial" id="frmAltaEditorial" action="altaEditorial.php" method="POST" enctype="multipart/form-data">
        <legend>Alta de editorial</legend>

        <div class="col-md-6 mb-3">
            <label for="txtNombre" class="form-label">Nombre</label>
            <input type="text" class="form-control" id="txtNombre" name="txtNombre" placeholder="Introduzca el nombre de la editorial" maxlength="45" size="50" required />
        </div>

        <div class="col-md-6 mb-3">
            <label for="txtContacto" class="form-label">Contacto</label>
            <input type="text" class="form-control" id="txtContacto" name="txtContacto" placeholder="Introduzca el nombre del contacto" maxlength="45" size="50" />
        </div>

        <div class="col-md-6 mb-3">
            <label for="txtPais" class="form-label">País</label>
            <input type="text" class="form-control" id="txtPais" name="txtPais" placeholder="Introduzca el país de la editorial" maxlength="45" size="50" />
        </div>

        <div class="col-md-6 mb-3">
            <label for="dateOrigen" class="form-label">Año de origen</label>
            <input type="date" class="form-control" id="dateOrigen" name="dateOrigen" />
        </div>

        <div class="col-md-6 mb-3">
            <div class="form-check">
                <input class="form-check-input" type="checkbox" id="checkPais" name="checkPais" value="1" />
                <label class="form-check-label" for="checkPais">Edita en español</label>
            </div>
        </div>

        <div class="col-md-6 mb-3">
            <label for="imgEditorial" class="form-label">Imagen editorial</label>
            <input type="file" class="form-control" id="imgEditorial" name="imgEditorial" accept="image/*" />
        </div>

        <button type="submit" class="btn btn-primary">Aceptar</button>
    </form>

    <?php
    if ($_SERVER["REQUEST_METHOD"] == "POST") {

        // Recogemos datos de texto
        $nombre = $_POST["txtNombre"];
        $contacto = $_POST["txtContacto"];
        $pais = $_POST["txtPais"];
        $fecha = $_POST["dateOrigen"];

        // Comprobación del check box
        if (isset($_POST["checkPais"])) {
            $editaEspanol = 1; // Edita en español
        } else {
            $editaEspanol = 0; // No edita en español
        }

        // Nomenclatura del nombre de la imagen
        $sqlId = "SELECT id_editorial FROM editorial";
        $nombreImg = "E" . $sqlId;

        if (isset($_FILES["imgEditorial"][$nombreImg])) {
            $img = $_FILES["imgEditorial"][$nombreImg];
            move_uploaded_file($_FILES["imgEditorial"][$nombreImg], "img/editoriales" . $img); // La guardamos
        }

        $conexion = getMySQLiConnection();

        // Evitamos inyección SQL usando un prepared Statement, cambiando variables por ?
        $sql = "INSERT INTO editorial (id_pais, nombre_comercial, contacto, imagen_editorial, edita_espanol, anio_origen) 
                    VALUES ((SELECT id_pais FROM paises WHERE nombre_pais = ? LIMIT 1), ?, ?, ?, ?, ?)";

        // Preparamos la consulta
        $sentencia = mysqli_prepare($conexion, $sql);

        if ($sentencia) {
            // s = String / i = Integer. Vinculamos variables a interrogaciones
            mysqli_stmt_bind_param($sentencia, "ssssis", $pais, $nombre, $contacto, $img, $editaEspanol, $fecha);

            mysqli_stmt_execute($sentencia);

            // Comprobación de errores en el preparedStatement
            if (mysqli_stmt_errno($sentencia) == 0) {
                echo "<div class='alert alert-success mt-4'>Ha añadido una nueva editorial correctamente.</div>";
            } else {
                $numerror = mysqli_stmt_errno($sentencia);
                $descrerror = mysqli_stmt_error($sentencia);

                if ($numerror == 1062) { // PK duplicada
                    echo "<div class='alert alert-warning mt-4'>Ya existe la editorial <strong>$nombre</strong> en la Base de Datos.</div>";
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
// 3. Pie de página global (Carga scripts JS y cierra </body>/</html>)
include 'footer.php';
?>