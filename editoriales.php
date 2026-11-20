<?php
$seccion_activa = 'editoriales';
require_once 'config.php';
// Cabecera global (Carga <head>, estilos CSS, Navbar e Iconos)
include 'header.php';
?>

<!-- Contenido principal de la página -->
<div class="container my-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4>Listado de editoriales</h4>
        <a href="altaEditorial.php" class="btn btn-success">Nueva Editorial</a>
    </div>

    <!-- Inserción de la tabla -->
    <div class="flex-growth-1 overflow-auto p-3">
        <div class="border-bottom border-dark-subtle pb-2 mb-3">
            <h2>Listado de editoriales</h2>
        </div>
    </div>
    <div class="table-responsive border rounded" style="max-height: 80vh;">
        <table class="table table-striped border-dark-subtle align-middle small">
            <thead class="table-dark sticky-top">
                <tr>
                    <th>Imagen</th>
                    <th>Nombre</th>
                    <th>Contacto</th>
                    <th>Año Origen</th>
                    <th>¿Edita en Español?</th>
                    <th class="text-end">Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php
                // Conectamos a la base de datos
                $conexion = getMySQLiConnection();

                if ($conexion) {
                    // Consulta SQL apuntando a la misma tabla que altaEditorial.php
                    $sql = "SELECT id_editorial, url_imagen, nombre_comercial, contacto, anio_origen, edita_espanol 
                            FROM editorial_carlos 
                            WHERE fecha_baja IS NULL 
                            ORDER BY nombre_comercial ASC";

                    $resultado = mysqli_query($conexion, $sql);

                    // Comprobamos si la consulta devuelve filas
                    if ($resultado && mysqli_num_rows($resultado) > 0) {

                        // Recorremos fila a fila y generamos el HTML
                        while ($fila = mysqli_fetch_assoc($resultado)) {
                            $id = $fila['id_editorial'];
                            $nombre = htmlspecialchars($fila['nombre_comercial'] ?: '');

                            echo "<tr>";

                            // Columna de la imagen
                            echo "<td>";
                            if (!empty($fila['url_imagen'])) {
                                $ruta_imagen = htmlspecialchars($fila['url_imagen']);
                                echo "<img src='" . $ruta_imagen . "' alt='Logo " . $nombre . "' class='img-thumbnail' style='width: 50px; height: 50px; object-fit: cover;'>";
                            } else {
                                echo "<span class='text-muted small'>Sin imagen</span>";
                            }
                            echo "</td>";

                            echo "<td>" . $nombre . "</td>";
                            echo "<td>" . htmlspecialchars($fila['contacto'] ?: '') . "</td>";
                            echo "<td>" . htmlspecialchars($fila['anio_origen'] ?: '') . "</td>";
                            echo "<td>" . (($fila['edita_espanol'] == 1) ? "Sí" : "No") . "</td>";

                            echo "<td class='text-end'>";

                            // Botón de modificar
                            echo "<a href='modificarEditorial.php?id_editorial=" . $id . "' class='btn btn-warning btn-sm me-2' title='Modificar'>";
                            echo "<i class='bi bi-pencil-square'></i></a>";

                            // Botón del Modal de Bootstrap
                            echo "<button type='button' class='btn btn-danger btn-sm' data-bs-toggle='modal' data-bs-target='#modalBaja" . $id . "' title='Dar de baja'>";
                            echo "<i class='bi bi-trash'></i></button>";

                            echo "</td>";
                            echo "</tr>";

                            // Modal de confirmación
                            echo "
                                <div class='modal fade' id='modalBaja" . $id . "' data-bs-backdrop='static' data-bs-keyboard='false' tabindex='-1' aria-labelledby='modalLabel" . $id . "' aria-hidden='true'>
                                <div class='modal-dialog'>
                                    <div class='modal-content'>
                                    
                                    <div class='modal-header border-bottom-0'>
                                        <h1 class='modal-title fs-5' id='modalLabel" . $id . "'>Confirmar baja</h1>
                                        <button type='button' class='btn-close' data-bs-dismiss='modal' aria-label='No'></button>
                                    </div>
                                    
                                    <div class='modal-body text-start'>
                                        ¿Estás seguro de que deseas dar de baja la editorial <strong>" . $nombre . "</strong>?
                                    </div>
                                    
                                    <div class='modal-footer border-top-0'>
                                        <button type='button' class='btn btn-secondary' data-bs-dismiss='modal'>No</button>
                                        <a href='bajaEditorial.php?id_editorial=" . $id . "' class='btn btn-danger'>Sí</a>
                                    </div>
                                    
                                    </div>
                                </div>
                                </div>
                            ";
                        }
                    } else {
                        // Si la tabla está vacía mostramos un mensaje que ocupe todas las columnas
                        echo "<tr><td colspan='6' class='text-center'>No hay editoriales registradas en la base de datos.</td></tr>";
                    }

                    mysqli_close($conexion);
                } else {
                    echo "<tr><td colspan='6' class='text-center text-danger'>Error al conectar con la base de datos.</td></tr>";
                }
                ?>
            </tbody>
        </table>
    </div>

    <ul class="pagination justify-content-center mb-0 mt-3"
        style="--bs-pagination-active-bg: #198754; 
            --bs-pagination-active-border-color: #198754; 
            --bs-pagination-color: #198754; 
            --bs-pagination-hover-color: #146c43;">

        <!-- Botón Anterior -->
        <li class="page-item disabled">
            <a class="page-link" href="editoriales.php?pagina=0">Anterior</a>
        </li>

        <!-- Números de página -->
        <li class="page-item active">
            <a class="page-link" href="editoriales.php?pagina=1">1</a>
        </li>
        <li class="page-item ">
            <a class="page-link" href="editoriales.php?pagina=2">2</a>
        </li>

        <!-- Botón Siguiente -->
        <li class="page-item ">
            <a class="page-link" href="editoriales.php?pagina=2">Siguiente</a>
        </li>
    </ul>
</div>


</div>

<?php
// Pie de página global (Carga scripts JS y cierra </body>/</html>)
include 'footer.php';
?>