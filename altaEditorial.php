<!DOCTYPE html>
<html lang="es">

<?php
// 1. Cabecera global (Carga <head>, estilos CSS y Navbar)
include 'header.php';
?>

<!-- 2. Contenido principal de la página -->
<div class="container my-4">
    <h4>Procesar el alta de editorial</h4>

    // Formulario que envía todos los datos a esta misma página, donde se procesarán
    <form name="frmAltaEditorial" id="frmAltaEditorial" action="altaEditorial.php" method="POST">
        <legend>Alta de editorial</legend>
        <div class="col-md-6 mb-3">
            <label for="txtNombre" class="form-label">Nombre</label>
            <input type="text" class="form-control" id="txtNombre" name="txtNombre" placeholder="Introduzca el nombre de la editorial" maxlength="45" size="50" />
        </div>

        <div class="col-md-6 mb-3">
            <label for="txtContacto" class="form-label">Contacto</label>
            <input type="text" class="form-control" id="txtContacto" name="txtContacto" placeholder="Introduzca el nombre del contacto" maxlength="45" size="50" />
        </div>

        <div class="col-md-6 mb-3">
            <label for="txtPais" class="form-label">Pais</label>
            <input type="text" class="form-control" id="txtPais" name="txtPais" placeholder="Introduzca el país de la editorial" maxlength="45" size="50" />
        </div>

        <div class="col-md-6 mb-3">
            <label for="dateOrigen" class="form-label">Año de origen</label>
            <input type="date" class="form-control" id="dateOrigen" name="dateOrigen" placeholder="Introduzca el año de origen" maxlength="45" size="50" />
        </div>

        <div class="col-md-6 mb-3">
            <div class="form-check">
                <input class="form-check-input" type="checkbox" id="checkPais" name="checkPais" value="1" />
                <label class="form-check-label" for="checkPais">Edita en español</label>
            </div>
        </div>

        <div class="col-md-6 mb-3">
            <label for="imgEditorial" class="form-label">Imagen editorial</label>
            <input type="file" class="form-control" id="imgEditorial" name="imgEditorial" placeholder="Introduzca una foto del logo de la editorial" />
        </div>

        <button type="submit" class="btn btn-primary">Aceptar</button>
    </form>

    <?php
        $nombre = $_POST["txtNombre"];
        $contacto = $_POST["txtContacto"];
        $pais = $_POST["txtPais"];
        $fecha = $_POST["dateOrigen"];
        $editaEspanol = $_POST["checkPais"];
        $img = $_POST["imgEditorial"];

        $conexion = getMySQLiConnection();

        $sql = "INSERT INTO editorial (id_pais, nombre_comercial, contacto, imagen_editorial, edita_espanol, anio_origen) 
                VALUES ((SELECT id_pais FROM paises WHERE nombre_pais = $pais), '$nombre', '$contacto', '$img', '$editaEspanol', '$fecha');";

        mysqli_query($conexion, $sql);

        if (mysqli_errno($conexion) == 0) {
            echo "<p>Ha añadido una nueva editorial.</p>";
            echo "<p><a href='altaEditorial.php' class='btn btn-primary'>Añadir otra editorial.</a></p>";
        } else {
            $numerror = mysqli_errno($conexion);
            $descrerror = mysqli_error($conexion);

            if ($numerror == 1062) {
                echo "<p>Ya existe la editorial $nombre en la Base de Datos.</p>";
                echo "<p><a href='altaEditorial.php' class='btn btn-primary'>Añadir otra editorial</a></p>";
            } else {
                echo "<p>Se ha producido un error numero $numerror que corresponde a: $descrerror</p>";
            }
        }
    ?>

</div>

<?php
// 3. Pie de página global (Carga scripts JS y cierra </body>/</html>)
include 'footer.php';
?>