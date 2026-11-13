<?php
  require_once 'auth.php';
  requireLogin();

  // Si la página no define una sección activa, por defecto es 'inicio'
  if (!isset($seccion_activa)) {
      $seccion_activa = 'inicio';
  }

  // Asignar el nombre del responsable de forma sencilla según la sección
  switch ($seccion_activa) {
      case 'editoriales': $responsable = 'Carlos García'; break;
      case 'libros':      $responsable = 'Manuel Orozco'; break;
      case 'ejemplares':  $responsable = 'Juan José Lobo'; break;
      case 'socios':      $responsable = 'Ricardo Daza'; break;
      case 'prestamos':   $responsable = 'Juan José Rodríguez-Prat'; break;
      default:            $responsable = 'Grupo 4'; break; // Para el inicio
  }
?>
<!DOCTYPE html>
<html lang="es" class="h-100">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Panel de Administración - Biblioteca Ohara</title>
  <!-- Bootstrap 5 CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <!-- Bootstrap Icons -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
</head>
<body class="d-flex flex-column vh-100 bg-light m-0 overflow-hidden">

  <!-- Barra de navegación superior para móviles -->
  <div class="d-md-none bg-dark text-white p-2 d-flex align-items-center overflow-auto shadow-sm flex-shrink-0">
    <a href="index.php" class="text-white text-decoration-none me-3 d-flex align-items-center flex-shrink-0 ps-2">
      <i class="bi bi-book-half text-success fs-6 me-1"></i>
      <span class="fw-bold text-nowrap fs-6">Biblioteca Ohara</span>
    </a>
    <ul class="nav nav-pills flex-row flex-nowrap gap-1">
      <li class="nav-item">
        <a class="nav-link py-1 px-2 fs-6 <?= ($seccion_activa == 'inicio') ? 'active bg-success text-white' : 'text-white' ?>" href="index.php" title="Inicio"><i class="bi bi-speedometer2"></i></a>
      </li>
      <li class="nav-item">
        <a class="nav-link py-1 px-2 fs-6 <?= ($seccion_activa == 'editoriales') ? 'active bg-success text-white' : 'text-white' ?>" href="editoriales.php" title="Editoriales"><i class="bi bi-building"></i></a>
      </li>
      <li class="nav-item">
        <a class="nav-link py-1 px-2 fs-6 <?= ($seccion_activa == 'libros') ? 'active bg-success text-white' : 'text-white' ?>" href="libros.php" title="Libros"><i class="bi bi-journal-bookmark"></i></a>
      </li>
      <li class="nav-item">
        <a class="nav-link py-1 px-2 fs-6 <?= ($seccion_activa == 'ejemplares') ? 'active bg-success text-white' : 'text-white' ?>" href="ejemplares.php" title="Ejemplares"><i class="bi bi-qr-code-scan"></i></a>
      </li>
      <li class="nav-item">
        <a class="nav-link py-1 px-2 fs-6 <?= ($seccion_activa == 'socios') ? 'active bg-success text-white' : 'text-white' ?>" href="socios.php" title="Socios"><i class="bi bi-people"></i></a>
      </li>
      <li class="nav-item">
        <a class="nav-link py-1 px-2 fs-6 <?= ($seccion_activa == 'prestamos') ? 'active bg-success text-white' : 'text-white' ?>" href="prestamos.php" title="Préstamos"><i class="bi bi-arrow-left-right"></i></a>
      </li>
    </ul>
    <form action="logout.php" method="post" class="ms-auto flex-shrink-0 ps-2">
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">
      <button type="submit" class="btn btn-outline-light btn-sm text-nowrap">
        <i class="bi bi-box-arrow-right me-1" aria-hidden="true"></i>Salir
      </button>
    </form>
  </div>

  <!-- Contenedor principal -->
  <div class="d-flex flex-grow-1 vh-100 w-100 m-0 p-0 overflow-hidden">

    <!-- Panel de Navegación Lateral / Sidebar -->
    <aside class="bg-dark text-white p-3 d-none d-md-flex flex-column vh-100 overflow-auto flex-shrink-0" style="width: 260px;">
      
      <!-- Logotipo / Título -->
      <a href="index.php" class="d-flex align-items-center mb-4 text-white text-decoration-none border-bottom border-secondary pb-3 flex-shrink-0">
        <i class="bi bi-book-half fs-5 text-success me-2"></i>
        <span class="fw-bold text-nowrap fs-6">Biblioteca Ohara</span>
      </a>

      <!-- Menú de Entidades del CRUD (Explícito) -->
      <ul class="nav nav-pills flex-column mb-auto">
        <li class="nav-item mb-1">
          <a href="index.php" class="nav-link text-nowrap fs-6 <?= ($seccion_activa == 'inicio') ? 'active bg-success text-white' : 'text-white' ?>">
            <i class="bi bi-speedometer2 me-2"></i>
            <span>Inicio</span>
          </a>
        </li>
        <li class="nav-item mb-1">
          <a href="editoriales.php" class="nav-link text-nowrap fs-6 <?= ($seccion_activa == 'editoriales') ? 'active bg-success text-white' : 'text-white' ?>">
            <i class="bi bi-building me-2"></i>
            <span>Editoriales</span>
          </a>
        </li>
        <li class="nav-item mb-1">
          <a href="libros.php" class="nav-link text-nowrap fs-6 <?= ($seccion_activa == 'libros') ? 'active bg-success text-white' : 'text-white' ?>">
            <i class="bi bi-journal-bookmark me-2"></i>
            <span>Libros</span>
          </a>
        </li>
        <li class="nav-item mb-1">
          <a href="ejemplares.php" class="nav-link text-nowrap fs-6 <?= ($seccion_activa == 'ejemplares') ? 'active bg-success text-white' : 'text-white' ?>">
            <i class="bi bi-qr-code-scan me-2"></i>
            <span>Ejemplares</span>
          </a>
        </li>
        <li class="nav-item mb-1">
          <a href="socios.php" class="nav-link text-nowrap fs-6 <?= ($seccion_activa == 'socios') ? 'active bg-success text-white' : 'text-white' ?>">
            <i class="bi bi-people me-2"></i>
            <span>Socios</span>
          </a>
        </li>
        <li class="nav-item mb-1">
          <a href="prestamos.php" class="nav-link text-nowrap fs-6 <?= ($seccion_activa == 'prestamos') ? 'active bg-success text-white' : 'text-white' ?>">
            <i class="bi bi-arrow-left-right me-2"></i>
            <span>Préstamos</span>
          </a>
        </li>
      </ul>

      <!-- Bloque del Responsable (Cambia según la sección) -->
      <div class="pt-3 border-top border-secondary flex-shrink-0">
        <div class="d-flex align-items-center mb-1">
          <i class="bi bi-person-badge-fill text-success fs-6 me-2"></i>
          <span class="small fw-bold text-uppercase text-nowrap">Responsable</span>
        </div>
        <div class="small text-white text-nowrap ps-4">
          <?= $responsable ?>
        </div>
      </div>
      <div class="pt-3 mt-3 border-top border-secondary flex-shrink-0">
        <div class="small text-white-50 mb-2">Sesión iniciada</div>
        <div class="small text-white text-break mb-3">
          <i class="bi bi-person-circle text-success me-2" aria-hidden="true"></i><?= htmlspecialchars($_SESSION['usuario']['nombre_completo'], ENT_QUOTES, 'UTF-8') ?>
        </div>
        <form action="logout.php" method="post">
          <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">
          <button type="submit" class="btn btn-outline-light btn-sm w-100">
            <i class="bi bi-box-arrow-right me-2" aria-hidden="true"></i>Cerrar sesión
          </button>
        </form>
      </div>

    </aside>