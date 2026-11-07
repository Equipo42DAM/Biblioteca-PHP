# 📚 Biblioteca Ohara - Sistema de Gestión CRUD

Bienvenido al repositorio del proyecto **Biblioteca Ohara**. Este sistema es una aplicación web desarrollada con una arquitectura modular en **PHP nativo** y **Bootstrap 5**, respaldada por un entorno **LAMP** (Linux, Apache, MySQL, PHP) gestionado mediante **Docker Compose**.

## 🔗 Enlaces del Proyecto

* **Repositorio en GitHub:** <https://github.com/tu-usuario/biblioteca-ohara>
* **Despliegue / Alojamiento Web:** <https://tu-alojamiento-web.com>

---

## 👥 Equipo de Desarrollo y Entidades Asignadas

| Alumno | Entidad / Tabla Responsable | Descripción de la Responsabilidad |
| :--- | :--- | :--- |
| **Carlos** | `editorial_carlos` | Gestión de casas editoras, datos de contacto y origen |
| **Manuel** | `libros_manuel` | Ficha catalográfica de obras e ISBN |
| **Ricardo** | `socios_ricardo` | Control de usuarios/socios de la biblioteca y VIPs |
| **Juanjo** | `ejemplares_juanjo` | Control físico de copias, signaturas y código de barras |
| **Juanjo R. P.** | `prestamos_juanjorpa` | Gestión de transacciones de préstamos y fianzas |

> *Tablas maestras y auxiliares del sistema (`paises`, `estados_ejemplar`, `usuarios`) administradas conjuntamente.*

---

## 🔑 Datos de Conexión a la Base de Datos

* **Nombre de la Base de Datos:** `biblioteca_ohara`
* **Usuario BD:** `admin`
* **Contraseña BD:** `admin`
* **Servidor / Host (Docker):** `db` (usar `localhost` o `127.0.0.1` en entornos locales sin Docker)
* **Gestor web (phpMyAdmin):** <http://localhost:8081>

### 👤 Cuentas de Acceso a la Aplicación Web

* **Administrador:** `admin` / `admin123`
* **Personal de Gestión (Usuario):** `usuario1` / `user123`

---

## 📂 Estructura del Proyecto y Arquitectura

```text
nombreProyecto/
├── docs/                     # Documentación técnica y especificaciones en PDF
├── img/                      # Recortes e imágenes de assets locales
├── sql/                      # Scripts y recursos de la base de datos
│   ├── backup.sql            # Estructura completa de las 8 tablas y seed data
│   └── imagen_diagrama_E-R   # Captura gráfica del modelo entidad-relación (phpMyAdmin)
├── config.php                # Conexión global a la base de datos (función getPDOConnection)
├── header.php                # Cabecera HTML común (head, CSS Bootstrap, barra de navegación)
├── footer.php                # Cierre HTML (scripts JS Bootstrap, pie de página)
├── index.php                 # Página principal y portada del sitio web
├── resto_páginas.php         # Módulos y vistas CRUD de la aplicación (ejemplares.php, etc.)
├── docker-compose.yml        # Orquestación del servidor web y base de datos
└── README.md                 # Documentación global del proyecto
```

### 🧩 Arquitectura Modular (Includes & Standard Views)

Para garantizar un código mantenible y ordenado, todas las vistas principales de la aplicación utilizan la siguiente estructura modular estándar:

```php
<?php 
// 1. Cabecera global (Carga <head>, estilos CSS y Navbar)
include 'header.php'; 
?>

<!-- 2. Contenido principal de la página -->
<main class="container my-4">
    <!-- Código específico de la vista (Tablas CRUD, Formularios, Consultas SQL) -->
</main>

<?php 
// 3. Pie de página global (Carga scripts JS y cierra </body>/</html>)
include 'footer.php'; 
?>
```

---

## 🗄️ Esquema de Base de Datos (`sql/backup.sql`)

El sistema utiliza **8 tablas** totalmente normalizadas:

1. **`paises`**: Tabla maestra de origen de las editoriales.
2. **`editorial_carlos`**: Registro de casas editoras.
3. **`libros_manuel`**: Catálogo general de libros.
4. **`socios_ricardo`**: Registro de socios y carnés activos.
5. **`estados_ejemplar`**: Estados de conservación (Nuevo, Bueno, Deteriorado...).
6. **`ejemplares_juanjo`**: Copias físicas en estantería.
7. **`prestamos_juanjorpa`**: Registro transaccional de préstamos y fianzas.
8. **`usuarios`**: Gestión de cuentas del sistema y roles (`admin`, `usuario`).

---

## ⚙️ Instrucciones de Instalación y Ejecución

1. **Clonar o descomprimir** el proyecto en el equipo.
2. **Levantar el entorno con Docker:**

   ```bash
   docker compose up -d --build
   ```

3. **Acceder a los servicios:**
   * **Aplicación Web:** <http://localhost:8080>
   * **phpMyAdmin:** <http://localhost:8081>

---

## 🛠️ Comandos Útiles para el Desarrollo

* **Detener los servicios:**
  ```bash
  docker compose down
  ```

* **Reiniciar y recargar la base de datos limpia desde `sql/backup.sql`:**
  ```bash
  docker compose down -v
  docker compose up -d
  ```

* **Ver logs del servidor web y BD en tiempo real:**
  ```bash
  docker compose logs -f
  ```

---

## 🐙 Guía de Git: Trabajo con Ramas y Convención de Commits

### 📌 ¿Es necesario poner tu nombre en los mensajes de commit?
No es obligatorio incluir el nombre en el texto del mensaje, ya que Git firma automáticamente el autor con tu configuración global (`git config user.name`). Sin embargo, si deseas especificar la entidad o módulo modificado en proyectos de grupo, puedes indicar el ámbito entre paréntesis, por ejemplo: `feat(socios): añadir validación de DNI`.

---

### 🏷️ Convención de Commits (*Conventional Commits*)

* **`feat:`** Nuevas funcionalidades (ej. `feat: agregar vista de préstamos activos`).
* **`fix:`** Corrección de fallos o bugs (ej. `fix: corregir consulta SQL en config.php`).
* **`chore:`** Tareas de configuración o infraestructura (ej. `chore: actualizar docker-compose.yml`).
* **`docs:`** Cambios exclusivamente en la documentación (ej. `docs: actualizar el README.md`).
* **`style:`** Formato visual o retoques CSS sin cambio de lógica (ej. `style: ajustar márgenes en el footer`).
* **`refactor:`** Reestructuración de código sin alterar su comportamiento actual.

---

### 🌿 Flujo de Trabajo con Ramas (Paso a Paso)

Para evitar trabajar directamente sobre la rama principal (`main`), seguimos el flujo de trabajo basado en ramas de características (*feature branches*):

#### 1. Crear una nueva rama para una tarea
Antes de empezar a programar una nueva funcionalidad o corrección, crea y entra en tu nueva rama:
```bash
# Crear la rama y cambiar a ella
git checkout -b feature/nombre-de-tu-rama

# Ejemplo:
git checkout -b feature/modulo-socios
```

#### 2. Desarrollar y guardar commits en tu rama
Trabaja normalmente en tus archivos PHP, añade tus cambios al área de preparación (*staging*) y realiza los commits correspondientes:
```bash
# Ver estado de los archivos
git status

# Preparar los archivos
git add .

# Confirmar los cambios con un mensaje claro
git commit -m "feat(socios): añadir formulario para nuevos socios"
```

#### 3. Subir la rama a GitHub (opcional / copia de seguridad)
Si deseas publicar tu rama de trabajo en la nube:
```bash
git push -u origin feature/nombre-de-tu-rama
```

#### 4. Fusionar la rama con `main` al terminar
Una vez que hayas probado tus cambios y el módulo funcione correctamente, fusiona tu trabajo con la rama principal:

```bash
# a. Cambiar a la rama principal
git checkout main

# b. Traer los últimos cambios remotos por si algún compañero actualizó main
git pull origin main

# c. Fusionar tu rama dentro de main
git merge feature/nombre-de-tu-rama

# d. Subir el resultado fusionado a GitHub
git push origin main
```

#### 5. Limpieza local (opcional)
Una vez fusionada la rama en `main`, puedes eliminar la rama secundaria para mantener limpio tu entorno:
```bash
git branch -d feature/nombre-de-tu-rama
```