<?php
require_once 'config.php';

$mensaje = ''; // Variable para guardar el aviso y mostrarlo arriba

// 1. Procesamos el POST antes de imprimir el HTML
if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    $nombre = trim($_POST["txtNombre"] ?? '');
    $contacto = trim($_POST["txtContacto"] ?? '');
    $pais = trim($_POST["txtPais"] ?? '');
    $fecha = !empty($_POST["dateOrigen"]) ? $_POST["dateOrigen"] : null;
    $editaEspanol = isset($_POST["checkPais"]) ? 1 : 0;
    $nombreImagen = '';

    $conexion = getMySQLiConnection();

    if ($conexion) {
        $sql = "INSERT INTO editorial_carlos (id_pais, nombre_comercial, contacto, url_imagen, edita_espanol, anio_origen)
                VALUES ((SELECT id_pais FROM paises WHERE nombre_pais = ? LIMIT 1), ?, ?, ?, ?, ?)";

        // Realizamos consultas preparadas para evitar inyección de código
        if ($sentencia = $conexion->prepare($sql)) {
            $sentencia->bind_param("ssssis", $pais, $nombre, $contacto, $nombreImagen, $editaEspanol, $fecha);

            try {
                $sentencia->execute();
                $id = $conexion->insert_id;

                // Validación y subida de imagen conservando su extensión original
                if (isset($_FILES['imgEditorial']) && $_FILES['imgEditorial']['error'] === UPLOAD_ERR_OK) {
                    $extension = pathinfo($_FILES['imgEditorial']['name'], PATHINFO_EXTENSION);
                    $ruta = "img/editoriales/E" . $id . ($extension ? "." . strtolower($extension) : "");

                    if (move_uploaded_file($_FILES['imgEditorial']['tmp_name'], $ruta)) {
                        $upd = $conexion->prepare("UPDATE editorial_carlos SET url_imagen = ? WHERE id_editorial = ?");
                        $upd->bind_param("si", $ruta, $id);
                        $upd->execute();
                        $upd->close();
                    }
                }

                // Guardamos el mensaje de éxito (puedes añadir un botón directo a listarEditoriales.php)
                $mensaje = "
                <div class='alert alert-success alert-dismissible fade show' role='alert'>
                    <strong>¡Editorial enviada!</strong> Se ha registrado correctamente.
                    <div class='mt-2'>
                        <a href='listarEditoriales.php' class='btn btn-sm btn-success'>Ver listado de editoriales</a>
                        <a href='altaEditorial.php' class='btn btn-sm btn-outline-success'>Añadir otra</a>
                    </div>
                    <button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Cerrar'></button>
                </div>";

            } catch (mysqli_sql_exception $e) {
                $mensaje = "<div class='alert alert-danger'>No se pudo añadir la editorial: " . htmlspecialchars($e->getMessage()) . "</div>";
            }

            $sentencia->close();
        }
        mysqli_close($conexion);
    } else {
        $mensaje = "<div class='alert alert-danger'>Error al conectar con la base de datos.</div>";
    }
}

// 2. Cabecera global
include 'header.php';
?>

<!-- 3. Contenido principal de la página -->
<div class="container my-4">
    <h4>Procesar el alta de editorial</h4>

    <!-- AQUÍ SE MUESTRA EL MENSAJE DE AVISO (justo encima del formulario) -->
    <?php echo $mensaje; ?>

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
            <input type="number" class="form-control" id="dateOrigen" name="dateOrigen" />
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
        <a href="editoriales.php" class="btn btn-secondary">Volver</a>
    </form>
</div>

<?php
include 'footer.php';
?>