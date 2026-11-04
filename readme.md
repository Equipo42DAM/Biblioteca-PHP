# 📚 Biblioteca Ohara - Sistema de Gestión CRUD

Bienvenido al repositorio del proyecto **Biblioteca Ohara**. Este sistema es una aplicación web desarrollada con una arquitectura modular en **PHP nativo** y **Bootstrap 5**, respaldada por un entorno **LAMP** (Linux, Apache, MySQL, PHP) gestionado mediante **Docker Compose** con una estructura de archivos limpia en la raíz.

---

## 🚀 Requisitos Previos

Antes de comenzar, asegúrate de tener instalado en tu equipo:
* [Docker](https://www.docker.com/)
* [Docker Compose](https://docs.docker.com/compose/) (incluido de serie en Docker Desktop)

---

## 📂 Estructura del Proyecto y Base de Datos

* **`/docs/import.sql`** -> Contiene el script SQL con la estructura completa de las tablas (`editoriales`, `libros`, `socios`, `ejemplares`, `prestamos`), sus relaciones 1:N con restricciones de integridad y datos de prueba iniciales.
* **Automatización:** No es necesario realizar ninguna importación manual en phpMyAdmin. El contenedor de base de datos está configurado para leer y ejecutar automáticamente el archivo `docs/import.sql` durante su primer arranque.

---

## ⚙️ Instrucciones de Instalación y Ejecución

Sigue estos sencillos pasos para poner en marcha el entorno de desarrollo local:

### 1. Clonar o descargar el repositorio
Abre una terminal en la carpeta raíz de tu proyecto donde se encuentran todos los archivos del código y el archivo `docker-compose.yml`.

### 2. Arrancar los contenedores con Docker
Ejecuta el siguiente comando en tu terminal para construir y levantar los servicios en segundo plano:

```bash
docker compose up -d --build