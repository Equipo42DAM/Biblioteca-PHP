<?php
$seccion_activa = 'editoriales';
include_once('header.php');
?>

<div class="container">
    <h1>Opciones de editorial</h1>

    <ul class="list-group d-grid gap-2 col-12 col-sm-6 col-md-4 mt-3">
        <li class="list-group-item border rounded">
            <a href="altaEditorial.php" class="text-decoration-none fs-5 d-block">Añadir editorial</a>
        </li>
        <li class="list-group-item border rounded">
            <a href="bajaEditorial.php" class="text-decoration-none fs-5 d-block">Borrar editorial</a>
        </li>
        <li class="list-group-item border rounded">
            <a href="listarEditoriales.php" class="text-decoration-none fs-5 d-block">Listar editoriales</a>
        </li>
        <li class="list-group-item border rounded">
            <a href="modificarEditorial.php" class="text-decoration-none fs-5 d-block">Modificar editoriales</a>
        </li>
    </ul>
</div>

<?php
include_once('footer.php');
?>