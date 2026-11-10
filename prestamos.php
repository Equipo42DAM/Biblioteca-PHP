<?php
$seccion_activa = 'prestamos';
include_once('header.php');
include_once('config.php');

$conn = getMySQLiConnection();
$prestamos = [];

if ($conn) {
    $query = "SELECT 
                p.*,
                l.titulo AS titulo_libro,
                CONCAT(s.nombre, ' ', s.apellidos) AS nombre_socio
              FROM prestamos_juanjorpa p
              JOIN ejemplares_juanjo e ON p.id_ejemplar = e.id_ejemplar
              JOIN libros_manuel l ON e.id_libro = l.id_libro
              JOIN socios_ricardo s ON p.id_socio = s.id_socio";
              
    $stmt = $conn->prepare($query);
    if ($stmt) {
        $stmt->execute();
        $resultado = $stmt->get_result();
        $prestamos = $resultado->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
    }
}
?>

<div class="flex-grow-1 overflow-auto p-3">
    <div class="border-bottom border-dark-subtle pb-2 mb-3">
        <h2 class="h4 fw-bold text-dark mb-0">
            <i class="bi bi-arrow-left-right text-success me-2"></i> Listado de Préstamos
        </h2>
    </div>

    <div class="table-responsive border rounded" style="max-height: 80vh;">
        <table class="table table-striped border-dark-subtle align-middle">
            <thead class="table-dark sticky-top">
                <tr>
                    <th>Imagen</th>
                    <th>ID Préstamo</th>
                    <th>Título Libro</th>
                    <th>Nombre Socio</th>
                    <th>Fecha Préstamo</th>
                    <th>Fecha Límite</th>
                    <th>Fecha Devolución</th>
                    <th>Observaciones</th>
                    <th>Importe Fianza</th>
                    <th>Tiene Fianza</th>
                    <th>Acción</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($prestamos)): ?>
                    <?php foreach ($prestamos as $prestamo): ?>
                        <tr>
                            <td>
                                <?php
                                // Limpiamos posibles espacios en blanco
                                $ruta_imagen = trim($prestamo['imagen_prestamo']);

                                // Verificamos si no está vacío Y si el archivo físico realmente existe en el disco
                                if (!empty($ruta_imagen) && file_exists($ruta_imagen)):
                                ?>
                                    <img src="<?= $ruta_imagen ?>" alt="Imagen préstamo" width="50" height="50" class="rounded object-fit-cover">
                                <?php else: ?>
                                    <span class="text-muted small fst-italic">No hay imagen</span>
                                <?php endif; ?>
                            </td>
                            <td><?= $prestamo['id_prestamo'] ?></td>
                            <td><?= $prestamo['titulo_libro'] ?></td>
                            <td><?= $prestamo['nombre_socio'] ?></td>
                            <td><?= $prestamo['fecha_prestamo'] ?></td>
                            <td><?= $prestamo['fecha_limite'] ?></td>
                            <td><?= $prestamo['fecha_devolucion'] ?></td>
                            <td><?= $prestamo['observaciones'] ?></td>
                            <td><?= $prestamo['importe_fianza'] ?></td>
                            <td>
                                <?php if ($prestamo['tiene_fianza'] == 1): ?>
                                    <i class="bi bi-check-circle-fill text-success fs-5"></i>
                                <?php else: ?>
                                    <i class="bi bi-dash text-muted fs-5"></i>
                                <?php endif; ?>

                            </td>
                            <td class="text-nowrap">
                                <button type="button" class="btn btn-warning btn-sm mb-1">
                                    <i class="bi bi-pencil-square"></i>
                                </button>
                                <button type="button" class="btn btn-danger btn-sm mb-1">
                                    <i class="bi bi-archive"></i>
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="11" class="text-center text-muted">No se encontraron registros en préstamos.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    
</div>

<?php
include_once('footer.php');
?>