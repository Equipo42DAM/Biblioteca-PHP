<?php

$seccion_activa = 'socios';

include_once('config.php');
include_once('header.php');

$conexion = getMySQLiConnection();

$busqueda = "";
$estado = "todos";
$vip = "todos";

if (isset($_GET['busqueda'])) {
    $busqueda = trim($_GET['busqueda']);
}

if (isset($_GET['estado'])) {
    $estado = $_GET['estado'];
}

if (isset($_GET['vip'])) {
    $vip = $_GET['vip'];
}

// Consulta para buscar los socios según los filtros
$sql = "SELECT id_socio, dni_nif, nombre, apellidos,
        email, edad, es_vip, fecha_alta, fecha_baja
        FROM socios_ricardo
        WHERE (dni_nif LIKE ? OR nombre LIKE ? OR apellidos LIKE ?)
        AND (? = 'todos' OR
             (? = 'activo' AND fecha_baja IS NULL) OR
             (? = 'baja' AND fecha_baja IS NOT NULL))
        AND (? = 'todos' OR es_vip = ?)
        ORDER BY apellidos, nombre";

$consulta = mysqli_prepare($conexion, $sql);

$texto = "%" . $busqueda . "%";

mysqli_stmt_bind_param(
    $consulta,
    "ssssssss",
    $texto,
    $texto,
    $texto,
    $estado,
    $estado,
    $estado,
    $vip,
    $vip
);

mysqli_stmt_execute($consulta);

$resultado = mysqli_stmt_get_result($consulta);

$total = mysqli_num_rows($resultado);

?>

<main class="flex-grow-1 overflow-auto">

    <div class="container my-4">

        <h1 class="h3 mb-3">Listado de socios por filtro</h1>

        <!-- Formulario de filtros -->
        <form action="listarFiltroSocio.php" method="GET" class="mb-4">

            <div class="row g-3">

                <div class="col-md-4">

                    <label for="busqueda" class="form-label">
                        DNI, nombre o apellidos
                    </label>

                    <input type="text" class="form-control"
                           id="busqueda" name="busqueda"
                           placeholder="Buscar socio..."
                           value="<?php echo htmlspecialchars($busqueda); ?>">

                </div>

                <div class="col-md-4">

                    <label for="estado" class="form-label">
                        Estado del socio
                    </label>

                    <select class="form-select" id="estado" name="estado">

                        <option value="todos" <?php if ($estado == 'todos') echo 'selected'; ?>>
                            Todos
                        </option>

                        <option value="activo" <?php if ($estado == 'activo') echo 'selected'; ?>>
                            Activos
                        </option>

                        <option value="baja" <?php if ($estado == 'baja') echo 'selected'; ?>>
                            Dados de baja
                        </option>

                    </select>

                </div>

                <div class="col-md-4">

                    <label for="vip" class="form-label">
                        Socio VIP
                    </label>

                    <select class="form-select" id="vip" name="vip">

                        <option value="todos" <?php if ($vip == 'todos') echo 'selected'; ?>>
                            Todos
                        </option>

                        <option value="1" <?php if ($vip == '1') echo 'selected'; ?>>
                            VIP
                        </option>

                        <option value="0" <?php if ($vip == '0') echo 'selected'; ?>>
                            No VIP
                        </option>

                    </select>

                </div>

            </div>

            <div class="mt-3">

                <button type="submit" class="btn btn-primary">
                    Filtrar
                </button>

                <a href="listarFiltroSocio.php" class="btn btn-secondary">
                    Limpiar filtros
                </a>

                <a href="socios.php" class="btn btn-secondary">
                    Volver
                </a>

            </div>

        </form>

        <p class="text-muted">
            Se han encontrado <?php echo $total; ?> socios.
        </p>

        <!-- Tabla de resultados -->
        <div class="table-responsive">

            <table class="table table-striped table-hover align-middle">

                <thead class="table-dark">

                    <tr>
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

                    if ($total > 0) {

                        while ($fila = mysqli_fetch_assoc($resultado)) {

                            echo "<tr>";

                            echo "<td>" . htmlspecialchars($fila['dni_nif']) . "</td>";

                            echo "<td>" . htmlspecialchars($fila['nombre'] . " " . $fila['apellidos']) . "</td>";

                            echo "<td>" . htmlspecialchars($fila['email']) . "</td>";

                            echo "<td>" . htmlspecialchars($fila['edad']) . "</td>";

                            if ($fila['es_vip'] == 1) {

                                echo "<td><span class='badge bg-warning text-dark'>VIP</span></td>";

                            } else {

                                echo "<td>No</td>";

                            }

                            echo "<td>" . date('d/m/Y', strtotime($fila['fecha_alta'])) . "</td>";

                            if ($fila['fecha_baja'] == null) {

                                echo "<td><span class='badge bg-success'>Activo</span></td>";

                            } else {

                                echo "<td><span class='badge bg-secondary'>Baja</span></td>";

                            }

                            echo "</tr>";

                        }

                    } else {

                        echo "<tr>";
                        echo "<td colspan='7' class='text-center'>No se han encontrado socios.</td>";
                        echo "</tr>";

                    }

                    ?>

                </tbody>

            </table>

        </div>

    </div>

</main>

<?php

mysqli_stmt_close($consulta);

include_once('footer.php');

?>
