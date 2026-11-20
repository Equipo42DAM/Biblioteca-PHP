<?php
require_once 'config.php';

$conexion = getMySQLiConnection();
$error = '';
$id_editorial = (int)($_GET['id_editorial'] ?? $_POST['id_editorial'] ?? 0);

// 1. Procesamos el POST ANTES de imprimir cualquier HTML
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    try {
        if (!$id_editorial) {
            throw new Exception("Error: No se ha especificado qué editorial modificar.");
        }

        $nombre = trim($_POST["txtNombre"] ?? '');
        $contacto = trim($_POST["txtContacto"] ?? '');
        $pais = trim($_POST["txtPais"] ?? '');
        $anioOrigen = !empty($_POST["dateOrigen"]) ? $_POST["dateOrigen"] : null;
        $editaEspanol = isset($_POST["checkPais"]) ? 1 : 0;
        $rutaFinal = $_POST["imgActual"] ?? '';

        // Subida directa sin GD (admite webp, jpg, png, gif, etc.)
        if (isset($_FILES['imgEditorial']) && $_FILES['imgEditorial']['error'] === UPLOAD_ERR_OK) {
            $ext = strtolower(pathinfo($_FILES['imgEditorial']['name'], PATHINFO_EXTENSION));
            $permitidas = ['jpg', 'jpeg', 'png', 'webp', 'gif'];

            if (!in_array($ext, $permitidas)) {
                throw new Exception("Formato no permitido. Usa JPG, PNG, WEBP o GIF.");
            }

            if (!is_dir("img/editoriales")) {
                mkdir("img/editoriales", 0777, true);
            }

            $rutaNueva = "img/editoriales/E" . $id_editorial . "." . $ext;

            if (move_uploaded_file($_FILES['imgEditorial']['tmp_name'], $rutaNueva)) {
                if (!empty($rutaFinal) && $rutaFinal !== $rutaNueva && file_exists($rutaFinal)) {
                    @unlink($rutaFinal);
                }
                $rutaFinal = $rutaNueva;
            } else {
                throw new Exception("No se pudo guardar la imagen. Revisa los permisos de la carpeta img/editoriales.");
            }
        }

        $sql = "UPDATE editorial_carlos 
                SET id_pais = (SELECT id_pais FROM paises WHERE nombre_pais = ? LIMIT 1), 
                    nombre_comercial = ?, 
                    contacto = ?, 
                    url_imagen = ?, 
                    edita_espanol = ?, 
                    anio_origen = ? 
                WHERE id_editorial = ?";

        $sentencia = mysqli_prepare($conexion, $sql);

        if (!$sentencia) {
            throw new Exception("Error al preparar el UPDATE: " . mysqli_error($conexion));
        }

        mysqli_stmt_bind_param($sentencia, "ssssisi", $pais, $nombre, $contacto, $rutaFinal, $editaEspanol, $anioOrigen, $id_editorial);

        if (!mysqli_stmt_execute($sentencia)) {
            throw new Exception("Error al ejecutar el UPDATE: " . mysqli_stmt_error($sentencia));
        }

        mysqli_stmt_close($sentencia);
        mysqli_close($conexion);

        // Redirección limpia antes de enviar cualquier HTML
        header("Location: editoriales.php?msg=actualizado");
        exit;
    } catch (\Throwable $e) {
        $error = $e->getMessage();
    }
}

// 2. Cabecera global (a partir de aquí ya se imprime HTML)
include 'header.php';
?>

<div class="container my-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4>Modificar editorial</h4>
        <a href="editoriales.php" class="btn btn-secondary">Volver al listado</a>
    </div>

    <?php
    if (!empty($error)) {
        echo "<div class='alert alert-danger'><strong>Error:</strong> " . htmlspecialchars($error) . "</div>";
    }

    try {
        if (!$id_editorial) {
            throw new Exception("Error: No se ha especificado qué editorial modificar.");
        }

        // SELECT para rellenar el formulario
        $sqlSelect = "SELECT e.*, p.nombre_pais 
                      FROM editorial_carlos e 
                      LEFT JOIN paises p ON e.id_pais = p.id_pais 
                      WHERE e.id_editorial = ?";

        $stmtSelect = mysqli_prepare($conexion, $sqlSelect);

        if (!$stmtSelect) {
            throw new Exception("Error al preparar el SELECT: " . mysqli_error($conexion));
        }

        mysqli_stmt_bind_param($stmtSelect, "i", $id_editorial);
        mysqli_stmt_execute($stmtSelect);
        $resultado = mysqli_stmt_get_result($stmtSelect);
        $datosActuales = mysqli_fetch_assoc($resultado);
        mysqli_stmt_close($stmtSelect);

        if (!$datosActuales) {
            echo "<div class='alert alert-warning'>No se encontró la editorial en la base de datos.</div>";
        } else {
    ?>
            <form name="frmModificarEditorial" id="frmModificarEditorial" action="modificarEditorial.php" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="id_editorial" value="<?php echo $datosActuales['id_editorial']; ?>">
                <input type="hidden" name="imgActual" value="<?php echo htmlspecialchars($datosActuales['url_imagen'] ?? ''); ?>">

                <legend>Datos de la editorial</legend>

                <div class="col-md-6 mb-3">
                    <label for="txtNombre" class="form-label">Nombre</label>
                    <input type="text" class="form-control" id="txtNombre" name="txtNombre" maxlength="45" required
                        value="<?php echo htmlspecialchars($datosActuales['nombre_comercial'] ?? ''); ?>" />
                </div>

                <div class="col-md-6 mb-3">
                    <label for="txtContacto" class="form-label">Contacto</label>
                    <input type="text" class="form-control" id="txtContacto" name="txtContacto" maxlength="45"
                        value="<?php echo htmlspecialchars($datosActuales['contacto'] ?? ''); ?>" />
                </div>

                <div class="col-md-6 mb-3">
                    <label for="txtPais" class="form-label">País</label>
                    <select class="form-select" id="txtPais" name="txtPais">
                        <option value="">Selecciona un país...</option>
                        <?php
                        $sqlPaises = "SELECT nombre_pais FROM paises ORDER BY nombre_pais ASC";
                        $resPaises = mysqli_query($conexion, $sqlPaises);
                        if ($resPaises) {
                            while ($p = mysqli_fetch_assoc($resPaises)) {
                                $sel = (($datosActuales['nombre_pais'] ?? '') === $p['nombre_pais']) ? 'selected' : '';
                                echo "<option value='" . htmlspecialchars($p['nombre_pais']) . "' $sel>" . htmlspecialchars($p['nombre_pais']) . "</option>";
                            }
                        }
                        ?>
                    </select>
                </div>

                <div class="col-md-6 mb-3">
                    <label for="dateOrigen" class="form-label">Año de origen</label>
                    <input type="number" class="form-control" id="dateOrigen" name="dateOrigen" min="1400" max="2100"
                        value="<?php echo htmlspecialchars($datosActuales['anio_origen'] ?? ''); ?>" />
                </div>

                <div class="col-md-6 mb-3">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="checkPais" name="checkPais" value="1"
                            <?php if (($datosActuales['edita_espanol'] ?? 0) == 1) echo 'checked'; ?> />
                        <label class="form-check-label" for="checkPais">Edita en español</label>
                    </div>
                </div>

                <div class="col-md-6 mb-3">
                    <label for="imgEditorial" class="form-label">Imagen editorial (Dejar vacío para conservar actual)</label>
                    <input type="file" class="form-control mb-2" id="imgEditorial" name="imgEditorial" accept="image/*" />

                    <?php if (!empty($datosActuales['url_imagen'])) { ?>
                        <div class="mt-2">
                            <small class="text-muted d-block mb-1">Imagen actual: <?php echo htmlspecialchars($datosActuales['url_imagen']); ?></small>
                            <img src="<?php echo htmlspecialchars($datosActuales['url_imagen']); ?>" alt="Vista previa" class="img-thumbnail" style="width: 80px; height: 80px; object-fit: cover;">
                        </div>
                    <?php } ?>
                </div>

                <button type="submit" class="btn btn-warning">Guardar Cambios</button>
            </form>
    <?php
        }
    } catch (\Throwable $e) {
        echo "<div class='alert alert-danger'><strong>Error:</strong> " . htmlspecialchars($e->getMessage()) . "</div>";
    }

    if (isset($conexion) && $conexion instanceof mysqli) {
        mysqli_close($conexion);
    }
    ?>
</div>

<?php
include 'footer.php';
?>