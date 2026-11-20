<?php
require_once 'config.php';

$mensaje = '';
$conexion = getMySQLiConnection();

// 1. Procesamos el POST antes de imprimir cualquier HTML
if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    $nombre = trim($_POST["txtNombre"] ?? '');
    $contacto = trim($_POST["txtContacto"] ?? '');
    $pais = trim($_POST["txtPais"] ?? '');
    $fecha = !empty($_POST["dateOrigen"]) ? $_POST["dateOrigen"] : null;
    $editaEspanol = isset($_POST["checkPais"]) ? 1 : 0;
    $nombreImagen = '';

    if ($conexion) {
        $sql = "INSERT INTO editorial_carlos (id_pais, nombre_comercial, contacto, url_imagen, edita_espanol, anio_origen)
                VALUES ((SELECT id_pais FROM paises WHERE nombre_pais = ? LIMIT 1), ?, ?, ?, ?, ?)";

        if ($sentencia = $conexion->prepare($sql)) {
            $sentencia->bind_param("ssssis", $pais, $nombre, $contacto, $nombreImagen, $editaEspanol, $fecha);

            try {
                if (!$sentencia->execute()) {
                    throw new Exception($sentencia->error);
                }
                $id = $conexion->insert_id;

                // Subida directa de la imagen (admite webp, jpg, png, gif, etc.)
                if (isset($_FILES['imgEditorial']) && $_FILES['imgEditorial']['error'] === UPLOAD_ERR_OK) {
                    $ext = strtolower(pathinfo($_FILES['imgEditorial']['name'], PATHINFO_EXTENSION));
                    $permitidas = ['jpg', 'jpeg', 'png', 'webp', 'gif'];

                    if (!in_array($ext, $permitidas)) {
                        throw new Exception("La editorial se creó, pero el formato de imagen no es válido (usa JPG, PNG, WEBP o GIF).");
                    }

                    if (!is_dir("img/editoriales")) {
                        mkdir("img/editoriales", 0777, true);
                    }

                    $ruta = "img/editoriales/E" . $id . "." . $ext;

                    if (move_uploaded_file($_FILES['imgEditorial']['tmp_name'], $ruta)) {
                        $upd = $conexion->prepare("UPDATE editorial_carlos SET url_imagen = ? WHERE id_editorial = ?");
                        $upd->bind_param("si", $ruta, $id);
                        $upd->execute();
                        $upd->close();
                    } else {
                        throw new Exception("La editorial se creó, pero no se pudo guardar la imagen. Revisa los permisos de la carpeta img/editoriales.");
                    }
                }

                $mensaje = "
                <div class='alert alert-success alert-dismissible fade show' role='alert'>
                    <strong>¡Editorial enviada!</strong> Se ha registrado correctamente.
                    <div class='mt-2'>
                        <a href='editoriales.php' class='btn btn-sm btn-success'>Ver listado de editoriales</a>
                        <a href='altaEditorial.php' class='btn btn-sm btn-outline-success'>Añadir otra</a>
                    </div>
                    <button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Cerrar'></button>
                </div>";
            } catch (Exception $e) {
                $mensaje = "<div class='alert alert-danger'>No se pudo añadir la editorial: " . htmlspecialchars($e->getMessage()) . "</div>";
            }

            $sentencia->close();
        }
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
            <select class="form-select" id="txtPais" name="txtPais">
                <option value="">Selecciona un país...</option>
                <?php
                if ($conexion) {
                    $sqlPaises = "SELECT nombre_pais FROM paises ORDER BY nombre_pais ASC";
                    $resPaises = mysqli_query($conexion, $sqlPaises);
                    if ($resPaises) {
                        while ($p = mysqli_fetch_assoc($resPaises)) {
                            echo "<option value='" . htmlspecialchars($p['nombre_pais']) . "'>" . htmlspecialchars($p['nombre_pais']) . "</option>";
                        }
                    }
                }
                ?>
            </select>
        </div>

        <div class="col-md-6 mb-3">
            <label for="dateOrigen" class="form-label">Año de origen</label>
            <input type="number" class="form-control" id="dateOrigen" name="dateOrigen" min="1400" max="2100" />
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
if (isset($conexion) && $conexion instanceof mysqli) {
    mysqli_close($conexion);
}
include 'footer.php';
?>