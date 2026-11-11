-- Creacion de la base de datos
CREATE DATABASE IF NOT EXISTS biblioteca_ohara CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE biblioteca_ohara;

-- Desactivar temporalmente las restricciones de claves foraneas para limpiar si ya existian
SET FOREIGN_KEY_CHECKS = 0;

-- Eliminar tablas en orden inverso para evitar conflictos de dependencias
DROP TABLE IF EXISTS prestamos_juanjorpa;
DROP TABLE IF EXISTS ejemplares_juanjo;
DROP TABLE IF EXISTS estados_ejemplar;
DROP TABLE IF EXISTS libros_manuel;
DROP TABLE IF EXISTS socios_ricardo;
DROP TABLE IF EXISTS editorial_carlos;
DROP TABLE IF EXISTS paises;
DROP TABLE IF EXISTS usuarios;

-- 1. Tabla PAISES
CREATE TABLE paises (
    id_pais INT AUTO_INCREMENT PRIMARY KEY,
    nombre_pais VARCHAR(100) NOT NULL UNIQUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Tabla EDITORIAL_CARLOS
CREATE TABLE editorial_carlos (
    id_editorial INT AUTO_INCREMENT PRIMARY KEY,
    id_pais INT NOT NULL,
    nombre_comercial VARCHAR(100) NOT NULL,
    contacto VARCHAR(100) DEFAULT NULL,
    url_imagen VARCHAR(255) DEFAULT NULL,
    edita_espanol BOOLEAN DEFAULT TRUE,
    anio_origen INT DEFAULT NULL,
    fecha_baja DATETIME DEFAULT NULL,
    CONSTRAINT fk_editorial_pais FOREIGN KEY (id_pais) 
        REFERENCES paises(id_pais) 
        ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Tabla LIBROS_MANUEL
CREATE TABLE libros_manuel (
    id_libro INT AUTO_INCREMENT PRIMARY KEY,
    id_editorial INT NOT NULL,
    titulo VARCHAR(150) NOT NULL,
    isbn VARCHAR(20) DEFAULT NULL,
    anio_edicion INT DEFAULT NULL,
    esta_espanol BOOLEAN DEFAULT TRUE,
    url_imagen VARCHAR(255) DEFAULT NULL,
    fecha_baja DATETIME DEFAULT NULL,
    CONSTRAINT fk_libro_editorial FOREIGN KEY (id_editorial) 
        REFERENCES editorial_carlos(id_editorial) 
        ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Tabla SOCIOS_RICARDO
CREATE TABLE socios_ricardo (
    id_socio INT AUTO_INCREMENT PRIMARY KEY,
    dni_nif VARCHAR(20) NOT NULL UNIQUE,
    nombre VARCHAR(50) NOT NULL,
    apellidos VARCHAR(100) NOT NULL,
    email VARCHAR(100) DEFAULT NULL,
    edad INT DEFAULT NULL,
    es_vip BOOLEAN DEFAULT FALSE,
    url_imagen VARCHAR(255) DEFAULT NULL,
    fecha_alta DATE NOT NULL,
    fecha_baja DATETIME DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. Tabla ESTADOS_EJEMPLAR
CREATE TABLE estados_ejemplar (
    id_estado INT AUTO_INCREMENT PRIMARY KEY,
    nombre_estado VARCHAR(50) NOT NULL UNIQUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. Tabla EJEMPLARES_JUANJO
CREATE TABLE ejemplares_juanjo (
    id_ejemplar INT AUTO_INCREMENT PRIMARY KEY,
    id_libro INT NOT NULL,
    id_estado INT NOT NULL,
    codigo_barras VARCHAR(50) NOT NULL UNIQUE,
    signatura VARCHAR(50) DEFAULT NULL,
    es_referencia BOOLEAN DEFAULT FALSE,
    numero_copia INT DEFAULT 1,
    url_imagen VARCHAR(255) DEFAULT NULL,
    fecha_baja DATETIME DEFAULT NULL,
    CONSTRAINT fk_ejemplar_libro FOREIGN KEY (id_libro) 
        REFERENCES libros_manuel(id_libro) 
        ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_ejemplar_estado FOREIGN KEY (id_estado) 
        REFERENCES estados_ejemplar(id_estado) 
        ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 7. Tabla PRESTAMOS_JUANJORPA
CREATE TABLE prestamos_juanjorpa (
    id_prestamo INT AUTO_INCREMENT PRIMARY KEY,
    id_ejemplar INT NOT NULL,
    id_socio INT NOT NULL,
    fecha_prestamo DATE NOT NULL,
    fecha_limite DATE NOT NULL,
    fecha_devolucion DATE DEFAULT NULL,
    observaciones VARCHAR(255) DEFAULT NULL,
    importe_fianza DECIMAL(8,2) DEFAULT 0.00,
    tiene_fianza BOOLEAN DEFAULT FALSE,
    url_imagen VARCHAR(255) DEFAULT NULL,
    fecha_baja DATETIME DEFAULT NULL,
    CONSTRAINT fk_prestamo_ejemplar FOREIGN KEY (id_ejemplar) 
        REFERENCES ejemplares_juanjo(id_ejemplar) 
        ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_prestamo_socio FOREIGN KEY (id_socio) 
        REFERENCES socios_ricardo(id_socio) 
        ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 8. Tabla USUARIOS
CREATE TABLE usuarios (
    id_usuario INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    nombre_completo VARCHAR(100) NOT NULL,
    rol ENUM('admin', 'usuario') DEFAULT 'usuario',
    url_imagen VARCHAR(255) DEFAULT NULL,
    fecha_baja DATETIME DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Reactivar las restricciones de claves foraneas
SET FOREIGN_KEY_CHECKS = 1;

-- ==========================================
-- DATOS DE PRUEBA REALISTAS (SEED DATA)
-- ==========================================

-- 1. Paises
INSERT INTO paises (nombre_pais) VALUES
('Espana'), ('Reino Unido'), ('Estados Unidos'), ('Colombia'), ('Argentina'),
('Francia'), ('Japon'), ('Alemania'), ('Italia'), ('Mexico'),
('Chile'), ('Peru'), ('Brasil'), ('Portugal'), ('Canada'),
('Australia'), ('Belgica'), ('Suiza'), ('Suecia'), ('Holanda'),
('Corea del Sur'), ('China');

-- 2. Estados de Ejemplar
INSERT INTO estados_ejemplar (nombre_estado) VALUES
('Nuevo'), ('Bueno'), ('Regular'), ('Deteriorado'), ('En reparacion');

-- 3. Editoriales
INSERT INTO editorial_carlos (id_pais, nombre_comercial, contacto, url_imagen, edita_espanol, anio_origen, fecha_baja) VALUES
(1, 'Editorial Planeta', 'contacto@planeta.es', 'img/editoriales/E01.webp', 1, 1949, NULL),
(1, 'Alfaguara', 'info@alfaguara.com', 'img/editoriales/E02.webp', 1, 1964, NULL),
(1, 'Penguin Random House Grupo Editorial', 'contacto@penguinrandomhouse.com', 'img/editoriales/E03.webp', 1, 2001, NULL),
(2, 'Penguin Books UK', 'support@penguin.co.uk', 'img/editoriales/E04.webp', 0, 1935, NULL),
(3, 'HarperCollins Publishers', 'contact@harpercollins.com', 'img/editoriales/E05.webp', 0, 1819, NULL),
(4, 'Seix Barral', 'seixbarral@planeta.es', 'img/editoriales/E06.webp', 1, 1911, NULL),
(5, 'Editorial Sudamericana', 'sudamericana@penguinrc.com', 'img/editoriales/E07.webp', 1, 1939, NULL),
(6, 'Gallimard', 'contact@gallimard.fr', 'img/editoriales/E08.webp', 0, 1911, NULL),
(7, 'Shueisha', 'info@shueisha.co.jp', 'img/editoriales/E09.webp', 0, 1926, NULL),
(8, 'Suhrkamp Verlag', 'kontakt@suhrkamp.de', 'img/editoriales/E10.webp', 0, 1950, NULL),
(1, 'Editorial Antigua Descatalogada', 'contacto@antigua.es', 'img/editoriales/E11.webp', 1, 1920, '2025-11-20 10:30:00');

-- 4. Libros
INSERT INTO libros_manuel (id_editorial, titulo, isbn, anio_edicion, esta_espanol, url_imagen, fecha_baja) VALUES
(1, 'Don Quijote de la Mancha (Miguel de Cervantes)', '978-84-376-0494-7', 1605, 1, 'img/libros/L01.webp', NULL),
(2, 'Cien anos de soledad (Gabriel Garcia Marquez)', '978-84-376-0495-4', 1967, 1, 'img/libros/L02.webp', NULL),
(3, '1984 (George Orwell)', '978-84-998-9094-4', 1949, 1, 'img/libros/L03.webp', NULL),
(4, 'Orgullo y prejuicio (Jane Austen)', '978-84-206-5163-7', 1813, 1, 'img/libros/L04.webp', NULL),
(5, 'El gran Gatsby (F. Scott Fitzgerald)', '978-84-376-0496-1', 1925, 1, 'img/libros/L05.webp', NULL),
(6, 'La sombra del viento (Carlos Ruiz Zafon)', '978-84-080-7954-5', 2001, 1, 'img/libros/L06.webp', NULL),
(7, 'Ficciones (Jorge Luis Borges)', '978-95-004-2699-2', 1944, 1, 'img/libros/L07.webp', NULL),
(8, 'El extranjero (Albert Camus)', '978-84-206-7421-6', 1942, 1, 'img/libros/L08.webp', NULL),
(9, 'One Piece Vol. 1 (Eiichiro Oda)', '978-4-08-872509-3', 1997, 1, 'img/libros/L09.webp', NULL),
(2, 'Rayuela (Julio Cortazar)', '978-84-376-0555-5', 1963, 1, 'img/libros/L10.webp', NULL),
(1, 'Obra Obsoleta Descatalogada', '978-84-000-0000-0', 1950, 1, 'img/libros/L11.webp', '2025-12-10 14:15:00');

-- 5. Socios
INSERT INTO socios_ricardo (dni_nif, nombre, apellidos, email, edad, es_vip, url_imagen, fecha_alta, fecha_baja) VALUES
('45123678X', 'Alejandro', 'Perez Gomez', 'alejandro.perez@ohara.es', 28, 1, 'img/socios/S01.webp', '2026-01-10', NULL),
('71234589Y', 'Lucia', 'Fernandez Ruiz', 'lucia.fernandez@ohara.es', 34, 0, 'img/socios/S02.webp', '2026-01-12', NULL),
('32659874Z', 'Carlos', 'Martinez Gil', 'carlos.martinez@ohara.es', 41, 1, 'img/socios/S03.webp', '2026-01-15', NULL),
('54123987H', 'Sofia', 'Alonso Blanco', 'sofia.alonso@ohara.es', 22, 0, 'img/socios/S04.webp', '2026-02-01', NULL),
('12547896K', 'Mateo', 'Gomez Navarro', 'mateo.gomez@ohara.es', 50, 1, 'img/socios/S05.webp', '2026-02-03', NULL),
('78965412L', 'Carmen', 'Moreno Jimenez', 'carmen.moreno@ohara.es', 29, 0, 'img/socios/S06.webp', '2026-02-05', NULL),
('36541289M', 'David', 'Sanchez Ramos', 'david.sanchez@ohara.es', 38, 0, 'img/socios/S07.webp', '2026-02-10', NULL),
('98745632N', 'Elena', 'Romero Navarro', 'elena.romero@ohara.es', 26, 1, 'img/socios/S08.webp', '2026-02-14', NULL),
('14785236P', 'Javier', 'Alvarez Martin', 'javier.alvarez@ohara.es', 45, 0, 'img/socios/S09.webp', '2026-02-18', NULL),
('96325874Q', 'Paula', 'Gil Torres', 'paula.gil@ohara.es', 31, 0, 'img/socios/S10.webp', '2026-02-20', NULL),
('11223344B', 'Marcos', 'Baja Ejemplo', 'marcos.baja@ohara.es', 40, 0, 'img/socios/S11.webp', '2025-01-10', '2026-01-05 09:00:00');

-- 6. Ejemplares
INSERT INTO ejemplares_juanjo (id_libro, id_estado, codigo_barras, signatura, es_referencia, numero_copia, url_imagen, fecha_baja) VALUES
(1, 2, 'LIB-001-01', '863.3 CER d', 0, 1, 'img/ejemplares/EJ01.webp', NULL),
(1, 1, 'LIB-001-02', '863.3 CER d', 1, 2, 'img/ejemplares/EJ02.webp', NULL),
(2, 2, 'LIB-002-01', '863.4 GAR c', 0, 1, 'img/ejemplares/EJ03.webp', NULL),
(2, 3, 'LIB-002-02', '863.4 GAR c', 0, 2, 'img/ejemplares/EJ04.webp', NULL),
(3, 1, 'LIB-003-01', '823.9 ORW 1', 0, 1, 'img/ejemplares/EJ05.webp', NULL),
(3, 3, 'LIB-003-02', '823.9 ORW 1', 0, 2, 'img/ejemplares/EJ06.webp', NULL),
(4, 2, 'LIB-004-01', '820 AUS o', 0, 1, 'img/ejemplares/EJ07.webp', NULL),
(5, 4, 'LIB-005-01', '813.5 FIT g', 0, 1, 'img/ejemplares/EJ08.webp', NULL),
(6, 2, 'LIB-006-01', '863.6 RUI s', 0, 1, 'img/ejemplares/EJ09.webp', NULL),
(6, 1, 'LIB-006-02', '863.6 RUI s', 0, 2, 'img/ejemplares/EJ10.webp', NULL),
(7, 1, 'LIB-007-01', '863.4 BOR f', 0, 1, 'img/ejemplares/EJ11.webp', NULL),
(8, 2, 'LIB-008-01', '843.9 CAM e', 0, 1, 'img/ejemplares/EJ12.webp', NULL),
(9, 1, 'LIB-009-01', '741.5 ODA o', 0, 1, 'img/ejemplares/EJ13.webp', NULL),
(9, 2, 'LIB-009-02', '741.5 ODA o', 0, 2, 'img/ejemplares/EJ14.webp', NULL),
(10, 2, 'LIB-010-01', '863.4 COR r', 0, 1, 'img/ejemplares/EJ15.webp', NULL),
(1, 4, 'LIB-001-03', '863.3 CER d', 0, 3, 'img/ejemplares/EJ16.webp', '2026-01-20 16:45:00');

-- 7. Prestamos
INSERT INTO prestamos_juanjorpa (id_ejemplar, id_socio, fecha_prestamo, fecha_limite, fecha_devolucion, observaciones, importe_fianza, tiene_fianza, url_imagen, fecha_baja) VALUES
(1, 1, '2026-01-05', '2026-01-19', '2026-01-15', 'Devuelto en perfectas condiciones', 0.00, 0, 'img/prestamos/P01.webp', NULL),
(2, 2, '2026-01-10', '2026-01-24', '2026-01-22', 'Sin incidencias', 0.00, 0, 'img/prestamos/P02.webp', NULL),
(3, 3, '2026-01-15', '2026-01-29', '2026-01-28', 'Devuelto a tiempo', 0.00, 0, 'img/prestamos/P03.webp', NULL),
(4, 4, '2026-01-20', '2026-02-03', '2026-02-02', 'Ninguna', 0.00, 0, 'img/prestamos/P04.webp', NULL),
(5, 5, '2026-02-01', '2026-02-15', NULL, 'Prestamo activo de ejemplar especial', 15.00, 1, 'img/prestamos/P05.webp', NULL),
(6, 6, '2026-02-03', '2026-02-17', NULL, 'Sin observaciones', 0.00, 0, 'img/prestamos/P06.webp', NULL),
(7, 7, '2026-02-05', '2026-02-19', NULL, 'Ninguna', 0.00, 0, 'img/prestamos/P07.webp', NULL),
(8, 8, '2026-02-08', '2026-02-22', NULL, 'Ejemplar con fianza de garantia', 20.00, 1, 'img/prestamos/P08.webp', NULL),
(9, 9, '2026-02-10', '2026-02-24', NULL, 'Sin observaciones', 0.00, 0, 'img/prestamos/P09.webp', NULL),
(10, 10, '2026-02-12', '2026-02-26', NULL, 'Ninguna', 0.00, 0, 'img/prestamos/P10.webp', NULL),
(11, 1, '2026-02-14', '2026-02-28', NULL, 'Sin observaciones', 0.00, 0, 'img/prestamos/P11.webp', NULL),
(12, 2, '2026-02-15', '2026-03-01', NULL, 'Ninguna', 0.00, 0, 'img/prestamos/P12.webp', NULL),
(1, 1, '2026-01-01', '2026-01-15', NULL, 'Prestamo anulado por error administrativo', 0.00, 0, 'img/prestamos/P13.webp', '2026-01-02 11:20:00');

-- 8. Usuarios del sistema
INSERT INTO usuarios (username, email, password, nombre_completo, rol, url_imagen, fecha_baja) VALUES
('admin', 'admin@ohara.es', 'admin123', 'Administrador Principal', 'admin', 'img/usuarios/U01.webp', NULL),
('usuario1', 'staff@ohara.es', 'user123', 'Personal de Gestion', 'usuario', 'img/usuarios/U02.webp', NULL),
('usuario_inactivo', 'inactivo@ohara.es', 'user123', 'Ex Empleado', 'usuario', 'img/usuarios/U03.webp', '2025-10-01 08:00:00');
