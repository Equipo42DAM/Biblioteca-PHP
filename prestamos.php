<?php
include_once('config.php');
include_once('prestamos_funciones.php');

$conn = getMySQLiConnection();
$listadoPrestamos = obtenerTodosLosPrestamos($conn);

// Doy de baja al Prestamo
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['bajaPrestamo'], $_POST['id_prestamo'], $_POST['token_eliminar'])) {
    $id_prestamo = (int)$_POST['id_prestamo'];
    $token_recibido = $_POST['token_eliminar'];

    // Validar token firmado antes de tocar la base de datos
    if (!validarTokenPrestamo($id_prestamo, $token_recibido)) {
        header("Location: prestamos.php?status=internalError");
        exit;
    }

    darDeBajaPrestamo($conn, $_POST['id_prestamo']);
    header("Location: prestamos.php?status=deactivated&id=" . (int)$_POST['id_prestamo']);
    exit;
}

// Si el registro está dado de baja lo vuelvo a dar de alta
$prestamo_a_eliminar = null;
if (isset($_GET['accion'], $_GET['id']) && $_GET['accion'] === 'eliminar') {
    $p = obtenerPrestamoPorId($conn, $_GET['id']);

    if ($p && !empty($p['fecha_baja']) && $p['fecha_baja'] !== '0000-00-00 00:00:00') {
        header("Location: prestamos.php?status=already_activated&id=" . $p['id_prestamo']);
        exit;
    }
    $prestamo_a_eliminar = $p;
}

// Configuro un toast para gestionar los mensajes que muestro cuando se edita, crea o elimina
$toast_config = null;
if (isset($_GET['status'])) {
    // Utilizo esto para construir el id de forma segura para que no pueda ser un texto y que sea un entero
    $id_ref = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    $texto_id = $id_ref > 0 ? " #{$id_ref}" : "";

    // Manejo los mensajes que salen en el toast
    switch ($_GET['status']) {
        case 'created':
            $toast_config = [
                'icon'    => 'bi-plus-circle-fill text-success',
                'titulo'  => 'Préstamo Creado',
                'mensaje' => "El préstamo{$texto_id} ha sido registrado correctamente."
            ];
            break;

        case 'updated':
            $toast_config = [
                'icon'    => 'bi-pencil-square text-primary',
                'titulo'  => 'Préstamo Actualizado',
                'mensaje' => "Los datos del préstamo{$texto_id} han sido modificados correctamente."
            ];
            break;

        case 'deactivated':
            $toast_config = [
                'icon'    => 'bi-trash-fill text-danger',
                'titulo'  => 'Préstamo Eliminado',
                'mensaje' => "El préstamo{$texto_id} se ha dado de baja correctamente."
            ];
            break;

        case 'already_activated':
            $toast_config = [
                'icon'    => 'bi-plus-circle-fill text-success',
                'titulo'  => 'Préstamo actualizado',
                'mensaje' => "El préstamo{$texto_id} se ha reactivado."
            ];
            break;
        case 'internalError':
            $toast_config = [
                'icon'    => 'bi bi-ban-fill text-danger',
                'titulo'  => 'Acción no permitida',
                'mensaje' => "Se ha producido un error, se ha cancelado la acción."
            ];
            break;
    }
}

$seccion_activa = 'prestamos';
include_once('header.php');
?>

<div class="flex-grow-1 overflow-auto p-3">
    <!-- Toast para los mensajes -->
    <?php if ($toast_config): ?>
        <div class="toast-container position-fixed top-0 end-0 p-3" style="z-index: 1080;">

            <div class="toast border-0 shadow-sm"
                role="alert"
                aria-live="assertive"
                aria-atomic="true"
                data-bs-autohide="true"
                data-bs-delay="4000">

                <!-- Cabecera -->
                <div class="toast-header bg-dark text-white">
                    <i class="bi <?= $toast_config['icon'] ?> me-2"></i>
                    <strong class="me-auto"><?= $toast_config['titulo'] ?></strong>
                    <small class="text-white-50">Ahora mismo</small>
                    <!-- Botón de cierre -->
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="toast" aria-label="Close"></button>
                </div>

                <!-- Cuerpo del Toast -->
                <div class="toast-body bg-white text-dark">
                    <?= $toast_config['mensaje'] ?>
                </div>

            </div>
        </div>
    <?php endif; ?>

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

    <!-- Listado en tabla -->
    <div class="table-responsive border rounded" style="max-height: 80vh;">
        <table class="table table-striped border-dark-subtle align-middle small">
            <thead class="table-dark sticky-top">
                <tr>
                    <th>Imagen</th>
                    <th>ID</th>
                    <th>Título Libro</th>
                    <th>Nombre Socio</th>
                    <th>Tiene Fianza</th>
                    <th>Importe Fianza</th>
                    <th>Observaciones</th>
                    <th>Fecha Préstamo</th>
                    <th>Fecha Límite</th>
                    <th>Fecha Devolución</th>
                    <th>Fecha Baja</th>
                    <th>Acción</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($listadoPrestamos)): ?>
                    <?php foreach ($listadoPrestamos as $prestamo): ?>
                        <tr>
                            <td>
                                <?php
                                // Limpiamos posibles espacios en blanco
                                $ruta_imagen = trim($prestamo['url_imagen']);

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
                            <td>
                                <?php if ($prestamo['tiene_fianza'] == 1): ?>
                                    <i class="bi bi-check-circle-fill text-success fs-5"></i>
                                <?php else: ?>
                                    <i class="bi bi-dash text-muted fs-5"></i>
                                <?php endif; ?>

                            </td>
                            <td><?= $prestamo['importe_fianza'] ?></td>
                            <td><?= $prestamo['observaciones'] ?></td>
                            <td><?= $prestamo['fecha_prestamo'] ?></td>
                            <td><?= $prestamo['fecha_limite'] ?></td>
                            <td><?= $prestamo['fecha_devolucion'] ?></td>
                            <td><?= $prestamo['fecha_baja'] ?></td>
                            <td class="text-nowrap">
                                <!-- Botón Editar -->
                                <a class="btn btn-warning btn-sm mb-1" title="Editar" data-bs-toggle="modal" data-bs-target="#modalPrestamo">
                                    <i class="bi bi-pencil-square"></i>
                                </a>

                                <!-- Enlace Eliminar -->
                                <a class="btn btn-danger btn-sm mb-1" title="Borrar"
                                    href="prestamos.php?accion=eliminar&id=<?= $prestamo['id_prestamo'] ?>">
                                    <i class="bi bi-archive"></i>
                                </a>
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

    <!-- Modal Para nuevo y editar -->
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

    <!-- Modal Borrado -->
    <?php if ($prestamo_a_eliminar): ?>
        <div class="modal-backdrop fade show"></div>

        <!-- Modal -->
        <div class="modal fade show d-block" id="modalEliminar" tabindex="-1" aria-labelledby="modalEliminarLabel" aria-modal="true" role="dialog">
            <div class="modal-dialog modal-sm modal-dialog-centered">
                <div class="modal-content border-success shadow-sm">

                    <!-- Cabecera -->
                    <div class="modal-header bg-dark text-white py-2">
                        <h6 class="modal-title fw-bold" id="modalEliminarLabel">
                            <i class="bi bi-exclamation-triangle-fill me-1"></i> Eliminar Préstamo
                        </h6>
                    </div>

                    <!-- Formulario POST -->
                    <form action="prestamos.php" method="POST">
                        <div class="modal-body text-center p-3">
                            <!-- Envia el ID y el Token firmado generado por la función -->
                            <input type="hidden" name="id_prestamo" value="<?= $prestamo_a_eliminar['id_prestamo'] ?>">
                            <input type="hidden" name="token_eliminar" value="<?= generarTokenPrestamo($prestamo_a_eliminar['id_prestamo']) ?>">

                            <p class="mb-1 fw-semibold text-dark">¿Confirmas la eliminación?</p>

                            <!-- Mostramos la información del préstamo traído por la función -->
                            <div class="small text-muted mt-2">
                                <strong>Préstamo #<?= $prestamo_a_eliminar['id_prestamo'] ?></strong><br>
                                <?= $prestamo_a_eliminar['titulo_libro'] ?><br>
                                <span class="fst-italic">Socio: <?= $prestamo_a_eliminar['nombre_socio'] ?></span>
                            </div>
                        </div>

                        <!-- Pie de Modal -->
                        <div class="modal-footer bg-light py-2 px-3 justify-content-end">
                            <a href="prestamos.php" class="btn btn-light btn-sm border">Cancelar</a>
                            <button type="submit" class="btn btn-danger btn-sm" name="bajaPrestamo">Eliminar</button>
                        </div>
                    </form>

                </div>
            </div>
        </div>
    <?php endif; ?>

</div>

<?php
include_once('footer.php');
?>

<!-- Solo un poco de js para que el modal de los mensaje se vea correctamen -->
<script>
    document.querySelectorAll('.toast').forEach(toastEl => new bootstrap.Toast(toastEl).show());
    if (window.history.replaceState) {
        window.history.replaceState(null, null, window.location.pathname);
    }
</script>