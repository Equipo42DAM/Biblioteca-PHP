<?php

/**
 * Módulo de Gestión de Préstamos - Biblioteca Ohara
 */
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include_once('config.php');
include_once('prestamos_funciones.php');

$conn = getMySQLiConnection();

// ==========================================
// 0. CONTROL DE TAMAÑO POST DEL SERVIDOR
// ==========================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($_POST) && empty($_FILES) && isset($_SERVER['CONTENT_LENGTH']) && $_SERVER['CONTENT_LENGTH'] > 0) {
    header("Location: prestamos.php?status=fileTooLarge");
    exit;
}

// ==========================================
// 1. PROCESAMIENTO DE PETICIONES POST
// ==========================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // A. Cambio de estado (Baja lógica / Reactivación)
    if (isset($_POST['cambiarEstadoPrestamo'], $_POST['id_prestamo'], $_POST['accion_estado'], $_POST['token_estado'])) {
        $id_prestamo    = (int)$_POST['id_prestamo'];
        $accion_estado  = $_POST['accion_estado'];
        $token_recibido = $_POST['token_estado'];

        if (!validarTokenPrestamo($id_prestamo, $token_recibido)) {
            header("Location: prestamos.php?status=internalError");
            exit;
        }

        if ($accion_estado === 'reactivated') {
            $p_check = obtenerPrestamoPorId($conn, $id_prestamo, true);
            if ($p_check && empty($p_check['fecha_devolucion'])) {
                if (!libroTieneEjemplaresDisponibles($conn, $p_check['id_ejemplar'], $id_prestamo)) {
                    header("Location: prestamos.php?status=sinStockLibro");
                    exit;
                }
            }
        }

        if (!alternarEstadoPrestamo($conn, $id_prestamo)) {
            header("Location: prestamos.php?status=notFound&id=" . $id_prestamo);
            exit;
        }

        $status = ($accion_estado === 'reactivated') ? 'reactivated' : 'deactivated';
        header("Location: prestamos.php?status={$status}&id=" . $id_prestamo);
        exit;
    }

    // B. Registro de devolución / entrega de préstamo
    if (isset($_POST['devolverPrestamo'], $_POST['id_prestamo'], $_POST['token_devolver'])) {
        $id_prestamo      = (int)$_POST['id_prestamo'];
        $token_recibido   = $_POST['token_devolver'];
        $fecha_devolucion = !empty($_POST['fecha_devolucion']) ? $_POST['fecha_devolucion'] : date('Y-m-d');

        if (!validarTokenPrestamo($id_prestamo, $token_recibido)) {
            header("Location: prestamos.php?status=internalError");
            exit;
        }

        $prestamo_actual = obtenerPrestamoPorId($conn, $id_prestamo);
        if (!$prestamo_actual) {
            header("Location: prestamos.php?status=notFound&id=" . $id_prestamo);
            exit;
        }

        if ($fecha_devolucion < substr($prestamo_actual['fecha_prestamo'], 0, 10)) {
            header("Location: prestamos.php?status=invalidDates");
            exit;
        }

        if (!devolverPrestamo($conn, $id_prestamo, $fecha_devolucion)) {
            header("Location: prestamos.php?status=updateError&id=" . $id_prestamo);
            exit;
        }

        header("Location: prestamos.php?status=returned&id=" . $id_prestamo);
        exit;
    }

    // C. Alta y edición de préstamos
    if (isset($_POST['guardarPrestamo'])) {
        $id_prestamo    = !empty($_POST['id_prestamo']) ? (int)$_POST['id_prestamo'] : 0;
        $token_recibido = $_POST['token_editar'] ?? '';

        if (!validarTokenPrestamo($id_prestamo, $token_recibido)) {
            header("Location: prestamos.php?status=internalError");
            exit;
        }

        $id_ejemplar_post = (int)$_POST['id_ejemplar'];
        $fecha_devolucion = !empty($_POST['fecha_devolucion']) ? $_POST['fecha_devolucion'] : null;

        // Validación estricta de stock por libro para préstamos activos
        if ($fecha_devolucion === null && !libroTieneEjemplaresDisponibles($conn, $id_ejemplar_post, $id_prestamo)) {
            header("Location: prestamos.php?status=sinStockLibro");
            exit;
        }

        // Validación de imagen adjunta
        if (isset($_FILES['imagen_prestamo']) && $_FILES['imagen_prestamo']['error'] !== UPLOAD_ERR_NO_FILE) {
            if (in_array($_FILES['imagen_prestamo']['error'], [UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE]) || $_FILES['imagen_prestamo']['size'] > 5 * 1024 * 1024) {
                header("Location: prestamos.php?status=fileTooLarge");
                exit;
            }

            if ($_FILES['imagen_prestamo']['error'] !== UPLOAD_ERR_OK) {
                header("Location: prestamos.php?status=invalidImage");
                exit;
            }

            $permitidos = ['image/jpeg', 'image/png', 'image/webp'];
            if (!in_array(mime_content_type($_FILES['imagen_prestamo']['tmp_name']), $permitidos)) {
                header("Location: prestamos.php?status=invalidImage");
                exit;
            }
        }

        // Validación cronológica de fechas
        $fecha_prestamo = $_POST['fecha_prestamo'] ?? '';
        $fecha_limite   = $_POST['fecha_limite'] ?? '';

        if (empty($fecha_prestamo) || empty($fecha_limite) || $fecha_limite < $fecha_prestamo || ($fecha_devolucion !== null && $fecha_devolucion < $fecha_prestamo)) {
            header("Location: prestamos.php?status=invalidDates");
            exit;
        }

        $tiene_fianza = isset($_POST['tiene_fianza']) ? (int)$_POST['tiene_fianza'] : 0;
        $datos = [
            'id_ejemplar'      => $id_ejemplar_post,
            'id_socio'         => (int)$_POST['id_socio'],
            'fecha_prestamo'   => $fecha_prestamo,
            'fecha_limite'     => $fecha_limite,
            'fecha_devolucion' => $fecha_devolucion,
            'tiene_fianza'     => $tiene_fianza,
            'importe_fianza'   => ($tiene_fianza === 1 && !empty($_POST['importe_fianza'])) ? (float)$_POST['importe_fianza'] : 0.00,
            'observaciones'    => trim($_POST['observaciones'] ?? ''),
            'url_imagen'       => null
        ];

        if ($id_prestamo > 0) {
            // Edición
            $prestamo_actual = obtenerPrestamoPorId($conn, $id_prestamo);
            if (!$prestamo_actual) {
                header("Location: prestamos.php?status=notFound&id=" . $id_prestamo);
                exit;
            }

            $datos['url_imagen'] = guardarImagenPrestamo($id_prestamo, $_FILES['imagen_prestamo'] ?? null, $prestamo_actual['url_imagen']);

            try {
                $ok = editarPrestamo($conn, $id_prestamo, $datos);
            } catch (mysqli_sql_exception $e) {
                header("Location: prestamos.php?status=updateError&id=" . $id_prestamo);
                exit;
            }

            header("Location: prestamos.php?status=" . ($ok ? "updated&id={$id_prestamo}" : "updateError&id={$id_prestamo}"));
            exit;
        } else {
            // Alta
            try {
                $nuevo_id = crearPrestamo($conn, $datos);
            } catch (mysqli_sql_exception $e) {
                header("Location: prestamos.php?status=createError");
                exit;
            }

            if (!$nuevo_id) {
                header("Location: prestamos.php?status=createError");
                exit;
            }

            $ruta_imagen = guardarImagenPrestamo($nuevo_id, $_FILES['imagen_prestamo'] ?? null);
            if ($ruta_imagen) {
                actualizarImagenPrestamo($conn, $nuevo_id, $ruta_imagen);
            }

            header("Location: prestamos.php?status=created&id=" . $nuevo_id);
            exit;
        }
    }
}

// ==========================================
// 2. CONSULTAS Y PRECARGA DE DATOS PARA LA VISTA (GET)
// ==========================================
$busqueda = trim($_GET['buscar'] ?? '');
$es_admin = isset($_SESSION['rol']) && $_SESSION['rol'] === 'admin';

// Configuración de la paginación
$por_pagina = 10; // Registros por página
$pagina_actual = isset($_GET['pagina']) ? max(1, (int)$_GET['pagina']) : 1;
$offset = ($pagina_actual - 1) * $por_pagina;

// Obtener datos limitados y total de registros
$listadoPrestamos = obtenerTodosLosPrestamos($conn, $busqueda, $es_admin, $por_pagina, $offset);
$total_registros = contarTotalPrestamos($conn, $busqueda, $es_admin);
$total_paginas = ceil($total_registros / $por_pagina);

// Control de modales por parámetros GET
$prestamo_estado = null;
if (isset($_GET['accion'], $_GET['id']) && $_GET['accion'] === 'cambiar_estado') {
    $id_get          = (int)$_GET['id'];
    $prestamo_estado = obtenerPrestamoPorId($conn, $id_get, true);
    if (!$prestamo_estado) {
        header("Location: prestamos.php?status=notFound&id=" . $id_get);
        exit;
    }
}

$prestamo_editar = null;
if (isset($_GET['accion'], $_GET['id']) && $_GET['accion'] === 'editar') {
    $id_get          = (int)$_GET['id'];
    $prestamo_editar = obtenerPrestamoPorId($conn, $id_get);
    if (!$prestamo_editar) {
        header("Location: prestamos.php?status=notFound&id=" . $id_get);
        exit;
    }
}

$prestamo_devolver = null;
if (isset($_GET['accion'], $_GET['id']) && $_GET['accion'] === 'devolver') {
    $id_get            = (int)$_GET['id'];
    $prestamo_devolver = obtenerPrestamoPorId($conn, $id_get);
    if (!$prestamo_devolver) {
        header("Location: prestamos.php?status=notFound&id=" . $id_get);
        exit;
    }
}

$id_ejemplar_edicion = $prestamo_editar['id_ejemplar'] ?? null;
$listaEjemplares     = obtenerEjemplaresParaSelect($conn, $id_ejemplar_edicion);
$listaSocios         = obtenerSociosParaSelect($conn);

// Configuración unificada para el formulario de Alta / Edición
$form = [
    'abierto'          => !empty($prestamo_editar),
    'titulo'           => $prestamo_editar ? "Editar Préstamo #{$prestamo_editar['id_prestamo']}" : 'Nuevo Préstamo',
    'boton_texto'      => $prestamo_editar ? 'Actualizar Préstamo' : 'Guardar Préstamo',
    'boton_clase'      => $prestamo_editar ? 'btn-warning' : 'btn-success',
    'token'            => $prestamo_editar ? generarTokenPrestamo($prestamo_editar['id_prestamo']) : generarTokenPrestamo(0),
    'id_prestamo'      => $prestamo_editar['id_prestamo'] ?? '',
    'id_ejemplar'      => $prestamo_editar['id_ejemplar'] ?? '',
    'id_socio'         => $prestamo_editar['id_socio'] ?? '',
    'fecha_prestamo'   => !empty($prestamo_editar['fecha_prestamo']) ? substr($prestamo_editar['fecha_prestamo'], 0, 10) : date('Y-m-d'),
    'fecha_limite'     => !empty($prestamo_editar['fecha_limite']) ? substr($prestamo_editar['fecha_limite'], 0, 10) : '',
    'fecha_devolucion' => !empty($prestamo_editar['fecha_devolucion']) ? substr($prestamo_editar['fecha_devolucion'], 0, 10) : '',
    'tiene_fianza'     => $prestamo_editar['tiene_fianza'] ?? 0,
    'importe_fianza'   => $prestamo_editar['importe_fianza'] ?? '',
    'observaciones'    => $prestamo_editar['observaciones'] ?? '',
    'url_imagen'       => $prestamo_editar['url_imagen'] ?? ''
];

$seccion_activa = 'prestamos';
include_once('header.php');
?>

<div class="flex-grow-1 overflow-auto p-3">
    <!-- Notificaciones Toast -->
    <?php include_once("prestamos_notificaciones.php") ?>

    <div class="border-bottom border-dark-subtle pb-2 mb-3">
        <h2 class="h4 fw-bold text-dark mb-0">
            <i class="bi bi-arrow-left-right text-success me-2"></i> Listado de Préstamos
        </h2>
    </div>

    <!-- Barra de Acciones: Nuevo Préstamo + Buscador -->
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#modalPrestamo">
            <i class="bi bi-plus-circle me-1"></i> Nuevo Préstamo
        </button>

        <form action="prestamos.php" method="GET" class="d-flex gap-2" style="max-width: 380px; width: 100%;">
            <div class="position-relative flex-grow-1">
                <input type="text" class="form-control pe-5" name="buscar"
                    placeholder="Buscar libro, socio, ID..."
                    value="<?= htmlspecialchars($busqueda) ?>">

                <?php if ($busqueda !== ''): ?>
                    <a href="prestamos.php" class="btn btn-link text-muted position-absolute top-50 end-0 translate-middle-y text-decoration-none pe-3" title="Limpiar búsqueda">
                        <i class="bi bi-x-lg"></i>
                    </a>
                <?php endif; ?>
            </div>

            <!-- Botón de búsqueda principal -->
            <button type="submit" class="btn btn-dark" title="Buscar">
                <i class="bi bi-search"></i>
            </button>
        </form>
    </div>

    <!-- Tabla de Préstamos -->
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
                        <?php $esta_de_baja = !empty($prestamo['fecha_baja']) && $prestamo['fecha_baja'] !== '0000-00-00 00:00:00'; ?>
                        <tr class="<?= $esta_de_baja ? 'table-secondary text-muted' : '' ?>">
                            <td>
                                <?php
                                $ruta_imagen = trim($prestamo['url_imagen'] ?? '');
                                if (!empty($ruta_imagen) && file_exists($ruta_imagen)):
                                ?>
                                    <img src="<?= $ruta_imagen ?>?v=<?= filemtime($ruta_imagen) ?>" alt="Imagen préstamo" width="50" height="50" class="rounded object-fit-cover">
                                <?php else: ?>
                                    <span class="text-muted small fst-italic">No hay imagen</span>
                                <?php endif; ?>
                            </td>
                            <td><?= $prestamo['id_prestamo'] ?></td>
                            <td>
                                <div style="max-width: 240px; white-space: normal;">
                                    <?= htmlspecialchars($prestamo['titulo_libro']) ?>
                                </div>
                            </td>
                            <td><?= htmlspecialchars($prestamo['nombre_socio']) ?></td>
                            <td>
                                <?php if ($prestamo['tiene_fianza'] == 1): ?>
                                    <i class="bi bi-check-circle-fill text-success fs-5"></i>
                                <?php else: ?>
                                    <i class="bi bi-dash text-muted fs-5"></i>
                                <?php endif; ?>
                            </td>
                            <td><?= $prestamo['importe_fianza'] ?></td>
                            <td><?= htmlspecialchars($prestamo['observaciones'] ?? '') ?></td>
                            <td><?= $prestamo['fecha_prestamo'] ?></td>
                            <td><?= $prestamo['fecha_limite'] ?></td>
                            <td><?= $prestamo['fecha_devolucion'] ?></td>
                            <td><?= $prestamo['fecha_baja'] ?></td>
                            <td class="text-nowrap">
                                <a class="btn btn-warning btn-sm mb-1" title="Editar" href="prestamos.php?accion=editar&id=<?= $prestamo['id_prestamo'] ?>">
                                    <i class="bi bi-pencil-square"></i>
                                </a>

                                <?php $esta_devuelto = !empty($prestamo['fecha_devolucion']) && $prestamo['fecha_devolucion'] !== '0000-00-00' && $prestamo['fecha_devolucion'] !== '0000-00-00 00:00:00'; ?>
                                <?php if (!$esta_de_baja && !$esta_devuelto): ?>
                                    <a class="btn btn-info btn-sm mb-1 text-white" title="Entregar / Registrar devolución" href="prestamos.php?accion=devolver&id=<?= $prestamo['id_prestamo'] ?>">
                                        <i class="bi bi-box-arrow-in-down"></i>
                                    </a>
                                <?php endif; ?>

                                <?php if ($esta_de_baja): ?>
                                    <a class="btn btn-success btn-sm mb-1" title="Reactivar préstamo" href="prestamos.php?accion=cambiar_estado&id=<?= $prestamo['id_prestamo'] ?>">
                                        <i class="bi bi-arrow-counterclockwise"></i>
                                    </a>
                                <?php else: ?>
                                    <a class="btn btn-danger btn-sm mb-1" title="Dar de baja" href="prestamos.php?accion=cambiar_estado&id=<?= $prestamo['id_prestamo'] ?>">
                                        <i class="bi bi-archive"></i>
                                    </a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="12" class="text-center text-muted">No se encontraron registros en préstamos.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <!-- Paginación con Bootstrap -->
    <?php if ($total_paginas > 1): ?>
        <nav aria-label="Navegación de páginas" class="mt-3">
            <ul class="pagination justify-content-center mb-0"
                style="--bs-pagination-active-bg: #198754; --bs-pagination-active-border-color: #198754; --bs-pagination-color: #198754; --bs-pagination-hover-color: #146c43;">

                <!-- Botón Anterior -->
                <li class="page-item <?= ($pagina_actual <= 1) ? 'disabled' : '' ?>">
                    <a class="page-link" href="prestamos.php?pagina=<?= $pagina_actual - 1 ?><?= $busqueda !== '' ? '&buscar=' . urlencode($busqueda) : '' ?>">Anterior</a>
                </li>

                <!-- Números de página -->
                <?php for ($i = 1; $i <= $total_paginas; $i++): ?>
                    <li class="page-item <?= ($pagina_actual === $i) ? 'active' : '' ?>">
                        <a class="page-link" href="prestamos.php?pagina=<?= $i ?><?= $busqueda !== '' ? '&buscar=' . urlencode($busqueda) : '' ?>"><?= $i ?></a>
                    </li>
                <?php endfor; ?>

                <!-- Botón Siguiente -->
                <li class="page-item <?= ($pagina_actual >= $total_paginas) ? 'disabled' : '' ?>">
                    <a class="page-link" href="prestamos.php?pagina=<?= $pagina_actual + 1 ?><?= $busqueda !== '' ? '&buscar=' . urlencode($busqueda) : '' ?>">Siguiente</a>
                </li>
            </ul>
        </nav>
    <?php endif; ?>

    <!-- ========================================== -->
    <!-- 3. MODALES DE GESTIÓN                        -->
    <!-- ========================================== -->

    <!-- Modal Alta / Edición -->
    <?php if ($form['abierto']): ?>
        <div class="modal-backdrop fade show"></div>
    <?php endif; ?>

    <div class="modal fade <?= $form['abierto'] ? 'show d-block' : '' ?>" id="modalPrestamo" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-dark text-white">
                    <h5 class="modal-title"><?= $form['titulo'] ?></h5>
                    <a href="prestamos.php" class="btn-close btn-close-white" aria-label="Cerrar"></a>
                </div>

                <form action="prestamos.php" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="token_editar" value="<?= $form['token'] ?>">

                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-md-2">
                                <label for="id_prestamo" class="form-label fw-semibold">ID</label>
                                <input type="text" class="form-control bg-light" id="id_prestamo" name="id_prestamo" value="<?= $form['id_prestamo'] ?>" placeholder="Auto" readonly>
                            </div>

                            <div class="col-md-5">
                                <label for="id_ejemplar" class="form-label fw-semibold">Libro <span class="text-danger">*</span></label>
                                <select class="form-select" id="id_ejemplar" name="id_ejemplar" required>
                                    <option value="" disabled <?= empty($form['id_ejemplar']) ? 'selected' : '' ?>>Selecciona un libro...</option>
                                    <?php foreach ($listaEjemplares as $ej): ?>
                                        <?php $disp = max(0, (int)$ej['disponibles']); ?>
                                        <option value="<?= $ej['id_ejemplar'] ?>" <?= ($form['id_ejemplar'] == $ej['id_ejemplar']) ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($ej['titulo_libro']) ?> (Disponibles: <?= $disp ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="col-md-5">
                                <label for="id_socio" class="form-label fw-semibold">Socio <span class="text-danger">*</span></label>
                                <select class="form-select" id="id_socio" name="id_socio" required>
                                    <option value="" disabled <?= empty($form['id_socio']) ? 'selected' : '' ?>>Selecciona un socio...</option>
                                    <?php foreach ($listaSocios as $soc): ?>
                                        <option value="<?= $soc['id_socio'] ?>" <?= ($form['id_socio'] == $soc['id_socio']) ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($soc['nombre_socio']) ?> (#<?= $soc['id_socio'] ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="col-md-4">
                                <label for="fecha_prestamo" class="form-label fw-semibold">Fecha Préstamo <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" id="fecha_prestamo" name="fecha_prestamo" required value="<?= $form['fecha_prestamo'] ?>">
                            </div>

                            <div class="col-md-4">
                                <label for="fecha_limite" class="form-label fw-semibold">Fecha Límite <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" id="fecha_limite" name="fecha_limite" required value="<?= $form['fecha_limite'] ?>">
                            </div>

                            <div class="col-md-4">
                                <label for="fecha_devolucion" class="form-label fw-semibold">Fecha Devolución</label>
                                <input type="date" class="form-control" id="fecha_devolucion" name="fecha_devolucion" value="<?= $form['fecha_devolucion'] ?>">
                            </div>

                            <div class="col-md-6">
                                <label for="tiene_fianza" class="form-label fw-semibold">¿Tiene Fianza?</label>
                                <select class="form-select" id="tiene_fianza" name="tiene_fianza">
                                    <option value="0" <?= ($form['tiene_fianza'] == 0) ? 'selected' : '' ?>>No</option>
                                    <option value="1" <?= ($form['tiene_fianza'] == 1) ? 'selected' : '' ?>>Sí</option>
                                </select>
                            </div>

                            <div class="col-md-6">
                                <label for="importe_fianza" class="form-label fw-semibold">Importe Fianza (€)</label>
                                <input type="number" step="0.01" min="0" class="form-control" id="importe_fianza" name="importe_fianza" placeholder="0.00" value="<?= $form['importe_fianza'] ?>">
                            </div>

                            <div class="col-12">
                                <label for="imagen_prestamo" class="form-label fw-semibold">Imagen del Préstamo</label>
                                <?php if (!empty($form['url_imagen']) && file_exists($form['url_imagen'])): ?>
                                    <div class="d-flex align-items-center gap-3 mb-2 p-2 border rounded bg-light">
                                        <img src="<?= $form['url_imagen'] ?>?v=<?= filemtime($form['url_imagen']) ?>" width="55" height="55" class="rounded object-fit-cover">
                                        <span class="small text-muted">Imagen actual (subir otra la reemplazará).</span>
                                    </div>
                                <?php endif; ?>
                                <input type="file" class="form-control" id="imagen_prestamo" name="imagen_prestamo" accept="image/png, image/jpeg, image/webp">
                                <div class="form-text">Formatos permitidos: JPG, PNG o WEBP (máx. 5 MB).</div>
                            </div>

                            <div class="col-12">
                                <label for="observaciones" class="form-label fw-semibold">Observaciones</label>
                                <textarea class="form-control" id="observaciones" name="observaciones" rows="3"><?= htmlspecialchars($form['observaciones']) ?></textarea>
                            </div>
                        </div>
                    </div>

                    <div class="modal-footer bg-light">
                        <a href="prestamos.php" class="btn btn-secondary">Cancelar</a>
                        <button type="submit" class="btn <?= $form['boton_clase'] ?>" name="guardarPrestamo">
                            <?= $form['boton_texto'] ?>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Registrar Devolución / Entrega -->
    <?php if ($prestamo_devolver): ?>
        <div class="modal-backdrop fade show"></div>
        <div class="modal fade show d-block" id="modalDevolverPrestamo" tabindex="-1" aria-labelledby="modalDevolverLabel" aria-modal="true" role="dialog">
            <div class="modal-dialog modal-sm modal-dialog-centered">
                <div class="modal-content shadow-sm">
                    <div class="modal-header bg-dark text-white py-2">
                        <h6 class="modal-title fw-bold" id="modalDevolverLabel">
                            <i class="bi bi-bookmark-check-fill text-info me-1"></i> Entregar Préstamo
                        </h6>
                    </div>

                    <form action="prestamos.php" method="POST">
                        <div class="modal-body p-3">
                            <input type="hidden" name="id_prestamo" value="<?= $prestamo_devolver['id_prestamo'] ?>">
                            <input type="hidden" name="token_devolver" value="<?= generarTokenPrestamo($prestamo_devolver['id_prestamo']) ?>">

                            <div class="small text-muted text-center mb-3">
                                <strong>Préstamo #<?= $prestamo_devolver['id_prestamo'] ?></strong><br>
                                <?= htmlspecialchars($prestamo_devolver['titulo_libro']) ?><br>
                                <span class="fst-italic">Socio: <?= htmlspecialchars($prestamo_devolver['nombre_socio']) ?></span>
                            </div>

                            <label for="fecha_devolucion_modal" class="form-label small fw-semibold mb-1">Fecha de devolución</label>
                            <input type="date" class="form-control form-control-sm" id="fecha_devolucion_modal" name="fecha_devolucion" value="<?= date('Y-m-d') ?>" min="<?= substr($prestamo_devolver['fecha_prestamo'], 0, 10) ?>" required>
                        </div>

                        <div class="modal-footer bg-light py-2 px-3 justify-content-end">
                            <a href="prestamos.php" class="btn btn-light btn-sm border">Cancelar</a>
                            <button type="submit" class="btn btn-info btn-sm text-white" name="devolverPrestamo">Confirmar entrega</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- Modal Confirmación Baja / Reactivación -->
    <?php if ($prestamo_estado): ?>
        <?php $modal_es_baja = !empty($prestamo_estado['fecha_baja']) && $prestamo_estado['fecha_baja'] !== '0000-00-00 00:00:00'; ?>
        <div class="modal-backdrop fade show"></div>
        <div class="modal fade show d-block" id="modalCambiarEstado" tabindex="-1" aria-labelledby="modalCambiarEstadoLabel" aria-modal="true" role="dialog">
            <div class="modal-dialog modal-sm modal-dialog-centered">
                <div class="modal-content shadow-sm">
                    <div class="modal-header bg-dark text-white py-2">
                        <h6 class="modal-title fw-bold" id="modalCambiarEstadoLabel">
                            <?php if ($modal_es_baja): ?>
                                <i class="bi bi-arrow-counterclockwise text-success me-1"></i> Reactivar Préstamo
                            <?php else: ?>
                                <i class="bi bi-exclamation-triangle-fill text-warning me-1"></i> Dar de Baja Préstamo
                            <?php endif; ?>
                        </h6>
                    </div>

                    <form action="prestamos.php" method="POST">
                        <div class="modal-body text-center p-3">
                            <input type="hidden" name="id_prestamo" value="<?= $prestamo_estado['id_prestamo'] ?>">
                            <input type="hidden" name="accion_estado" value="<?= $modal_es_baja ? 'reactivated' : 'deactivated' ?>">
                            <input type="hidden" name="token_estado" value="<?= generarTokenPrestamo($prestamo_estado['id_prestamo']) ?>">

                            <p class="mb-1 fw-semibold text-dark">
                                <?= $modal_es_baja ? '¿Confirmas que deseas reactivar este préstamo?' : '¿Confirmas que deseas dar de baja este préstamo?' ?>
                            </p>

                            <div class="small text-muted mt-2">
                                <strong>Préstamo #<?= $prestamo_estado['id_prestamo'] ?></strong><br>
                                <?= htmlspecialchars($prestamo_estado['titulo_libro']) ?><br>
                                <span class="fst-italic">Socio: <?= htmlspecialchars($prestamo_estado['nombre_socio']) ?></span>
                            </div>
                        </div>

                        <div class="modal-footer bg-light py-2 px-3 justify-content-end">
                            <a href="prestamos.php" class="btn btn-light btn-sm border">Cancelar</a>
                            <?php if ($modal_es_baja): ?>
                                <button type="submit" class="btn btn-success btn-sm" name="cambiarEstadoPrestamo">Reactivar</button>
                            <?php else: ?>
                                <button type="submit" class="btn btn-danger btn-sm" name="cambiarEstadoPrestamo">Dar de baja</button>
                            <?php endif; ?>
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

<!-- Script de Inicialización y Limpieza de URL -->
<script>
    document.querySelectorAll('.toast').forEach(toastEl => new bootstrap.Toast(toastEl).show());

    const params = new URLSearchParams(window.location.search);
    if (params.has('status') && window.history.replaceState) {
        params.delete('status');
        params.delete('id');
        const nuevaUrl = window.location.pathname + (params.toString() ? '?' + params.toString() : '');
        window.history.replaceState(null, null, nuevaUrl);
    }
</script>