<?php
    $seccion_activa = 'socios';
    include_once('config.php');
    include_once('header.php');

    $conexion = getMySQLiConnection();

    // Sacamos todos los socios (activos y de baja) junto con el nombre de su categoría
    $sql = "SELECT s.id_socio, s.dni_nif, s.nombre, s.apellidos, s.email, s.edad, s.es_vip,
                   s.url_imagen, s.fecha_alta, s.fecha_baja, c.nombre_categoria
            FROM socios_ricardo s
            INNER JOIN categorias_socio c ON s.id_categoria = c.id_categoria
            ORDER BY s.apellidos, s.nombre";

    $resultado = mysqli_query($conexion, $sql);
    $total = mysqli_num_rows($resultado);
?>
<main class="flex-grow-1 overflow-auto">
<div class="container my-4">

    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h3 mb-0">Listado de socios</h1>
        <a href="altaSocio.php" class="btn btn-success">
            <i class="bi bi-person-plus me-1"></i>Añadir socio
        </a>
    </div>

    <p class="text-muted">Hay <?php echo $total; ?> socios registrados.</p>

    <div class="table-responsive">
        <table class="table table-striped table-hover align-middle">
            <thead class="table-dark">
                <tr>
                    <th>Foto</th>
                    <th>DNI / NIE</th>
                    <th>Nombre y apellidos</th>
                    <th>Email</th>
                    <th>Edad</th>
                    <th>Categoría</th>
                    <th>VIP</th>
                    <th>Fecha de alta</th>
                    <th>Estado</th>
                </tr>
            </thead>
            <tbody>
                <?php
                    while ($fila = mysqli_fetch_assoc($resultado)) {
                        echo "<tr>";

                        // Foto: si el fichero existe lo enseñamos, y si no ponemos un icono
                        $foto = $fila['url_imagen'];
                        echo "<td>";
                        if ($foto != "" && file_exists(__DIR__ . "/" . $foto)) {
                            echo "<img src='" . htmlspecialchars($foto) . "' alt='Foto del socio' width='50' height='50' class='rounded-circle object-fit-cover'>";
                        } else {
                            echo "<i class='bi bi-person-circle fs-2 text-secondary'></i>";
                        }
                        echo "</td>";

                        echo "<td>" . htmlspecialchars($fila['dni_nif']) . "</td>";
                        echo "<td>" . htmlspecialchars($fila['nombre'] . " " . $fila['apellidos']) . "</td>";
                        echo "<td>" . htmlspecialchars($fila['email']) . "</td>";
                        echo "<td>" . $fila['edad'] . "</td>";
                        echo "<td>" . htmlspecialchars($fila['nombre_categoria']) . "</td>";

                        // VIP: en la base de datos es 1 o 0
                        if ($fila['es_vip'] == 1) {
                            echo "<td><span class='badge bg-warning text-dark'>VIP</span></td>";
                        } else {
                            echo "<td>No</td>";
                        }

                        // La fecha viene como 2026-01-10, la damos la vuelta a 10/01/2026
                        echo "<td>" . date('d/m/Y', strtotime($fila['fecha_alta'])) . "</td>";

                        // Si fecha_baja está vacía, el socio sigue activo
                        if ($fila['fecha_baja'] == null) {
                            echo "<td><span class='badge bg-success'>Activo</span></td>";
                        } else {
                            echo "<td><span class='badge bg-secondary'>Baja</span></td>";
                        }

                        echo "</tr>";
                    }
                ?>
            </tbody>
        </table>
    </div>

</div>
</main>
<?php
    include_once('footer.php');
?>