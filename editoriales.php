<?php
$seccion_activa = 'editoriales';
include_once('header.php');
?>

<div class="container">
    <h1>Opciones de editorial</h1>
    <ul class="list-group list-unstyled p-3">
        <li class="list-group-item border-1"> <a href="altaEditorial.php">Añadir editorial</a> </li>
        <li class="list-group-item border-1"> <a href="bajaEditorial.php">Borrar editorial</a> </li>
        <li class="list-group-item border-1"> <a href="listarEditoriales.php">Listar editoriales</a> </li>
        <li class="list-group-item border-1"> <a href="modificarEditorial.php">Modificar editoriales</a> </li>
    </ul>
</div>

<?php
include_once('footer.php');
?>