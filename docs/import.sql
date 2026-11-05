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
    fecha_baja DATE DEFAULT NULL,
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
    fecha_baja DATE DEFAULT NULL,
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
    fecha_alta DATE NOT NULL,
    fecha_baja DATE DEFAULT NULL
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
    fecha_baja DATE DEFAULT NULL,
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
    CONSTRAINT fk_prestamo_ejemplar FOREIGN KEY (id_ejemplar) 
        REFERENCES ejemplares_juanjo(id_ejemplar) 
        ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_prestamo_socio FOREIGN KEY (id_socio) 
        REFERENCES socios_ricardo(id_socio) 
        ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Reactivar las restricciones de claves foraneas
SET FOREIGN_KEY_CHECKS = 1;

-- ==========================================
-- DATOS DE PRUEBA 
-- ==========================================

-- 21 Paises
INSERT INTO paises (nombre_pais) VALUES
('Espana'),
('Reino Unido'),
('Estados Unidos'),
('Colombia'),
('Argentina'),
('Francia'),
('Japon'),
('Alemania'),
('Italia'),
('Mexico'),
('Chile'),
('Peru'),
('Brasil'),
('Portugal'),
('Canada'),
('Australia'),
('Belgica'),
('Suiza'),
('Suecia'),
('Holanda'),
('Corea del Sur'),
('China');

-- 2. Estados de Ejemplar
INSERT INTO estados_ejemplar (nombre_estado) VALUES
('Nuevo'),
('Bueno'),
('Regular'),
('Deteriorado'),
('En reparacion');

-- 3. Editoriales Reales (Mapeadas a su id_pais correspondiente)
INSERT INTO editorial_carlos (id_pais, nombre_comercial, contacto, fecha_baja) VALUES
(1, 'Editorial Planeta', 'contacto@planeta.es', NULL),
(1, 'Alfaguara', 'info@alfaguara.com', NULL),
(1, 'Penguin Random House Grupo Editorial', 'contacto@penguinrandomhouse.com', NULL),
(2, 'Penguin Books UK', 'support@penguin.co.uk', NULL),
(3, 'HarperCollins Publishers', 'contact@harpercollins.com', NULL),
(4, 'Seix Barral', 'seixbarral@planeta.es', NULL),
(5, 'Editorial Sudamericana', 'sudamericana@penguinrc.com', NULL),
(6, 'Gallimard', 'contact@gallimard.fr', NULL),
(7, 'Shueisha', 'info@shueisha.co.jp', NULL),
(8, 'Suhrkamp Verlag', 'kontakt@suhrkamp.de', NULL);

-- 4. Obras Literarias Reales (libros_manuel)
INSERT INTO libros_manuel (id_editorial, titulo, isbn, anio_edicion, fecha_baja) VALUES
(1, 'Don Quijote de la Mancha (Miguel de Cervantes)', '978-84-376-0494-7', 1605, NULL),
(2, 'Cien anos de soledad (Gabriel Garcia Marquez)', '978-84-376-0495-4', 1967, NULL),
(3, '1984 (George Orwell)', '978-84-998-9094-4', 1949, NULL),
(4, 'Orgullo y prejuicio (Jane Austen)', '978-84-206-5163-7', 1813, NULL),
(5, 'El gran Gatsby (F. Scott Fitzgerald)', '978-84-376-0496-1', 1925, NULL),
(6, 'La sombra del viento (Carlos Ruiz Zafon)', '978-84-080-7954-5', 2001, NULL),
(7, 'Ficciones (Jorge Luis Borges)', '978-95-004-2699-2', 1944, NULL),
(8, 'El extranjero (Albert Camus)', '978-84-206-7421-6', 1942, NULL),
(9, 'One Piece Vol. 1 (Eiichiro Oda)', '978-4-08-872509-3', 1997, NULL),
(2, 'Rayuela (Julio Cortazar)', '978-84-376-0555-5', 1963, NULL);

-- 5. Socios con Nombres y Apellidos Realistas
INSERT INTO socios_ricardo (dni_nif, nombre, apellidos, email, fecha_alta, fecha_baja) VALUES
('45123678X', 'Alejandro', 'Perez Gomez', 'alejandro.perez@ohara.es', '2026-01-10', NULL),
('71234589Y', 'Lucia', 'Fernandez Ruiz', 'lucia.fernandez@ohara.es', '2026-01-12', NULL),
('32659874Z', 'Carlos', 'Martinez Gil', 'carlos.martinez@ohara.es', '2026-01-15', NULL),
('54123987H', 'Sofia', 'Alonso Blanco', 'sofia.alonso@ohara.es', '2026-02-01', NULL),
('12547896K', 'Mateo', 'Gomez Navarro', 'mateo.gomez@ohara.es', '2026-02-03', NULL),
('78965412L', 'Carmen', 'Moreno Jimenez', 'carmen.moreno@ohara.es', '2026-02-05', NULL),
('36541289M', 'David', 'Sanchez Ramos', 'david.sanchez@ohara.es', '2026-02-10', NULL),
('98745632N', 'Elena', 'Romero Navarro', 'elena.romero@ohara.es', '2026-02-14', NULL),
('14785236P', 'Javier', 'Alvarez Martin', 'javier.alvarez@ohara.es', '2026-02-18', NULL),
('96325874Q', 'Paula', 'Gil Torres', 'paula.gil@ohara.es', '2026-02-20', NULL);

-- 6. Ejemplares Físicos Asociados (varios por libro)
INSERT INTO ejemplares_juanjo (id_libro, id_estado, codigo_barras, signatura, fecha_baja) VALUES
(1, 2, 'LIB-001-01', '863.3 CER d', NULL),
(1, 1, 'LIB-001-02', '863.3 CER d', NULL),
(2, 2, 'LIB-002-01', '863.4 GAR c', NULL),
(2, 3, 'LIB-002-02', '863.4 GAR c', NULL),
(3, 1, 'LIB-003-01', '823.9 ORW 1', NULL),
(3, 3, 'LIB-003-02', '823.9 ORW 1', NULL),
(4, 2, 'LIB-004-01', '820 AUS o', NULL),
(5, 4, 'LIB-005-01', '813.5 FIT g', NULL),
(6, 2, 'LIB-006-01', '863.6 RUI s', NULL),
(6, 1, 'LIB-006-02', '863.6 RUI s', NULL),
(7, 1, 'LIB-007-01', '863.4 BOR f', NULL),
(8, 2, 'LIB-008-01', '843.9 CAM e', NULL),
(9, 1, 'LIB-009-01', '741.5 ODA o', NULL),
(9, 2, 'LIB-009-02', '741.5 ODA o', NULL),
(10, 2, 'LIB-010-01', '863.4 COR r', NULL);

-- 7. Préstamos Ampliados (Históricos y Activos)
INSERT INTO prestamos_juanjorpa (id_ejemplar, id_socio, fecha_prestamo, fecha_limite, fecha_devolucion) VALUES
(1, 1, '2026-01-05', '2026-01-19', '2026-01-15'),  -- Devuelto
(2, 2, '2026-01-10', '2026-01-24', '2026-01-22'),  -- Devuelto
(3, 3, '2026-01-15', '2026-01-29', '2026-01-28'),  -- Devuelto
(4, 4, '2026-01-20', '2026-02-03', '2026-02-02'),  -- Devuelto
(5, 5, '2026-02-01', '2026-02-15', NULL),          -- Activo
(6, 6, '2026-02-03', '2026-02-17', NULL),          -- Activo
(7, 7, '2026-02-05', '2026-02-19', NULL),          -- Activo
(8, 8, '2026-02-08', '2026-02-22', NULL),          -- Activo
(9, 9, '2026-02-10', '2026-02-24', NULL),          -- Activo
(10, 10, '2026-02-12', '2026-02-26', NULL),        -- Activo
(11, 1, '2026-02-14', '2026-02-28', NULL),        -- Activo
(12, 2, '2026-02-15', '2026-03-01', NULL);        -- Activo