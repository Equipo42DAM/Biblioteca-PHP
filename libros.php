<?php
$seccion_activa = 'libros';
$mostrar_formulario = false;

try {
    $conexion = new PDO('mysql:host=db;dbname=biblioteca_ohara;charset=utf8', 'admin', 'admin');
    $conexion->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Procesar el formulario si se envía por POST
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $titulo = $_POST['titulo'] ?? '';
        $id_editorial = $_POST['id_editorial'] ?? '';
        $isbn = $_POST['isbn'] ?? '';
        $anio_edicion = $_POST['anio_edicion'] ?? null;
        $fecha_baja = $_POST['fecha_baja'] ?? null;

        if (!empty($titulo) && !empty($id_editorial) && !empty($isbn)) {
            $sql = "INSERT INTO libros_manuel (titulo, id_editorial, isbn, anio_edicion, fecha_baja) 
                    VALUES (:titulo, :id_editorial, :isbn, :anio_edicion, :fecha_baja)";
            
            $stmt = $conexion->prepare($sql);
            $stmt->execute([
                ':titulo' => $titulo,
                ':id_editorial' => $id_editorial,
                ':isbn' => $isbn,
                ':anio_edicion' => !empty($anio_edicion) ? $anio_edicion : null,
                ':fecha_baja' => !empty($fecha_baja) ? $fecha_baja : null
            ]);

            // Redirigir para mostrar de nuevo el catálogo
            header('Location: libros.php');
            exit();
        } else {
            $error_db = "Por favor, completa los campos obligatorios (*).";
            $mostrar_formulario = true;
        }
    }
    
    // Si la URL recibe ?vista=formulario, activamos la bandera para mostrarlo
    if (isset($_GET['vista']) && $_GET['vista'] === 'formulario') {
        $mostrar_formulario = true;
    }

    // Recuperar todos los libros de la base de datos
    $stmt = $conexion->query("SELECT id_libro, id_editorial, titulo, isbn, anio_edicion, fecha_baja FROM libros_manuel");
    $libros = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $libros = [];
    $error_conexion = $e->getMessage();
}

include_once('header.php');
?>

<div class="container-fluid py-4" style="overflow-y: auto; height: 100vh;">
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
                <div class="card shadow-sm mb-5">
                    <div class="card-header bg-white d-flex justify-content-between align-items-center py-3">
                        <h2 class="h5 card-title mb-0">Catálogo de Libros Registrados</h2>
                        <div>
                            <span class="badge bg-secondary me-2">Total: <?= count($libros) ?></span>
                            <a href="libros.php?vista=formulario" class="btn btn-success btn-sm">+ Añadir Libro</a>
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
                                        <th class="text-center">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (count($libros) > 0): ?>
                                        <?php foreach ($libros as $libro): ?>
                                            <?php $es_activo = empty($libro['fecha_baja']); ?>
                                            <tr class="<?= $es_activo ? '' : 'table-secondary' ?>">
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
                                                <td class="text-center">
                                                    <button class="btn btn-sm btn-outline-warning me-1" style="width: 110px;">Editar</button>
                                                    <button class="btn btn-sm btn-outline-danger" style="width: 110px;">Dar de Baja</button>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="8" class="text-center py-4 text-muted">No hay libros registrados en la base de datos.</td>
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
                        <h2 class="h5 card-title mb-0">Formulario de Nuevo Libro</h2>
                        <!-- Enlace para volver al catálogo sin guardar -->
                        <a href="libros.php" class="btn btn-outline-secondary btn-sm">← Volver al Catálogo</a>
                    </div>
                    <div class="card-body">
                        <form action="" method="POST">
                            
                            <input type="hidden" name="id_libro" id="id_libro">

                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label for="titulo" class="form-label">Título del Libro *</label>
                                    <input type="text" class="form-control" id="titulo" name="titulo" placeholder="Ej: Don Quijote de la Mancha" required>
                                </div>

                                <div class="col-md-6">
                                    <label for="id_editorial" class="form-label">ID Editorial (FK) *</label>
                                    <input type="number" class="form-control" id="id_editorial" name="id_editorial" placeholder="Ej: 1" required>
                                </div>

                                <div class="col-md-4">
                                    <label for="isbn" class="form-label">ISBN *</label>
                                    <input type="text" class="form-control" id="isbn" name="isbn" placeholder="978-84-376-0494-7" required>
                                </div>

                                <div class="col-md-4">
                                    <label for="anio_edicion" class="form-label">Año de Edición</label>
                                    <input type="number" class="form-control" id="anio_edicion" name="anio_edicion" placeholder="2024" min="1000" max="2099">
                                </div>

                                <div class="col-md-4">
                                    <label for="fecha_baja" class="form-label">Fecha de Baja (Borrado Lógico)</label>
                                    <input type="date" class="form-control" id="fecha_baja" name="fecha_baja">
                                    <div class="form-text">Dejar vacío para mantener el registro activo.</div>
                                </div>
                            </div>

                            <div class="mt-4 text-end">
                                <a href="libros.php" class="btn btn-outline-secondary me-2">Cancelar</a>
                                <button type="submit" class="btn btn-success">Guardar Libro</button>
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