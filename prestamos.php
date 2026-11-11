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

    <!-- Botón para abrir el Modal -->
    <div class="mb-2">
        <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#modalPrestamo">
            <i class="bi bi-plus-circle me-1"></i> Nuevo Préstamo
        </button>
    </div>

    <!-- Modal -->
    <div class="modal fade" id="modalPrestamo" tabindex="-1" aria-labelledby="modalPrestamoLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">

                <!-- Cabecera -->
                <div class="modal-header bg-dark text-white">
                    <h5 class="modal-title" id="modalPrestamoLabel">
                        <i class="bi bi-journal-plus me-2"></i>Gestión de Préstamo
                    </h5>
                </div>

                <!-- Formulario Modal -->
                <form action="guardar_prestamo.php" method="POST" enctype="multipart/form-data">
                    <div class="modal-body">

                        <div class="row g-3">

                            <!-- ID Préstamo (Normalmente autoincremental o readonly al editar) -->
                            <div class="col-md-4">
                                <label for="id_prestamo" class="form-label fw-semibold">ID Préstamo</label>
                                <input type="text" class="form-control" id="id_prestamo" name="id_prestamo" placeholder="Auto" readonly>
                            </div>

                            <!-- ID Ejemplar -->
                            <div class="col-md-4">
                                <label for="id_ejemplar" class="form-label fw-semibold">ID Ejemplar <span class="text-danger">*</span></label>
                                <input type="number" class="form-control" id="id_ejemplar" name="id_ejemplar" required placeholder="Ej: 102">
                            </div>

                            <!-- ID Socio -->
                            <div class="col-md-4">
                                <label for="id_socio" class="form-label fw-semibold">ID Socio <span class="text-danger">*</span></label>
                                <input type="number" class="form-control" id="id_socio" name="id_socio" required placeholder="Ej: 45">
                            </div>

                            <!-- Fechas -->
                            <div class="col-md-4">
                                <label for="fecha_prestamo" class="form-label fw-semibold">Fecha Préstamo <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" id="fecha_prestamo" name="fecha_prestamo" required>
                            </div>

                            <div class="col-md-4">
                                <label for="fecha_limite" class="form-label fw-semibold">Fecha Límite <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" id="fecha_limite" name="fecha_limite" required>
                            </div>

                            <div class="col-md-4">
                                <label for="fecha_devolucion" class="form-label fw-semibold">Fecha Devolución</label>
                                <input type="date" class="form-control" id="fecha_devolucion" name="fecha_devolucion">
                            </div>

                            <!-- Fianza -->
                            <div class="col-md-6">
                                <label for="tiene_fianza" class="form-label fw-semibold">¿Tiene Fianza?</label>
                                <select class="form-select" id="tiene_fianza" name="tiene_fianza">
                                    <option value="0" selected>No</option>
                                    <option value="1">Sí</option>
                                </select>
                            </div>

                            <div class="col-md-6">
                                <label for="importe_fianza" class="form-label fw-semibold">Importe Fianza (€)</label>
                                <input type="number" step="0.01" min="0" class="form-control" id="importe_fianza" name="importe_fianza" placeholder="0.00">
                            </div>

                            <!-- Campo de Subida de Imagen -->
                            <div class="col-12">
                                <label for="imagen_prestamo" class="form-label fw-semibold">Imagen del Préstamo</label>
                                <input type="file" class="form-control" id="imagen_prestamo" name="imagen_prestamo" accept="image/png, image/jpeg, image/webp">
                                <div class="form-text">Formatos permitidos: JPG, PNG o WEBP.</div>
                            </div>

                            <!-- Observaciones -->
                            <div class="col-12">
                                <label for="observaciones" class="form-label fw-semibold">Observaciones</label>
                                <textarea class="form-control" id="observaciones" name="observaciones" rows="3" placeholder="Añade notas o estado del ejemplar..."></textarea>
                            </div>

                        </div>

                    </div>

                    <!-- Pie de Modal -->
                    <div class="modal-footer bg-light">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="bi bi-x-circle me-1"></i> Cancelar
                        </button>
                        <button type="submit" class="btn btn-success">
                            <i class="bi bi-check-circle me-1"></i> Guardar Préstamo
                        </button>
                    </div>
                </form>

            </div>
        </div>
    </div>

    <!-- Listado en tabla -->
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
                                <button type="button" class="btn btn-warning btn-sm mb-1" title="Editar" data-bs-toggle="modal" data-bs-target="#modalPrestamo">
                                    <i class="bi bi-pencil-square"></i>
                                </button>
                                <button type="button" class="btn btn-danger btn-sm mb-1" title="Borrar" data-bs-toggle="modal" data-bs-target="#modalEliminar"
                                    onclick="prepararBorrado(<?= $prestamo['id_prestamo'] ?>)">
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

    <!-- Modal Confirmar Borrado -->
    <div class="modal fade" id="modalEliminar" tabindex="-1" aria-labelledby="modalEliminarLabel" aria-hidden="true">
        <div class="modal-dialog modal-sm modal-dialog-centered">
            <div class="modal-content border-success shadow-sm">

                <!-- Cabecera -->
                <div class="modal-header bg-dark text-white py-2">
                    <h6 class="modal-title fw-bold" id="modalEliminarLabel">
                        <i class="bi bi-exclamation-triangle-fill me-1"></i> Eliminar Préstamo
                    </h6>
                </div>

                <!-- Formulario POST -->
                <form action="eliminar_prestamo.php" method="POST">
                    <div class="modal-body text-center p-3">
                        <input type="hidden" id="id_prestamo_eliminar" name="id_prestamo">

                        <p class="mb-1 fw-semibold text-dark">¿Confirmas la eliminación?</p>
                    </div>

                    <!-- Pie de Modal -->
                    <div class="modal-footer bg-light py-2 px-3 justify-content-end">
                        <button type="button" class="btn btn-light btn-sm border" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-danger btn-sm">Eliminar</button>
                    </div>
                </form>

            </div>
        </div>
    </div>

</div>

<?php
include_once('footer.php');
?>