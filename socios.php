<?php
    
    $seccion_activa = 'socios';
    include_once('header.php');
?>
<main>
    <div class="container my-4">
        <h1 class="h3 mb-3">Gestión de socios</h1>
        <div class="list-group">
            <a href="altaSocio.php" class="list-group-item list-group-item-action">
                <i class="bi bi-person-plus me-2"></i>Añadir socio
            </a>
            <a href="consultarSocio.php" class="list-group-item list-group-item-action">
                <i class="bi bi-search me-2"></i>Consultar socio
            </a>
            <a href="bajaSocio.php" class="list-group-item list-group-item-action">
                <i class="bi bi-person-dash me-2"></i>Dar de baja un socio
            </a>
            <a href="listarSocios.php" class="list-group-item list-group-item-action">
                <i class="bi bi-list-ul me-2"></i>Listar todos los socios
            </a>
            <a href="filtrarSocios.php" class="list-group-item list-group-item-action">
                <i class="bi bi-funnel me-2"></i>Listar socios con filtros
            </a>
        </div>
    </div>
</main>
<?php
    include_once('footer.php');
?>