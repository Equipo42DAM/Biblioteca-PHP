<?php
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
        <div class="table-responsive border rounded" style="max-height: 80vh;">
            <table class="table table-striped table-hover align-middle">
                <thead class="table-dark sticky-top">
                    <tr>
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
                        // Consulta SQL apuntando a la misma tabla que altaEditorial.php (editorial_carlos)
                        $sql = "SELECT id_editorial, nombre_comercial, contacto, anio_origen, edita_espanol 
                    FROM editorial_carlos 
                    ORDER BY nombre_comercial ASC";

                        $resultado = mysqli_query($conexion, $sql);

                        // Comprobamos si la consulta devuelve filas
                        if ($resultado && mysqli_num_rows($resultado) > 0) {

                            // Recorremos fila a fila y generamos el HTML
                            while ($fila = mysqli_fetch_assoc($resultado)) {
                                echo "<tr>";

                                // ?? '' evita avisos en PHP 8+ si contacto o anio_origen son NULL en la BD
                                echo "<td>" . htmlspecialchars($fila['nombre_comercial'] ?? '') . "</td>";
                                echo "<td>" . htmlspecialchars($fila['contacto'] ?? '') . "</td>";
                                echo "<td>" . htmlspecialchars($fila['anio_origen'] ?? '') . "</td>";

                                $edita = ($fila['edita_espanol'] == 1) ? "Sí" : "No";
                                echo "<td>" . $edita . "</td>";

                                echo "<td class='text-end'>";

                                echo "<a href='modificarEditoriales.php?id_editorial=" . $fila['id_editorial'] . "' class='btn btn-warning btn-sm me-2' title='Modificar'>";
                                echo "<i class='bi bi-pencil-square'></i></a>";

                                echo "<a href='bajaEditorial.php?id_editorial=" . $fila['id_editorial'] . "' class='btn btn-danger btn-sm' title='Eliminar'>";
                                echo "<i class='bi bi-trash'></i></a>";

                                echo "</td>";
                                echo "</tr>";
                            }
                        } else {
                            // Si la tabla está vacía mostramos un mensaje que ocupe todas las columnas
                            echo "<tr><td colspan='5' class='text-center'>No hay editoriales registradas en la base de datos.</td></tr>";
                        }

                        mysqli_close($conexion);
                    } else {
                        echo "<tr><td colspan='5' class='text-center text-danger'>Error al conectar con la base de datos.</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>


</div>

<?php
// Pie de página global (Carga scripts JS y cierra </body>/</html>)
include 'footer.php';
?>