<?php
$toast_config = null;

if (isset($_GET['status'])) {
    $id_ref = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    $texto_id = $id_ref > 0 ? " #{$id_ref}" : "";

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
                'titulo'  => 'Préstamo Desactivado',
                'mensaje' => "El préstamo{$texto_id} se ha dado de baja correctamente."
            ];
            break;

        case 'reactivated':
            $toast_config = [
                'icon'    => 'bi-arrow-counterclockwise text-success',
                'titulo'  => 'Préstamo Reactivado',
                'mensaje' => "El préstamo{$texto_id} se ha vuelto a dar de alta."
            ];
            break;

        case 'notFound':
            $toast_config = [
                'icon'    => 'bi-question-circle-fill text-warning',
                'titulo'  => 'Préstamo no encontrado',
                'mensaje' => "No se ha encontrado el préstamo{$texto_id} o no está activo."
            ];
            break;

        case 'createError':
            $toast_config = [
                'icon'    => 'bi-exclamation-circle-fill text-danger',
                'titulo'  => 'Error al registrar',
                'mensaje' => "No se pudo guardar el nuevo préstamo. Comprueba que el ejemplar y el socio sean válidos."
            ];
            break;

        case 'updateError':
            $toast_config = [
                'icon'    => 'bi-exclamation-circle-fill text-danger',
                'titulo'  => 'Error al actualizar',
                'mensaje' => "No se pudieron guardar los cambios en el préstamo{$texto_id}."
            ];
            break;

        case 'fileTooLarge':
            $toast_config = [
                'icon'    => 'bi-file-earmark-x-fill text-warning',
                'titulo'  => 'Imagen demasiado pesada',
                'mensaje' => "La imagen supera el tamaño máximo permitido de 5 MB."
            ];
            break;

        case 'invalidImage':
            $toast_config = [
                'icon'    => 'bi-file-earmark-image text-warning',
                'titulo'  => 'Formato no válido',
                'mensaje' => "La imagen debe estar en formato JPG, PNG o WEBP."
            ];
            break;

        case 'invalidDates':
            $toast_config = [
                'icon'    => 'bi-calendar-x-fill text-warning',
                'titulo'  => 'Fechas no válidas',
                'mensaje' => "La fecha límite y la de devolución no pueden ser anteriores a la fecha de préstamo."
            ];
            break;
            
        case 'returned':
            $toast_config = [
                'icon'    => 'bi-bookmark-check-fill text-info',
                'titulo'  => 'Préstamo Entregado',
                'mensaje' => "Se ha registrado la devolución del préstamo{$texto_id}."
            ];
            break;

        case 'sinStockLibro':
            $toast_config = [
                'icon'    => 'bi-exclamation-triangle-fill text-warning',
                'titulo'  => 'Sin ejemplares disponibles',
                'mensaje' => "Todos los ejemplares de este libro ya están prestados actualmente."
            ];
            break;

        case 'internalError':
            $toast_config = [
                'icon'    => 'bi-shield-exclamation text-danger',
                'titulo'  => 'Acción no permitida',
                'mensaje' => "Error de validación de seguridad. Se ha cancelado la operación."
            ];
            break;
    }
}
?>

<?php if ($toast_config): ?>
    <div class="toast-container position-fixed top-0 end-0 p-3" style="z-index: 1080;">
        <div class="toast border-0 shadow-sm"
            role="alert"
            aria-live="assertive"
            aria-atomic="true"
            data-bs-autohide="true"
            data-bs-delay="4000">

            <div class="toast-header bg-dark text-white">
                <i class="bi <?= $toast_config['icon'] ?> me-2"></i>
                <strong class="me-auto"><?= $toast_config['titulo'] ?></strong>
                <small class="text-white-50">Ahora mismo</small>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>

            <div class="toast-body bg-white text-dark">
                <?= $toast_config['mensaje'] ?>
            </div>
        </div>
    </div>
<?php endif; ?>