<?php
require_once 'auth.php';
requireLogin();
require_once 'config.php';

$seccion_activa = 'ejemplares';
$error = '';
$mensaje = $_SESSION['mensaje_ejemplares'] ?? '';
unset($_SESSION['mensaje_ejemplares']);
$mostrar_formulario = isset($_GET['nuevo']) || isset($_GET['editar']);
$formulario = [
    'id_ejemplar' => '',
    'id_libro' => '',
    'id_estado' => '',
    'codigo_barras' => '',
    'signatura' => '',
    'numero_copia' => '1',
    'es_referencia' => '0',
    'url_imagen' => '',
];
$libros = [];
$estados = [];
$ejemplares = [];
$datos_disponibles = true;
$busqueda = is_string($_GET['busqueda'] ?? '') ? trim($_GET['busqueda'] ?? '') : '';

function escaparEjemplar($valor): string
{
    return htmlspecialchars((string) $valor, ENT_QUOTES, 'UTF-8');
}

function enteroPositivoEjemplar($valor)
{
    if (!is_string($valor) && !is_int($valor)) {
        return false;
    }
    return filter_var($valor, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 2147483647]]);
}

try {
    $conexion = getMySQLiConnection();

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $token = $_POST['csrf_token'] ?? '';
        $accion = $_POST['accion'] ?? '';
        if (!is_string($token) || !hash_equals($_SESSION['csrf_token'], $token)) {
            http_response_code(403);
            $error = 'No se pudo validar el formulario. Inténtalo de nuevo.';
        } elseif (!is_string($accion) || !in_array($accion, ['guardar', 'baja', 'alta'], true)) {
            http_response_code(400);
            $error = 'La acción solicitada no es válida.';
        } elseif ($accion === 'baja' || $accion === 'alta') {
            $id = enteroPositivoEjemplar($_POST['id_ejemplar'] ?? '');
            if ($id === false) {
                http_response_code(400);
                $error = 'El ejemplar indicado no es válido.';
            } else {
                if ($accion === 'baja') {
                    $stmt = $conexion->prepare(
                        'UPDATE ejemplares_juanjo e SET e.fecha_baja = NOW()
                         WHERE e.id_ejemplar = ? AND e.fecha_baja IS NULL
                         AND NOT EXISTS (SELECT 1 FROM prestamos_juanjorpa p
                             WHERE p.id_ejemplar = e.id_ejemplar AND p.fecha_baja IS NULL
                             AND p.fecha_devolucion IS NULL)'
                    );
                } else {
                    $stmt = $conexion->prepare(
                        'UPDATE ejemplares_juanjo SET fecha_baja = NULL
                         WHERE id_ejemplar = ? AND fecha_baja IS NOT NULL'
                    );
                }
                $stmt->bind_param('i', $id);
                $stmt->execute();
                if ($stmt->affected_rows === 0) {
                    $error = $accion === 'baja'
                        ? 'No se pudo dar de baja: el ejemplar no existe, ya está de baja o tiene un préstamo pendiente.'
                        : 'No se pudo reactivar: el ejemplar no existe o ya está activo.';
                } else {
                    $_SESSION['mensaje_ejemplares'] = $accion === 'baja' ? 'Ejemplar dado de baja.' : 'Ejemplar reactivado.';
                    header('Location: ejemplares.php');
                    exit;
                }
                $stmt->close();
            }
        } else {
            $mostrar_formulario = true;
            foreach ($formulario as $campo => $valor) {
                $dato = $_POST[$campo] ?? ($campo === 'es_referencia' ? '0' : '');
                if (!is_string($dato)) {
                    $error = 'Los datos del formulario no son válidos.';
                    break;
                }
                $formulario[$campo] = trim($dato);
            }

            $id = $formulario['id_ejemplar'] === '' ? 0 : enteroPositivoEjemplar($formulario['id_ejemplar']);
            $id_libro = enteroPositivoEjemplar($formulario['id_libro']);
            $id_estado = enteroPositivoEjemplar($formulario['id_estado']);
            $numero_copia = enteroPositivoEjemplar($formulario['numero_copia']);
            $codigo = $formulario['codigo_barras'];
            $signatura = $formulario['signatura'] !== '' ? $formulario['signatura'] : null;
            $imagen = $formulario['url_imagen'] !== '' ? $formulario['url_imagen'] : null;
            $referencia = $formulario['es_referencia'];

            if ($error === '' && ($id === false || $id_libro === false || $id_estado === false || $numero_copia === false
                || !preg_match('/^.{1,50}$/us', $codigo) || !preg_match('/^.{0,50}$/us', $signatura ?? '')
                || !preg_match('/^.{0,255}$/us', $imagen ?? '') || !in_array($referencia, ['0', '1'], true))) {
                $error = 'Revisa los campos obligatorios y sus valores. El número de copia debe ser positivo.';
            }

            if ($error === '') {
                $stmt = $conexion->prepare(
                    'SELECT e.id_libro, EXISTS(SELECT 1 FROM prestamos_juanjorpa p WHERE p.id_ejemplar = e.id_ejemplar) AS tiene_prestamos
                     FROM ejemplares_juanjo e WHERE e.id_ejemplar = ?'
                );
                $stmt->bind_param('i', $id);
                $stmt->execute();
                $actual = $stmt->get_result()->fetch_assoc();
                $stmt->close();

                if ($id && !$actual) {
                    $error = 'El ejemplar que intentas editar no existe.';
                } elseif ($actual && (int) $actual['id_libro'] !== $id_libro && (int) $actual['tiene_prestamos'] === 1) {
                    $error = 'No se puede cambiar el libro de un ejemplar con historial de préstamos.';
                } else {
                    $stmt = $conexion->prepare('SELECT id_libro FROM libros_manuel WHERE id_libro = ? AND (fecha_baja IS NULL OR id_libro = ?)');
                    $libro_actual = $actual ? (int) $actual['id_libro'] : 0;
                    $stmt->bind_param('ii', $id_libro, $libro_actual);
                    $stmt->execute();
                    $libro_valido = $stmt->get_result()->fetch_assoc();
                    $stmt->close();
                    $stmt = $conexion->prepare('SELECT id_estado FROM estados_ejemplar WHERE id_estado = ?');
                    $stmt->bind_param('i', $id_estado);
                    $stmt->execute();
                    $estado_valido = $stmt->get_result()->fetch_assoc();
                    $stmt->close();

                    if (!$libro_valido || !$estado_valido) {
                        $error = 'Selecciona un libro disponible y un estado válidos.';
                    } else {
                        try {
                            if ($id) {
                                $stmt = $conexion->prepare(
                                    'UPDATE ejemplares_juanjo e SET id_libro = ?, id_estado = ?, codigo_barras = ?, signatura = ?,
                                     numero_copia = ?, es_referencia = ?, url_imagen = ?
                                     WHERE id_ejemplar = ? AND (id_libro = ? OR NOT EXISTS
                                         (SELECT 1 FROM prestamos_juanjorpa p WHERE p.id_ejemplar = e.id_ejemplar))'
                                );
                                $stmt->bind_param('iissiisii', $id_libro, $id_estado, $codigo, $signatura, $numero_copia, $referencia, $imagen, $id, $id_libro);
                            } else {
                                $stmt = $conexion->prepare(
                                    'INSERT INTO ejemplares_juanjo (id_libro, id_estado, codigo_barras, signatura, numero_copia, es_referencia, url_imagen)
                                     VALUES (?, ?, ?, ?, ?, ?, ?)'
                                );
                                $stmt->bind_param('iissiis', $id_libro, $id_estado, $codigo, $signatura, $numero_copia, $referencia, $imagen);
                            }
                            $stmt->execute();
                            if ($id && $stmt->affected_rows === 0) {
                                // An unchanged row is also reported as zero affected rows.
                                $comprobar = $conexion->prepare('SELECT id_libro FROM ejemplares_juanjo WHERE id_ejemplar = ?');
                                $comprobar->bind_param('i', $id);
                                $comprobar->execute();
                                $guardado = $comprobar->get_result()->fetch_assoc();
                                $comprobar->close();
                                if (!$guardado || (int) $guardado['id_libro'] !== $id_libro) {
                                    $error = 'No se pudo actualizar el ejemplar: no existe o tiene historial de préstamos.';
                                }
                            }
                            $stmt->close();
                            if ($error === '') {
                                $_SESSION['mensaje_ejemplares'] = $id ? 'Ejemplar actualizado.' : 'Ejemplar creado.';
                                header('Location: ejemplares.php');
                                exit;
                            }
                        } catch (mysqli_sql_exception $e) {
                            if ($e->getCode() === 1062) {
                                $error = 'Ya existe un ejemplar con ese código de barras.';
                            } elseif ($e->getCode() === 1452) {
                                $error = 'El libro o el estado seleccionado ya no existe.';
                            } else {
                                throw $e;
                            }
                        }
                    }
                }
            }
        }
    } elseif (isset($_GET['editar'])) {
        $id = enteroPositivoEjemplar($_GET['editar']);
        if ($id === false) {
            http_response_code(400);
            $error = 'El ejemplar indicado no es válido.';
            $mostrar_formulario = false;
        } else {
            $stmt = $conexion->prepare('SELECT id_ejemplar, id_libro, id_estado, codigo_barras, signatura, numero_copia, es_referencia, url_imagen FROM ejemplares_juanjo WHERE id_ejemplar = ?');
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $ejemplar = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            if ($ejemplar) {
                $formulario = $ejemplar;
            } else {
                http_response_code(404);
                $error = 'El ejemplar que intentas editar no existe.';
                $mostrar_formulario = false;
            }
        }
    }

    if ($mostrar_formulario) {
        $libros = $conexion->query('SELECT id_libro, titulo, fecha_baja FROM libros_manuel ORDER BY titulo, id_libro')->fetch_all(MYSQLI_ASSOC);
        $estados = $conexion->query('SELECT id_estado, nombre_estado FROM estados_ejemplar ORDER BY nombre_estado')->fetch_all(MYSQLI_ASSOC);
    } else {
        $stmt = $conexion->prepare(
            'SELECT e.*, l.titulo, l.isbn, s.nombre_estado,
                EXISTS(SELECT 1 FROM prestamos_juanjorpa p WHERE p.id_ejemplar = e.id_ejemplar
                       AND p.fecha_baja IS NULL AND p.fecha_devolucion IS NULL) AS prestado
             FROM ejemplares_juanjo e
             JOIN libros_manuel l ON l.id_libro = e.id_libro
             JOIN estados_ejemplar s ON s.id_estado = e.id_estado
             WHERE l.titulo LIKE ? OR e.codigo_barras LIKE ? OR e.signatura LIKE ? OR l.isbn LIKE ?
             ORDER BY e.id_ejemplar DESC'
        );
        $patron = '%' . $busqueda . '%';
        $stmt->bind_param('ssss', $patron, $patron, $patron, $patron);
        $stmt->execute();
        $ejemplares = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
    }
} catch (mysqli_sql_exception $e) {
    error_log('Error en ejemplares: ' . $e->getMessage());
    http_response_code(503);
    $datos_disponibles = false;
    $error = 'No se pudo acceder a los datos de ejemplares. Contacta con el administrador.';
}

include_once 'header.php';
?>
<main class="container-fluid py-4 overflow-auto">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 pb-2 mb-3 border-bottom">
        <div>
            <h1 class="h2">Mantenimiento de Ejemplares</h1>
            <p class="text-muted mb-0">Registro y control de las copias de la biblioteca.</p>
        </div>
        <?php if (!$mostrar_formulario): ?>
            <a href="ejemplares.php?nuevo=1" class="btn btn-success">+ Añadir ejemplar</a>
        <?php endif; ?>
    </div>

    <?php if ($mensaje !== ''): ?><div class="alert alert-success" role="status"><?= escaparEjemplar($mensaje) ?></div><?php endif; ?>
    <?php if ($error !== ''): ?><div class="alert alert-danger" role="alert"><?= escaparEjemplar($error) ?></div><?php endif; ?>

    <?php if ($datos_disponibles && $mostrar_formulario): ?>
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-white"><h2 class="h5 mb-0"><?= $formulario['id_ejemplar'] !== '' ? 'Editar ejemplar' : 'Nuevo ejemplar' ?></h2></div>
            <div class="card-body">
                <form method="post" action="ejemplares.php">
                    <input type="hidden" name="csrf_token" value="<?= escaparEjemplar($_SESSION['csrf_token']) ?>">
                    <input type="hidden" name="accion" value="guardar">
                    <input type="hidden" name="id_ejemplar" value="<?= escaparEjemplar($formulario['id_ejemplar']) ?>">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="id_libro" class="form-label">Libro *</label>
                            <select class="form-select" id="id_libro" name="id_libro" required>
                                <option value="">Selecciona un libro</option>
                                <?php foreach ($libros as $libro): ?>
                                    <?php if ($libro['fecha_baja'] === null || (string) $libro['id_libro'] === (string) $formulario['id_libro']): ?>
                                        <option value="<?= escaparEjemplar($libro['id_libro']) ?>" <?= (string) $libro['id_libro'] === (string) $formulario['id_libro'] ? 'selected' : '' ?>><?= escaparEjemplar($libro['titulo']) ?> (ID: <?= escaparEjemplar($libro['id_libro']) ?>)<?= $libro['fecha_baja'] !== null ? ' - De baja' : '' ?></option>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </select>
                            <div class="form-text">No se puede cambiar el libro si el ejemplar tiene historial de préstamos.</div>
                        </div>
                        <div class="col-md-6">
                            <label for="id_estado" class="form-label">Estado de conservación *</label>
                            <select class="form-select" id="id_estado" name="id_estado" required>
                                <option value="">Selecciona un estado</option>
                                <?php foreach ($estados as $estado): ?>
                                    <option value="<?= escaparEjemplar($estado['id_estado']) ?>" <?= (string) $estado['id_estado'] === (string) $formulario['id_estado'] ? 'selected' : '' ?>><?= escaparEjemplar($estado['nombre_estado']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label for="codigo_barras" class="form-label">Código de barras *</label>
                            <input class="form-control" id="codigo_barras" name="codigo_barras" maxlength="50" required value="<?= escaparEjemplar($formulario['codigo_barras']) ?>">
                        </div>
                        <div class="col-md-4">
                            <label for="signatura" class="form-label">Signatura</label>
                            <input class="form-control" id="signatura" name="signatura" maxlength="50" value="<?= escaparEjemplar($formulario['signatura']) ?>">
                        </div>
                        <div class="col-md-4">
                            <label for="numero_copia" class="form-label">Número de copia *</label>
                            <input type="number" class="form-control" id="numero_copia" name="numero_copia" min="1" max="2147483647" required value="<?= escaparEjemplar($formulario['numero_copia']) ?>">
                        </div>
                        <div class="col-md-8">
                            <label for="url_imagen" class="form-label">Ruta o URL de la imagen</label>
                            <input class="form-control" id="url_imagen" name="url_imagen" maxlength="255" value="<?= escaparEjemplar($formulario['url_imagen']) ?>">
                        </div>
                        <div class="col-md-4 d-flex align-items-end">
                            <div class="form-check mb-2">
                                <input class="form-check-input" type="checkbox" id="es_referencia" name="es_referencia" value="1" <?= (string) $formulario['es_referencia'] === '1' ? 'checked' : '' ?>>
                                <label class="form-check-label" for="es_referencia">Ejemplar de referencia</label>
                            </div>
                        </div>
                    </div>
                    <div class="mt-4 text-end">
                        <a href="ejemplares.php" class="btn btn-outline-secondary">Cancelar</a>
                        <button type="submit" class="btn btn-success">Guardar ejemplar</button>
                    </div>
                </form>
            </div>
        </div>
    <?php elseif ($datos_disponibles): ?>
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-white py-3">
                <form method="get" class="d-flex flex-wrap gap-2">
                    <label class="visually-hidden" for="busqueda">Buscar ejemplares</label>
                    <input class="form-control flex-grow-1 w-auto" id="busqueda" name="busqueda" placeholder="Título, ISBN, código o signatura" value="<?= escaparEjemplar($busqueda) ?>">
                    <button class="btn btn-primary" type="submit">Buscar</button>
                    <?php if ($busqueda !== ''): ?><a class="btn btn-outline-secondary" href="ejemplares.php">Limpiar</a><?php endif; ?>
                    <span class="badge bg-secondary align-self-center">Total: <?= count($ejemplares) ?></span>
                </form>
            </div>
            <div class="table-responsive">
                <table class="table table-striped table-hover align-middle mb-0">
                    <thead class="table-light"><tr><th>ID</th><th>Libro</th><th>Código</th><th>Signatura</th><th>Copia</th><th>Conservación</th><th>Referencia</th><th>Situación</th><th>Acciones</th></tr></thead>
                    <tbody>
                    <?php foreach ($ejemplares as $ejemplar): ?>
                        <tr>
                            <td><?= escaparEjemplar($ejemplar['id_ejemplar']) ?></td>
                            <td><?= escaparEjemplar($ejemplar['titulo']) ?><br><small class="text-muted"><?= escaparEjemplar($ejemplar['isbn']) ?></small></td>
                            <td><?= escaparEjemplar($ejemplar['codigo_barras']) ?></td>
                            <td><?= escaparEjemplar($ejemplar['signatura']) ?></td>
                            <td><?= escaparEjemplar($ejemplar['numero_copia']) ?></td>
                            <td><?= escaparEjemplar($ejemplar['nombre_estado']) ?></td>
                            <td><?= $ejemplar['es_referencia'] ? 'Sí' : 'No' ?></td>
                            <td>
                                <?php if ($ejemplar['fecha_baja'] !== null): ?><span class="badge bg-secondary">De baja</span>
                                <?php elseif ($ejemplar['prestado']): ?><span class="badge bg-warning text-dark">Prestado</span>
                                <?php else: ?><span class="badge bg-success">Activo</span><?php endif; ?>
                            </td>
                            <td>
                                <div class="d-flex gap-1">
                                    <a class="btn btn-sm btn-outline-primary" href="ejemplares.php?editar=<?= escaparEjemplar($ejemplar['id_ejemplar']) ?>">Editar</a>
                                    <button class="btn btn-sm <?= $ejemplar['fecha_baja'] !== null ? 'btn-outline-success' : 'btn-outline-danger' ?>" type="button"
                                        data-bs-toggle="modal" data-bs-target="#modalEstadoEjemplar"
                                        data-id="<?= escaparEjemplar($ejemplar['id_ejemplar']) ?>"
                                        data-titulo="<?= escaparEjemplar($ejemplar['titulo']) ?>"
                                        data-codigo="<?= escaparEjemplar($ejemplar['codigo_barras']) ?>"
                                        data-accion="<?= $ejemplar['fecha_baja'] !== null ? 'alta' : 'baja' ?>"
                                        <?= $ejemplar['fecha_baja'] === null && $ejemplar['prestado'] ? 'disabled title="Tiene un préstamo pendiente"' : '' ?>><?= $ejemplar['fecha_baja'] !== null ? 'Reactivar' : 'Dar de baja' ?></button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (!$ejemplares): ?><tr><td colspan="9" class="text-center text-muted py-4">No se encontraron ejemplares.</td></tr><?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <div class="modal fade" id="modalEstadoEjemplar" tabindex="-1" aria-labelledby="modalEstadoEjemplarLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content shadow-sm">
                    <div class="modal-header bg-dark text-white">
                        <h2 class="modal-title fs-5" id="modalEstadoEjemplarLabel"></h2>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                    </div>
                    <form method="post" action="ejemplares.php">
                        <div class="modal-body">
                            <input type="hidden" name="csrf_token" value="<?= escaparEjemplar($_SESSION['csrf_token']) ?>">
                            <input type="hidden" name="id_ejemplar" id="estadoIdEjemplar">
                            <input type="hidden" name="accion" id="estadoAccionEjemplar">
                            <p id="estadoConfirmacionEjemplar"></p>
                            <p class="mb-0"><strong id="estadoTituloEjemplar"></strong><br><span class="text-muted" id="estadoCodigoEjemplar"></span></p>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                            <button type="submit" class="btn" id="estadoConfirmarEjemplar"></button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <script>
            document.getElementById('modalEstadoEjemplar').addEventListener('show.bs.modal', function (event) {
                const boton = event.relatedTarget;
                const esAlta = boton.dataset.accion === 'alta';
                const confirmar = document.getElementById('estadoConfirmarEjemplar');
                document.getElementById('estadoIdEjemplar').value = boton.dataset.id;
                document.getElementById('estadoAccionEjemplar').value = boton.dataset.accion;
                document.getElementById('estadoTituloEjemplar').textContent = boton.dataset.titulo;
                document.getElementById('estadoCodigoEjemplar').textContent = 'Código: ' + boton.dataset.codigo;
                document.getElementById('modalEstadoEjemplarLabel').textContent = esAlta ? 'Reactivar ejemplar' : 'Dar de baja ejemplar';
                document.getElementById('estadoConfirmacionEjemplar').textContent = esAlta
                    ? '¿Confirmas que deseas reactivar este ejemplar?'
                    : '¿Confirmas que deseas dar de baja este ejemplar?';
                confirmar.textContent = esAlta ? 'Reactivar' : 'Dar de baja';
                confirmar.classList.toggle('btn-success', esAlta);
                confirmar.classList.toggle('btn-danger', !esAlta);
            });
        </script>
    <?php endif; ?>
</main>
<?php include_once 'footer.php'; ?>
