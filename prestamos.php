<?php
    $seccion_activa = 'prestamos';
    include_once('header.php');
    include_once('config.php');
    $conn = getMySQLiConnection();

    $stmt = $conn->prepare("SELECT * FROM prestamos_juanjorpa");
    $stmt->execute();
    $resultado = $stmt->get_result();
    $prestamos = $resultado->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

?>
<main>
<!--Empezar a vuestro código dentro de esta etiqueta, no pongais ni html, ni head ni body, directamente vuestro div -->
    <div class="table-responsive">
        <table class="table table-striped table-hover table-bordered">
            <thead class="table-dark">
                <tr>
                    <th>ID Préstamo</th>
                    <th>ID Ejemplar</th>
                    <th>ID Socio</th>
                    <th>Fecha Prestamo</th>
                    <th>Fecha Límite</th>
                    <th>Fecha devolución</th>
                    <th>Observaciones</th>
                    <th>Importe Fianza</th>
                    <th>Tiene Fianza</th>
                    <th>Imagen</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($prestamos)): ?>
                    <?php foreach ($prestamos as $prestamo): ?>
                        <tr>
                            <td><?= $prestamo['id_prestamo'] ?></td>
                            <td><?= $prestamo['id_ejemplar'] ?></td>
                            <td><?= $prestamo['id_socio'] ?></td>
                            <td><?= $prestamo['fecha_prestamo'] ?></td>
                            <td><?= $prestamo['fecha_limite'] ?></td>
                            <td><?= $prestamo['fecha_devolucion'] ?></td>
                            <td><?= $prestamo['observaciones'] ?></td>
                            <td><?= $prestamo['importe_fianza'] ?></td>
                            <td><?= $prestamo['tiene_fianza'] ?></td>
                            <td><?= $prestamo['imagen_prestamo'] ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5" class="text-center text-muted">No se encontraron registros en prestamos_juanjoserpa.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

</main>
<?php
    include_once('footer.php');
?>