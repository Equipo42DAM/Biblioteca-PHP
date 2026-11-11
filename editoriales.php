<?php
$seccion_activa = 'editoriales';
include_once('header.php');
?>

<div class="container">
    <h1>Opciones de editorial</h1>
    <ul class="list-group">
        <li class="list-group-item"> <a href="altaEditorial.php">Añadir editorial</a> </li>
        <li class="list-group-item"> <a href="bajaEditorial.php">Borrar editorial</a> </li>
        <li class="list-group-item"> <a href="listarEditoriales.php">Listar editoriales</a> </li>
        <li class="list-group-item"> <a href="modificarEditorial.php">Modificar editoriales</a> </li>
    </ul>
</div>

<?php
include_once('footer.php');
?>