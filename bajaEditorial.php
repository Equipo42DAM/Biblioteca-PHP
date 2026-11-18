<?php
include_once('header.php');
?>
<div>
    <h4>Procesar el alta de editorial</h4>

    // Formulario que envía todos los datos a esta misma página, donde se procesarán
    <form name="frmBajaEditorial" id="frmBajaEditorial" action="bajaEditorial.php" method="POST" enctype="multipart/form-data">
        <legend>Baja de editorial</legend>
        <div class="col-md-6 mb-3">
            <label for="txtNombre" class="form-label">Nombre</label>
            <input type="text" class="form-control" id="txtNombre" name="txtNombre" placeholder="Introduzca el nombre de la editorial" maxlength="45" size="50" />
        </div>

        <div class="col-md-6 mb-3">
            <label for="txtContacto" class="form-label">Contacto</label>
            <input type="text" class="form-control" id="txtContacto" name="txtContacto" placeholder="Introduzca el nombre del contacto" maxlength="45" size="50" />
        </div>

        <div class="col-md-6 mb-3">
            <label for="txtPais" class="form-label">Pais</label>
            <input type="text" class="form-control" id="txtPais" name="txtPais" placeholder="Introduzca el país de la editorial" maxlength="45" size="50" />
        </div>
    </form>

    <?php

    $conexion = getMySQLiConnection();

    if (!$conexion) {
        die("<p>Error de conexión a la base de datos.</p>");
    }

    $id = (int)$_POST['id_editorial'];
    $fecha_actual = date('Y-m-d H:i:s');

    // La consulta tiene 2 comodines (?)
    $sql = "UPDATE editorial_carlos SET fecha_baja = ? WHERE id_editorial = ? AND fecha_baja IS NULL";

    $actualizado = false;

    if ($stmt = $conexion->prepare($sql)) {

        // S de String para la fecha e I para int del id
        $stmt->bind_param("si", $fecha_actual, $id);

        if ($stmt->execute()) {
            // Verificación de actualizació del registro
            if ($stmt->affected_rows > 0) {
                $actualizado = true;
            }
        }

        $stmt->close();
    }

    // Usamos actualizado para un mensaje
    if ($actualizado) {
        echo "Editorial dada de baja correctamente.";
    } else {
        echo "No se pudo realizar la baja (el ID no existe o ya estaba dado de baja).";
    }
    ?>
</div>

<?php
include_once('footer.php');
?>