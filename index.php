<?php
require_once 'auth.php';
requireLogin();
require_once 'config.php';

$conn = getMySQLiConnection();

// ==========================================
// ESTADÍSTICAS PARA EL PANEL (Dashboard)
// ==========================================

// 1. Estadísticas de Editoriales
$res_ed = $conn->query("SELECT COUNT(*) as total, SUM(fecha_baja IS NOT NULL) as bajas FROM editorial_carlos");
$stats_ed = $res_ed->fetch_assoc();
$editoriales_activas = $stats_ed['total'] - ($stats_ed['bajas'] ?? 0);

// 2. Estadísticas de Libros
$res_lib = $conn->query("SELECT COUNT(*) as total, SUM(esta_espanol = 1) as en_espanol, SUM(fecha_baja IS NOT NULL) as bajas FROM libros_manuel");
$stats_lib = $res_lib->fetch_assoc();
$libros_activos = $stats_lib['total'] - ($stats_lib['bajas'] ?? 0);

// 3. Estadísticas de Socios
$res_soc = $conn->query("SELECT COUNT(*) as total, SUM(es_vip = 1) as vips, SUM(fecha_baja IS NOT NULL) as bajas FROM socios_ricardo");
$stats_soc = $res_soc->fetch_assoc();
$socios_activos = $stats_soc['total'] - ($stats_soc['bajas'] ?? 0);

// 4. Estadísticas de Ejemplares
$res_ej = $conn->query("SELECT COUNT(*) as total, SUM(es_referencia = 1) as referencia, SUM(fecha_baja IS NOT NULL) as bajas FROM ejemplares_juanjo");
$stats_ej = $res_ej->fetch_assoc();
$ejemplares_activos = $stats_ej['total'] - ($stats_ej['bajas'] ?? 0);

// 5. Últimos 3 préstamos activos más próximos a cumplir (fecha límite más cercana)
$sql_prestamos = "SELECT p.*, l.titulo as titulo_libro, s.nombre as nombre_socio, s.apellidos as apellidos_socio 
                  FROM prestamos_juanjorpa p
                  JOIN ejemplares_juanjo ej ON p.id_ejemplar = ej.id_ejemplar
                  JOIN libros_manuel l ON ej.id_libro = l.id_libro
                  JOIN socios_ricardo s ON p.id_socio = s.id_socio
                  WHERE p.fecha_devolucion IS NULL AND p.fecha_baja IS NULL
                  ORDER BY p.fecha_limite ASC LIMIT 3";
$res_prestamos = $conn->query($sql_prestamos);
$prestamos_proximos = [];
while ($row = $res_prestamos->fetch_assoc()) {
    $prestamos_proximos[] = $row;
}

// Formateo de fecha en español de forma nativa
$dias_semana = ['Domingo', 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado'];
$meses = ['', 'enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio', 'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre'];

$dia_sem = $dias_semana[date('w')];
$dia_num = date('j');
$mes_nombre = $meses[date('n')];
$anio = date('Y');

$fecha_formateada = "$dia_sem, $dia_num de $mes_nombre de $anio";

$seccion_activa = 'inicio';
include_once('header.php');
?>

<style>
    .saas-card {
        transition: all 0.25s ease-in-out;
        border: 1px solid rgba(0, 0, 0, 0.04) !important;
    }

    .saas-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 1rem 2rem rgba(0, 0, 0, 0.08) !important;
    }

    /* Estilo de caja cuadrada con bordes redondeados tipo SaaS */
    .icon-box-saas {
        width: 52px;
        height: 52px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 12px;
        font-size: 1.4rem;
    }
</style>

<div class="flex-grow-1 overflow-auto p-4" style="background-color: #f8f9fa;">

    <!-- Cabecera -->
    <div class="row align-items-center mb-4 pb-3 border-bottom border-light">
        <div class="col-md-7 d-flex align-items-center gap-3">
            <div class="bg-success bg-opacity-10 text-success p-2 rounded-3 d-flex align-items-center justify-content-center" style="width: 45px; height: 45px;">
                <i class="bi bi-grid-1x2-fill fs-5"></i>
            </div>
            <h1 class="h3 fw-bold text-dark mb-0">
                Biblioteca Ohara — Panel de Control
            </h1>
        </div>
        <!-- Fecha -->
        <div class="col-md-5 text-md-end mt-3 mt-md-0">
            <div class="card border-0 shadow-sm rounded-3 d-inline-block bg-white">
                <div class="card-body py-2 px-3 d-flex align-items-center">
                    <i class="bi bi-calendar3 text-success me-2 fs-5"></i>
                    <span class="small fw-bold text-secondary"><?= htmlspecialchars($fecha_formateada) ?></span>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4 mb-4">

        <!-- CARD 1: Editoriales -->
        <div class="col-xl-6 col-md-6">
            <div class="card saas-card bg-white shadow rounded-4 h-100">
                <div class="card-body p-4">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <div>
                            <span class="text-uppercase text-success fw-bold" style="font-size: 0.7rem; letter-spacing: .08em;">1. Editoriales</span>
                            <h3 class="fw-bold text-dark mt-1 mb-0"><?= $editoriales_activas ?> <span class="fs-6 text-muted fw-normal">activas</span></h3>
                        </div>
                        <div class="icon-box-saas bg-success bg-opacity-15 text-white">
                            <i class="bi bi-building"></i>
                        </div>
                    </div>
                    <div class="d-flex justify-content-between align-items-center small text-secondary pt-3 border-top border-light">
                        <span>Total registradas: <strong class="text-dark"><?= $stats_ed['total'] ?></strong></span>
                        <span class="badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-10 px-2 py-1">Bajas: <?= $stats_ed['bajas'] ?? 0 ?></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- CARD 2: Libros -->
        <div class="col-xl-6 col-md-6">
            <div class="card saas-card bg-white shadow rounded-4 h-100">
                <div class="card-body p-4">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <div>
                            <span class="text-uppercase text-success fw-bold" style="font-size: 0.7rem; letter-spacing: .08em;">2. Catálogo de Libros</span>
                            <h3 class="fw-bold text-dark mt-1 mb-0"><?= $libros_activos ?> <span class="fs-6 text-muted fw-normal">títulos</span></h3>
                        </div>
                        <div class="icon-box-saas bg-success bg-opacity-15 text-white">
                            <i class="bi bi-journal-bookmark"></i>
                        </div>
                    </div>
                    <div class="d-flex justify-content-between align-items-center small text-secondary pt-3 border-top border-light">
                        <span>En español: <strong class="text-dark"><?= $stats_lib['en_espanol'] ?></strong></span>
                        <span class="badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-10 px-2 py-1">Descatalogados: <?= $stats_lib['bajas'] ?? 0 ?></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- CARD 3: Socios -->
        <div class="col-xl-6 col-md-6">
            <div class="card saas-card bg-white shadow rounded-4 h-100">
                <div class="card-body p-4">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <div>
                            <span class="text-uppercase text-success fw-bold" style="font-size: 0.7rem; letter-spacing: .08em;">3. Socios</span>
                            <h3 class="fw-bold text-dark mt-1 mb-0"><?= $socios_activos ?> <span class="fs-6 text-muted fw-normal">activos</span></h3>
                        </div>
                        <div class="icon-box-saas bg-success bg-opacity-15 text-white">
                            <i class="bi bi-people-fill"></i>
                        </div>
                    </div>
                    <div class="d-flex justify-content-between align-items-center small text-secondary pt-3 border-top border-light">
                        <span>Socios VIP: <strong class="text-success"><?= $stats_soc['vips'] ?></strong></span>
                        <span class="badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-10 px-2 py-1">Bajas: <?= $stats_soc['bajas'] ?? 0 ?></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- CARD 4: Ejemplares -->
        <div class="col-xl-6 col-md-6">
            <div class="card saas-card bg-white shadow rounded-4 h-100">
                <div class="card-body p-4">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <div>
                            <span class="text-uppercase text-success fw-bold" style="font-size: 0.7rem; letter-spacing: .08em;">4. Ejemplares Físicos</span>
                            <h3 class="fw-bold text-dark mt-1 mb-0"><?= $ejemplares_activos ?> <span class="fs-6 text-muted fw-normal">unidades</span></h3>
                        </div>
                        <div class="icon-box-saas bg-success bg-opacity-15 text-white">
                            <i class="bi bi-qr-code-scan"></i>
                        </div>
                    </div>
                    <div class="d-flex justify-content-between align-items-center small text-secondary pt-3 border-top border-light">
                        <span>De referencia: <strong class="text-dark"><?= $stats_ej['referencia'] ?></strong></span>
                        <span class="badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-10 px-2 py-1">Dañados/Baja: <?= $stats_ej['bajas'] ?? 0 ?></span>
                    </div>
                </div>
            </div>
        </div>

    </div>

    <!-- COLUMNA 5: Préstamos (Próximos a vencer) -->
    <div class="row justify-content-center">
        <div class="col-12">
            <div class="card border-0 shadow rounded-4 overflow-hidden">
                <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center py-3 px-4">
                    <h5 class="mb-0 fw-bold fs-6">
                        <i class="bi bi-exclamation-triangle-fill text-warning me-2"></i> 5. Préstamos Activos Próximos a Vencer (Top 3)
                    </h5>
                    <a href="prestamos.php" class="btn btn-sm btn-outline-light px-3 py-2 rounded-3 fw-semibold shadow-sm d-inline-flex align-items-center gap-2">
                        <i class="bi bi-list-ul"></i> <span>Ver todos los préstamos</span>
                    </a>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0 small">
                            <thead class="table-light text-secondary text-uppercase" style="font-size: 0.7rem; letter-spacing: .08em;">
                                <tr>
                                    <th class="ps-4 py-3">ID</th>
                                    <th class="py-3">Libro</th>
                                    <th class="py-3">Socio</th>
                                    <th class="py-3">F. Préstamo</th>
                                    <th class="py-3">F. Límite</th>
                                    <th class="text-end pe-4 py-3">Acción</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($prestamos_proximos)): ?>
                                    <?php foreach ($prestamos_proximos as $p): ?>
                                        <tr>
                                            <td class="ps-4 fw-bold">#<?= $p['id_prestamo'] ?></td>
                                            <td>
                                                <div class="text-truncate fw-semibold text-dark" style="max-width: 280px;" title="<?= htmlspecialchars($p['titulo_libro']) ?>">
                                                    <?= htmlspecialchars($p['titulo_libro']) ?>
                                                </div>
                                            </td>
                                            <td class="text-secondary"><?= htmlspecialchars($p['nombre_socio'] . ' ' . $p['apellidos_socio']) ?></td>
                                            <td class="text-secondary"><?= $p['fecha_prestamo'] ?></td>
                                            <td>
                                                <span class="badge bg-warning bg-opacity-15 text-warning-emphasis border border-warning border-opacity-25 px-2 py-1">
                                                    <i class="bi bi-clock me-1"></i> <?= $p['fecha_limite'] ?>
                                                </span>
                                            </td>
                                            <td class="text-end pe-4">
                                                <a class="btn btn-info btn-sm text-white shadow-sm" title="Entregar / Registrar devolución" href="prestamos.php?accion=devolver&id=<?= $p['id_prestamo'] ?>">
                                                    <i class="bi bi-box-arrow-in-down"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="6" class="text-center text-muted py-4">No hay préstamos pendientes próximos a vencer. ¡Todo al día! 🎉</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>

<?php
include_once('footer.php');
?>