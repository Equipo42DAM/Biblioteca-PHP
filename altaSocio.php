<!DOCTYPE html>
<html lang="en">
    <?php
        $seccion_activa = 'socios';
        include_once('config.php');
        include_once('header.php');
    ?>
    <div class="container my-4">
        <h4 class="h3 mb-3">Alta de socio</h4>
    
        <!--El formulario envía los datos -->
        <form name="frmAltaSocio" id="frmAltaSocio" action="altaSocio.php" method="POST" class="row g-3">
    
            <div class="col-md-4">
                <label for="txtDni" class="form-label">DNI / NIE</label>
                <input type="text" class="form-control" id="txtDni" name="txtDni" maxlength="9" placeholder="12345678Z" required>
            </div>
            
            <div class="col-md-4">
                <label for="txtNombre" class="form-label">Nombre</label>
                <input type="text" class="form-control" id="txtNombre" name="txtNombre" maxlength="50" required>
            </div>
           
            <div class="col-md-4">
                <label for="txtApellidos" class="form-label">Apellidos</label>
                <input type="text" class="form-control" id="txtApellidos" name="txtApellidos" maxlength="100" required>
            </div>
        
            <div class="col-md-6">
                <label for="txtEmail" class="form-label">Email</label>
                <input type="email" class="form-control" id="txtEmail" name="txtEmail" maxlength="100" required>
            </div>
    
            <div class="col-md-2">
                <label for="numEdad" class="form-label">Edad</label>
                <input type="number" class="form-control" id="numEdad" name="numEdad" min="0" max="120" required>
            </div>
    
            <div class="col-md-4">
                <label for="dateAlta" class="form-label">Fecha de alta</label>
                <input type="date" class="form-control" id="dateAlta" name="dateAlta" value="<?php echo date('Y-m-d'); ?>" required>
            </div>
    
        </div>
</html>