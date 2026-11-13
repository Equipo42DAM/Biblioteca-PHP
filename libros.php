<?php
$seccion_activa = 'libros';
$mostrar_formulario = false;
$libro_editar = null;
$busqueda = isset($_GET['busqueda']) ? trim($_GET['busqueda']) : '';
$criterio = isset($_GET['criterio']) ? $_GET['criterio'] : 'titulo';

try {
    $conexion = new PDO('mysql:host=db;dbname=biblioteca_ohara;charset=utf8', 'admin', 'admin');
    $conexion->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Procesar el formulario de creación o edición de libro
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $id_libro = $_POST['id_libro'] ?? '';
        $titulo = $_POST['titulo'] ?? '';
        $id_editorial = $_POST['id_editorial'] ?? '';
        $isbn = $_POST['isbn'] ?? '';
        $anio_edicion = $_POST['anio_edicion'] ?? null;
        $fecha_baja = $_POST['fecha_baja'] ?? null;
        $esta_espanol = $_POST['esta_espanol'] ?? null;

        if (!empty($titulo) && !empty($id_editorial) && !empty($isbn)) {

            if (!empty($id_libro)) {
                $sql = "UPDATE libros_manuel 
                        SET titulo = :titulo, id_editorial = :id_editorial, isbn = :isbn, anio_edicion = :anio_edicion, fecha_baja = :fecha_baja, esta_espanol = :esta_espanol
                        WHERE id_libro = :id_libro";

                $stmt = $conexion->prepare($sql);
                $stmt->execute([
                    ':titulo' => $titulo,
                    ':id_editorial' => $id_editorial,
                    ':isbn' => $isbn,
                    ':anio_edicion' => !empty($anio_edicion) ? $anio_edicion : null,
                    ':fecha_baja' => !empty($fecha_baja) ? $fecha_baja : null,
                    ':esta_espanol' => isset($esta_espanol) ? $esta_espanol : 0,
                    ':id_libro' => $id_libro
                ]);
            } else {
                $sql = "INSERT INTO libros_manuel (titulo, id_editorial, isbn, anio_edicion, fecha_baja, esta_espanol) 
                        VALUES (:titulo, :id_editorial, :isbn, :anio_edicion, :fecha_baja, :esta_espanol)";

                $stmt = $conexion->prepare($sql);
                $stmt->execute([
                    ':titulo' => $titulo,
                    ':id_editorial' => $id_editorial,
                    ':isbn' => $isbn,
                    ':anio_edicion' => !empty($anio_edicion) ? $anio_edicion : null,
                    ':fecha_baja' => !empty($fecha_baja) ? $fecha_baja : null,
                    ':esta_espanol' => isset($esta_espanol) ? $esta_espanol : 0
                ]);
            }

            header('Location: libros.php');
            exit();
        } else {
            $error_db = "Por favor, completa los campos obligatorios (*).";
            $mostrar_formulario = true;
        }
    }

    // Mostrar formulario para crear un nuevo libro
    if (isset($_GET['vista']) && $_GET['vista'] === 'formulario') {
        $mostrar_formulario = true;
    }

    // Mostrar formulario con datos para editar un libro
    if (isset($_GET['editar'])) {
        $stmt_edit = $conexion->prepare("SELECT * FROM libros_manuel WHERE id_libro = ?");
        $stmt_edit->execute([$_GET['editar']]);
        $libro_editar = $stmt_edit->fetch(PDO::FETCH_ASSOC);

        if ($libro_editar) {
            $mostrar_formulario = true;
        }
    }

    // Dar de baja a un libro
    if (isset($_GET['baja'])) {
        $id_baja = $_GET['baja'];
        $fecha_actual = date('Y-m-d');

        $stmt_baja = $conexion->prepare("UPDATE libros_manuel SET fecha_baja = ? WHERE id_libro = ?");
        $stmt_baja->execute([$fecha_actual, $id_baja]);

        header('Location: libros.php');
        exit();
    }

    // Dar de alta a un libro
    if (isset($_GET['alta'])) {
        $id_alta = $_GET['alta'];

        $stmt_alta = $conexion->prepare("UPDATE libros_manuel SET fecha_baja = NULL WHERE id_libro = ?");
        $stmt_alta->execute([$id_alta]);

        header('Location: libros.php');
        exit();
    }

    // CARGA DE LIBROS Y BÚSQUEDA UNIFICADA
    $libros = [];
    try {
        if ($busqueda !== '') {
            $columna = ($criterio === 'anio_edicion') ? 'anio_edicion' : 'titulo';

            $sql = "SELECT id_libro, id_editorial, titulo, isbn, anio_edicion, fecha_baja, esta_espanol 
                    FROM libros_manuel 
                    WHERE $columna LIKE :busqueda";
            $stmt = $conexion->prepare($sql);
            $stmt->execute(['busqueda' => '%' . $busqueda . '%']);
        } else {
            $sql = "SELECT id_libro, id_editorial, titulo, isbn, anio_edicion, fecha_baja, esta_espanol 
                    FROM libros_manuel";
            $stmt = $conexion->query($sql);
        }

        $libros = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $error_db = "Error en la consulta: " . $e->getMessage();
    }

} catch (PDOException $e) {
    $libros = [];
    $error_conexion = $e->getMessage();
}

include_once('header.php');
?>

<!-- Usamos clases oficiales de Bootstrap (vh-100 y overflow-auto) para permitir el scroll vertical -->
<div class="container-fluid py-4 vh-100 overflow-auto">
    <div class="row">
        <div class="col-12">

            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <div>
                    <h1 class="h2">Mantenimiento de Libros</h1>
                    <p class="text-muted mb-0">Registro y control del catálogo bibliográfico.</p>
                </div>
            </div>

            <?php if (isset($error_conexion)): ?>
                <div class="alert alert-danger" role="alert">
                    Error de conexión: <?= htmlspecialchars($error_conexion) ?>
                </div>
            <?php endif; ?>

            <?php if (isset($error_db)): ?>
                <div class="alert alert-warning" role="alert">
                    <?= htmlspecialchars($error_db) ?>
                </div>
            <?php endif; ?>

            <?php if (!$mostrar_formulario): ?>
                <!-- Mostrar la tabla de libros -->
                <div class="card shadow-sm mb-5">
                    <div class="card-header bg-white py-3">
                        <div class="row align-items-center g-3">
                            
                            <!-- TÍTULO -->
                            <div class="col-xl-3 col-lg-4 col-md-12">
                                <h2 class="h5 card-title mb-0">Catálogo de Libros Registrados</h2>
                            </div>

                            <!-- BARRA DE BÚSQUEDA -->
                            <div class="col-xl-6 col-lg-5 col-md-8 col-12">
                                <form action="" method="GET" class="input-group mb-0 shadow-sm">
                                    <select name="criterio" class="form-select w-auto">
                                        <option value="titulo" <?= ($criterio === 'titulo') ? 'selected' : ''; ?>>Título</option>
                                        <option value="anio_edicion" <?= ($criterio === 'anio_edicion') ? 'selected' : ''; ?>>Año Edición</option>
                                    </select>
                                    <input type="text"
                                           name="busqueda"
                                           class="form-control"
                                           placeholder="Buscar..."
                                           value="<?= htmlspecialchars($busqueda, ENT_QUOTES, 'UTF-8'); ?>">
                                    <button class="btn btn-primary px-3" type="submit">Buscar</button>
                                    <?php if ($busqueda !== ''): ?>
                                        <a href="?" class="btn btn-outline-secondary">Limpiar</a>
                                    <?php endif; ?>
                                </form>
                            </div>

                            <!-- TOTAL Y BOTÓN AÑADIR -->
                            <div class="col-xl-3 col-lg-3 col-md-4 col-12 text-md-end text-start">
                                <span class="badge bg-secondary me-2 p-2">Total: <?= count($libros) ?></span>
                                <a href="libros.php?vista=formulario" class="btn btn-success btn-sm">+ Añadir Libro</a>
                            </div>

                        </div>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-striped table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>ID</th>
                                        <th>Título</th>
                                        <th>ISBN</th>
                                        <th>Año Edición</th>
                                        <th>ID Editorial</th>
                                        <th>Estado</th>
                                        <th>Fecha Baja</th>
                                        <th>¿Es Español?</th>
                                        <th class="text-center">Acción</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (count($libros) > 0): ?>
                                        <?php foreach ($libros as $libro): ?>
                                            <?php $es_activo = empty($libro['fecha_baja']); ?>
                                            <tr class="<?= $es_activo ? '' : 'table-secondary' ?>"
                                                role="button"
                                                onclick="window.location.href='libros.php?editar=<?= $libro['id_libro'] ?>'"
                                                title="Haz clic en la fila para editar">

                                                <td><?= htmlspecialchars($libro['id_libro']) ?></td>
                                                <td class="fw-bold"><?= htmlspecialchars($libro['titulo']) ?></td>
                                                <td><?= htmlspecialchars($libro['isbn']) ?></td>
                                                <td><?= htmlspecialchars($libro['anio_edicion']) ?></td>
                                                <td><span class="badge bg-info text-dark">ID: <?= htmlspecialchars($libro['id_editorial']) ?></span></td>
                                                <td>
                                                    <?php if ($es_activo): ?>
                                                        <span class="badge bg-success">Activo</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-danger">Inactivo</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="text-muted">
                                                    <em><?= $es_activo ? 'NULL' : htmlspecialchars($libro['fecha_baja']) ?></em>
                                                </td>
                                                <td>
                                                    <?php if (isset($libro['esta_espanol']) && $libro['esta_espanol'] !== null): ?>
                                                        <?= ($libro['esta_espanol'] == 1) ? '<span class="badge bg-primary">Sí</span>' : '<span class="badge bg-warning text-dark">No</span>' ?>
                                                    <?php else: ?>
                                                        <span class="badge bg-secondary">Desconocido</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="text-center">
                                                    <?php if ($es_activo): ?>
                                                        <!-- Botón para Dar de Baja -->
                                                        <a href="libros.php?baja=<?= $libro['id_libro'] ?>"
                                                            class="btn btn-sm btn-outline-danger"
                                                            onclick="event.stopPropagation(); return confirm('¿Estás seguro de dar de baja este libro?');">
                                                            Dar de Baja
                                                        </a>
                                                    <?php else: ?>
                                                        <!-- Botón para Dar de Alta -->
                                                        <a href="libros.php?alta=<?= $libro['id_libro'] ?>"
                                                            class="btn btn-sm btn-outline-success"
                                                            onclick="event.stopPropagation(); return confirm('¿Deseas dar de alta este libro de nuevo?');">
                                                            Dar de Alta
                                                        </a>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="9" class="text-center py-4 text-muted">No hay libros registrados con ese criterio de búsqueda.</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

            <?php else: ?>
                <div class="card shadow-sm mb-5">
                    <div class="card-header bg-white d-flex justify-content-between align-items-center py-3">
                        <h2 class="h5 card-title mb-0">
                            <?= $libro_editar ? 'Editar Libro (ID: ' . $libro_editar['id_libro'] . ')' : 'Formulario de Nuevo Libro' ?>
                        </h2>
                    </div>
                    <div class="card-body">
                        <form action="" method="POST">

                            <input type="hidden" name="id_libro" value="<?= $libro_editar['id_libro'] ?? '' ?>">

                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label for="titulo" class="form-label">Título del Libro *</label>
                                    <input type="text" class="form-control" id="titulo" name="titulo"
                                        value="<?= htmlspecialchars($libro_editar['titulo'] ?? '') ?>" required>
                                </div>

                                <div class="col-md-6">
                                    <label for="id_editorial" class="form-label">ID Editorial (FK) *</label>
                                    <input type="number" class="form-control" id="id_editorial" name="id_editorial"
                                        value="<?= htmlspecialchars($libro_editar['id_editorial'] ?? '') ?>" required>
                                </div>

                                <div class="col-md-4">
                                    <label for="isbn" class="form-label">ISBN *</label>
                                    <input type="text" class="form-control" id="isbn" name="isbn"
                                        value="<?= htmlspecialchars($libro_editar['isbn'] ?? '') ?>" required>
                                </div>

                                <div class="col-md-4">
                                    <label for="anio_edicion" class="form-label">Año de Edición</label>
                                    <input type="number" class="form-control" id="anio_edicion" name="anio_edicion"
                                        value="<?= htmlspecialchars($libro_editar['anio_edicion'] ?? '') ?>" min="1000" max="2099">
                                </div>

                                <div class="col-md-4">
                                    <label for="fecha_baja" class="form-label">Fecha de Baja (Borrado Lógico)</label>
                                    <input type="date" class="form-control" id="fecha_baja" name="fecha_baja"
                                        value="<?= htmlspecialchars($libro_editar['fecha_baja'] ?? '') ?>">
                                    <div class="form-text">Dejar vacío para mantener el registro activo.</div>
                                </div>

                                <div class="col-md-4">
                                    <label for="esta_espanol" class="form-label">¿Es Español?</label>
                                    <select class="form-control" id="esta_espanol" name="esta_espanol">
                                        <option value="1" <?= (isset($libro_editar['esta_espanol']) && $libro_editar['esta_espanol'] == 1) ? 'selected' : '' ?>>Sí</option>
                                        <option value="0" <?= (isset($libro_editar['esta_espanol']) && $libro_editar['esta_espanol'] == 0) ? 'selected' : '' ?>>No</option>
                                    </select>
                                </div>
                            </div>

                            <div class="mt-4 text-end">
                                <a href="libros.php" class="btn btn-outline-secondary me-2">Cancelar</a>
                                <button type="submit" class="btn btn-success">
                                    <?= $libro_editar ? 'Actualizar Cambios' : 'Guardar Libro' ?>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            <?php endif; ?>

        </div>
    </div>
</div>

<?php
include_once('footer.php');
?>