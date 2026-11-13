<!DOCTYPE html>
<html lang="es">

<?php
// 1. Cabecera global (Carga <head>, estilos CSS y Navbar)
include 'header.php';
?>

<!-- 2. Contenido principal de la página -->
<div class="container my-4">
    <h4>Procesar el alta de editorial</h4>

    <!-- Formulario que envía todos los datos a esta misma página -->
    <form name="frmAltaEditorial" id="frmAltaEditorial" action="altaEditorial.php" method="POST" enctype="multipart/form-data">
        <legend>Alta de editorial</legend>
        <div class="col-md-6 mb-3">
            <label for="txtNombre" class="form-label">Nombre</label>
            <input type="text" class="form-control" id="txtNombre" name="txtNombre" placeholder="Introduzca el nombre de la editorial" maxlength="45" required />
        </div>

        <div class="col-md-6 mb-3">
            <label for="txtContacto" class="form-label">Contacto</label>
            <input type="text" class="form-control" id="txtContacto" name="txtContacto" placeholder="Introduzca el nombre del contacto" maxlength="45" />
        </div>

        <div class="col-md-6 mb-3">
            <label for="txtPais" class="form-label">País</label>
            <input type="text" class="form-control" id="txtPais" name="txtPais" placeholder="Introduzca el país de la editorial" maxlength="45" />
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
            <input type="file" class="form-control" id="imgEditorial" name="imgEditorial" />
        </div>

        <button type="submit" class="btn btn-primary">Aceptar</button>
    </form>

    <?php
    // Sólo valida mediante POST
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {

        // Si no se recoge el nombre por POST, tiene por defecto una cadena vacia 
        $nombre = $_POST["txtNombre"] ?: '';
        $contacto = $_POST["txtContacto"] ?: '';
        $pais = $_POST["txtPais"] ?: '';
        $fecha = $_POST["dateOrigen"] ?: '';
        $editaEspanol = isset($_POST["checkPais"]) ? 1 : 0;

        $nombreImagen = '';
        if (isset($_FILES['imgEditorial']) && $_FILES['imgEditorial']['error'] === UPLOAD_ERR_OK) {
            $nombreImagen = basename($_FILES['imgEditorial']['E' . $_POST['id_editorial']]);
            $rutaDestino = "img/" . $nombreImagen;

            move_uploaded_file($_FILES['imgEditorial']['tmp_name'], $rutaDestino);
        }

        $conexion = getMySQLiConnection();

        if ($conexion) {
            // Usamos ? para evitar inyección sql
            $sql = "INSERT INTO editorial (id_pais, nombre_comercial, contacto, imagen_editorial, edita_espanol, anio_origen) 
                    VALUES ((SELECT id_pais FROM paises WHERE nombre_pais = ?), ?, ?, ?, ?, ?)";

            if ($stmt = $conexion->prepare($sql)) {
                // Orden: pais (s), nombre (s), contacto (s), imagen (s), editaEspanol (i), fecha (s)
                $stmt->bind_param("ssssis", $pais, $nombre, $contacto, $nombreImagen, $editaEspanol, $fecha);

                $stmt->execute();

                if ($stmt->errno == 0) {
                    echo "<div class='alert alert-success mt-3'><p>Ha añadido una nueva editorial.</p>";
                    echo "<p><a href='altaEditorial.php' class='btn btn-primary'>Añadir otra editorial.</a></p></div>";
                } else {
                    $numerror = $stmt->errno;
                    $descrerror = $stmt->error;

                    if ($numerror == 1062) {
                        echo "<div class='alert alert-warning mt-3'><p>Ya existe la editorial <strong>" . htmlspecialchars($nombre) . "</strong> en la Base de Datos.</p>";
                        echo "<p><a href='altaEditorial.php' class='btn btn-primary'>Añadir otra editorial</a></p></div>";
                    } else {
                        echo "<div class='alert alert-danger mt-3'><p>Se ha producido un error número $numerror que corresponde a: $descrerror</p></div>";
                    }
                }
                $stmt->close();
            }
            mysqli_close($conexion);
        } else {
            echo "<div class='alert alert-danger mt-3'>Error al conectar con la base de datos.</div>";
        }
    }
    ?>

</div>

<?php
// 3. Pie de página global (Carga scripts JS y cierra </body>/</html>)
include 'footer.php';
?>