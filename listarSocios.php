<?php

$seccion_activa = 'socios';

include_once('config.php');
include_once('header.php');

$conexion = getMySQLiConnection();

$sql = "SELECT id_socio, dni_nif, nombre, apellidos,
        email, edad, es_vip, url_imagen, fecha_alta, fecha_baja
        FROM socios_ricardo
        ORDER BY apellidos, nombre";

$resultado = mysqli_query($conexion, $sql);

$total = mysqli_num_rows($resultado);

?>

<main class="flex-grow-1 overflow-auto">

    <div class="container my-4">

        <div class="d-flex justify-content-between align-items-center mb-3">

            <h1 class="h3 mb-0">Listado de socios</h1>

            <a href="altaSocio.php" class="btn btn-success">
                <i class="bi bi-person-plus me-1"></i>
                Añadir socio
            </a>

        </div>

        <p class="text-muted">
            Hay <?php echo $total; ?> socios registrados.
        </p>

        <div class="table-responsive">

            <table class="table table-striped table-hover align-middle">

                <thead class="table-dark">

                    <tr>
                        <th>Foto</th>
                        <th>DNI / NIE</th>
                        <th>Nombre y apellidos</th>
                        <th>Email</th>
                        <th>Edad</th>
                        <th>VIP</th>
                        <th>Fecha de alta</th>
                        <th>Estado</th>
                    </tr>

                </thead>

                <tbody>

                    <?php

                    while ($fila = mysqli_fetch_assoc($resultado)) {

                        echo "<tr>";

                        // Foto del socio
                        $foto = $fila['url_imagen'];

                        echo "<td>";

                        if ($foto != "" && file_exists(__DIR__ . "/" . $foto)) {

                            echo "<img src='" . htmlspecialchars($foto) . "' width='50' height='50' class='rounded-circle object-fit-cover'>";

                        } else {

                            echo "<i class='bi bi-person-circle fs-2 text-secondary'></i>";

                        }

                        echo "</td>";

                        echo "<td>" . htmlspecialchars($fila['dni_nif']) . "</td>";

                        echo "<td>" . htmlspecialchars($fila['nombre'] . " " . $fila['apellidos']) . "</td>";

                        echo "<td>" . htmlspecialchars($fila['email']) . "</td>";

                        echo "<td>" . htmlspecialchars($fila['edad']) . "</td>";

                        // Comprobar si es VIP
                        if ($fila['es_vip'] == 1) {

                            echo "<td><span class='badge bg-warning text-dark'>VIP</span></td>";

                        } else {

                            echo "<td>No</td>";

                        }

                        // Fecha de alta
                        echo "<td>" . date('d/m/Y', strtotime($fila['fecha_alta'])) . "</td>";

                        // Comprobar si está activo o de baja
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

