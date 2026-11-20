<?php
require_once 'config.php';
include_once('header.php');
?>

<div class="container my-4">
    <h4>Baja de editorial</h4>

    <?php
    $conexion = getMySQLiConnection();

    if (!$conexion) {
        die("<p>Error al conectar con la base de datos.</p>");
    }

    $id = (int)($_GET['id_editorial'] ?? 0);
    $fecha_actual = date('Y-m-d H:i:s');

    // La consulta tiene 2 entradas
    $sql = "UPDATE editorial_carlos SET fecha_baja = ? WHERE id_editorial = ? AND fecha_baja IS NULL";

    $actualizado = false;
    $error = ''; // Guarda el error de MySQL si lo hay

    try { // Si MySQL lanza una excepción, se muestra un aviso en vez de un error fatal
        if ($stmt = $conexion->prepare($sql)) {

            // S de String para la fecha e I para int del id
            $stmt->bind_param("si", $fecha_actual, $id);

            if ($stmt->execute()) {
                // Verificación de actualización del registro
                if ($stmt->affected_rows > 0) {
                    $actualizado = true;
                }
            }

            $stmt->close();
        }
    } catch (Exception $e) {
        $error = $e->getMessage();
    }

    mysqli_close($conexion); // CAMBIO: cerramos la conexión

    // Usamos actualizado para un mensaje
    if ($actualizado) {
        echo "<div class='alert alert-success'>Editorial dada de baja correctamente.</div>";
    } else {
        echo "<div class='alert alert-warning'>No se pudo realizar la baja (el ID no existe o ya estaba dado de baja).</div>";
        // Si fue un error de base de datos, lo mostramos
        if ($error !== '') {
            echo "<div class='alert alert-danger'>Error: " . htmlspecialchars($error) . "</div>";
        }
    }
    ?>

    <a href="editoriales.php" class="btn btn-secondary">Volver al listado</a>
</div>

<?php
include_once('footer.php');
?>