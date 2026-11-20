<?php

$seccion_activa = 'socios';

include_once('config.php');
include_once('header.php');

$conexion = getMySQLiConnection();

$socio = null;

if (isset($_GET['dni_nif'])) {

    $dni = $_GET['dni_nif'];

    $sql = "SELECT id_socio, dni_nif, nombre, apellidos,
            email, edad, es_vip, url_imagen, fecha_alta, fecha_baja
            FROM socios_ricardo
            WHERE dni_nif = ?";

    $consulta = mysqli_prepare($conexion, $sql);

    mysqli_stmt_bind_param($consulta, "s", $dni);

    mysqli_stmt_execute($consulta);

    $resultado = mysqli_stmt_get_result($consulta);

    $socio = mysqli_fetch_assoc($resultado);

    mysqli_stmt_close($consulta);
}

?>

<div class="container my-4">

    <h4 class="h3 mb-3">Consultar socio</h4>

    <!-- Formulario para buscar un socio -->
    <form action="consultarSocio.php" method="GET" class="mb-4">

        <div class="mb-3">

            <label for="dni_nif" class="form-label">DNI / NIE</label>

            <input type="text" class="form-control"
                   id="dni_nif" name="dni_nif"
                   maxlength="9" placeholder="12345678Z" required>

        </div>

        <button type="submit" class="btn btn-primary">
            Consultar
        </button>

        <a href="socios.php" class="btn btn-secondary">
            Volver
        </a>

    </form>

    <?php if (isset($_GET['dni_nif'])): ?>

        <?php if ($socio): ?>

            <div class="card">

                <div class="card-header">
                    Datos del socio
                </div>

                <div class="card-body">

                    <?php
                    $foto = $socio['url_imagen'];
                    if ($foto != "" && file_exists(__DIR__ . "/" . $foto)) {
                        echo "<img src='" . htmlspecialchars($foto) . "' alt='Foto del socio' width='100' height='100' class='rounded-circle object-fit-cover mb-3'>";
                    } else {
                        echo "<i class='bi bi-person-circle text-secondary d-block mb-3' style='font-size: 100px'></i>";
                    }
                    ?>

                    <p><strong>DNI / NIE:</strong>
                        <?php echo htmlspecialchars($socio['dni_nif']); ?>
                    </p>

                    <p><strong>Nombre:</strong>
                        <?php echo htmlspecialchars($socio['nombre']); ?>
                    </p>

                    <p><strong>Apellidos:</strong>
                        <?php echo htmlspecialchars($socio['apellidos']); ?>
                    </p>

                    <p><strong>Email:</strong>
                        <?php echo htmlspecialchars($socio['email']); ?>
                    </p>

                    <p><strong>Edad:</strong>
                        <?php echo htmlspecialchars($socio['edad']); ?>
                    </p>

                    <p><strong>Socio VIP:</strong>

                        <?php
                        if ($socio['es_vip'] == 1) {
                            echo "Sí";
                        } else {
                            echo "No";
                        }
                        ?>

                    </p>

                    <p><strong>Fecha de alta:</strong>
                        <?php echo date('d/m/Y', strtotime($socio['fecha_alta'])); ?>
                    </p>

                    <p><strong>Estado:</strong>

                        <?php
                        if (empty($socio['fecha_baja'])) {
                            echo "Activo";
                        } else {
                            echo "De baja";
                        }
                        ?>

                    </p>

                </div>

            </div>

        <?php else: ?>

            <div class="alert alert-warning">
                No se ha encontrado ningún socio con ese DNI/NIE.
            </div>

        <?php endif; ?>

    <?php endif; ?>

</div>

<?php

include_once('footer.php');

?>