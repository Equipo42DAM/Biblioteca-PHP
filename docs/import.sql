-- Creacion de la base de datos
CREATE DATABASE IF NOT EXISTS biblioteca_ohara CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE biblioteca_ohara;

-- Desactivar temporalmente las restricciones de claves foraneas para limpiar si ya existian
SET FOREIGN_KEY_CHECKS = 0;

-- Eliminar tablas en orden inverso para evitar conflictos
DROP TABLE IF EXISTS prestamos_juanjorpa;
DROP TABLE IF EXISTS ejemplares_juanjo;
DROP TABLE IF EXISTS libros_manuel;
DROP TABLE IF EXISTS socios_ricardo;
DROP TABLE IF EXISTS editorial_carlos;

-- 1. Tabla EDITORIAL_CARLOS
CREATE TABLE editorial_carlos (
    id_editorial INT AUTO_INCREMENT PRIMARY KEY,
    nombre_comercial VARCHAR(100) NOT NULL,
    pais VARCHAR(50) DEFAULT NULL,
    contacto VARCHAR(100) DEFAULT NULL,
    fecha_baja DATE DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Tabla LIBROS_MANUEL
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

-- 3. Tabla SOCIOS_RICARDO
CREATE TABLE socios_ricardo (
    id_socio INT AUTO_INCREMENT PRIMARY KEY,
    dni_nif VARCHAR(20) NOT NULL UNIQUE,
    nombre VARCHAR(50) NOT NULL,
    apellidos VARCHAR(100) NOT NULL,
    email VARCHAR(100) DEFAULT NULL,
    fecha_alta DATE NOT NULL,
    fecha_baja DATE DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Tabla EJEMPLARES_JUANJO
CREATE TABLE ejemplares_juanjo (
    id_ejemplar INT AUTO_INCREMENT PRIMARY KEY,
    id_libro INT NOT NULL,
    codigo_barras VARCHAR(50) NOT NULL UNIQUE,
    signatura VARCHAR(50) DEFAULT NULL,
    estado VARCHAR(50) DEFAULT 'Bueno',
    fecha_baja DATE DEFAULT NULL,
    CONSTRAINT fk_ejemplar_libro FOREIGN KEY (id_libro) 
        REFERENCES libros_manuel(id_libro) 
        ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. Tabla PRESTAMOS_JUANJORPA
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
-- DATOS DE PRUEBA MASIVOS (SEED DATA)
-- ==========================================

-- 1. Editoriales
INSERT INTO editorial_carlos (nombre_comercial, pais, contacto, fecha_baja) VALUES
('Editorial Planeta', 'Espana', 'contacto@planeta.es', NULL),
('Norma Editorial', 'Espana', 'info@normaeditorial.com', NULL),
('Penguin Random House', 'Estados Unidos', 'support@penguinrandomhouse.com', NULL),
('Shueisha', 'Japon', 'contact@shueisha.co.jp', NULL),
('Alfaguara', 'Espana', 'alfaguara@santillana.es', NULL),
('Minotauro', 'Espana', 'minotauro@planeta.es', NULL),
('Salamandra', 'Espana', 'info@salamandra.info', NULL),
('Panini Comics', 'Italia', 'support@panini.it', NULL),
('Debolsillo', 'Espana', 'debolsillo@penguinrc.com', NULL),
('Editorial Anagrama', 'Espana', 'anagrama@anagrama-ed.es', NULL);

-- 2. Libros
INSERT INTO libros_manuel (id_editorial, titulo, isbn, anio_edicion, fecha_baja) VALUES
(1, 'Don Quijote de la Mancha', '978-84-376-0494-7', 1605, NULL),
(4, 'One Piece Vol. 1', '978-4-08-872509-3', 1997, NULL),
(3, 'Cien anos de soledad', '978-0-307-47472-8', 1967, NULL),
(6, 'El Senor de los Anillos: La Comunidad del Anillo', '978-84-450-7175-3', 1954, NULL),
(7, 'Harry Potter y la Piedra Filosofal', '978-84-788-8445-2', 1997, NULL),
(5, 'La sombra del viento', '978-84-080-7954-5', 2001, NULL),
(4, 'Naruto Vol. 1', '978-4-08-872840-7', 2000, NULL),
(8, 'The Amazing Spider-Man: De vuelta a las fuentes', '978-84-988-5123-1', 2001, NULL),
(10, 'Los detectives salvajes', '978-84-339-0586-4', 1998, NULL),
(9, 'La metamorfosis', '978-84-910-5012-3', 1915, NULL),
(2, 'Fullmetal Alchemist Vol. 1', '978-84-984-7212-7', 2002, NULL),
(1, 'Farina', '978-84-170-6756-1', 2018, NULL),
(3, 'El resplandor', '978-84-663-3235-4', 1977, NULL),
(5, 'Rayuela', '978-84-376-0555-5', 1963, NULL),
(6, 'El Hobbit', '978-84-450-7140-1', 1937, NULL);

-- 3. Socios
INSERT INTO socios_ricardo (dni_nif, nombre, apellidos, email, fecha_alta, fecha_baja) VALUES
('12345678A', 'Monkey D.', 'Luffy', 'luffy@ohara.com', '2026-01-10', NULL),
('87654321B', 'Roronoa', 'Zoro', 'zoro@ohara.com', '2026-01-15', NULL),
('11223344C', 'Nami', 'Gata Ladrona', 'nami@ohara.com', '2026-02-01', NULL),
('22334455D', 'Sanji', 'Vinsmoke', 'sanji@ohara.com', '2026-02-05', NULL),
('33445566E', 'Tony Chopper', 'Medico de Abordo', 'chopper@ohara.com', '2026-02-10', NULL),
('44556677F', 'Nico', 'Robin', 'robin@ohara.com', '2026-02-12', NULL),
('55667788G', 'Franky', 'Cyborg', 'franky@ohara.com', '2026-02-15', NULL),
('66778899H', 'Brook', 'Soul King', 'brook@ohara.com', '2026-02-18', NULL),
('77889900I', 'Trafalgar', 'Water Law', 'law@ohara.com', '2026-02-20', NULL),
('88990011J', 'Portgas D.', 'Ace', 'ace@ohara.com', '2026-02-22', NULL);

-- 4. Ejemplares
INSERT INTO ejemplares_juanjo (id_libro, codigo_barras, signatura, estado, fecha_baja) VALUES
(1, 'LIB-001-01', '863.3 QUI d', 'Bueno', NULL),
(1, 'LIB-001-02', '863.3 QUI d', 'Deteriorado', NULL),
(2, 'LIB-002-01', '741.5 ONA o', 'Nuevo', NULL),
(2, 'LIB-002-02', '741.5 ONA o', 'Bueno', NULL),
(3, 'LIB-003-01', '863.4 GAB c', 'Bueno', NULL),
(4, 'LIB-004-01', '823.9 TOL s', 'Nuevo', NULL),
(4, 'LIB-004-02', '823.9 TOL s', 'Regular', NULL),
(5, 'LIB-005-01', '823.9 HAR h', 'Bueno', NULL),
(5, 'LIB-005-02', '823.9 HAR h', 'Deteriorado', NULL),
(5, 'LIB-005-03', '823.9 HAR h', 'Nuevo', NULL),
(6, 'LIB-006-01', '863.6 RUI s', 'Bueno', NULL),
(7, 'LIB-007-01', '741.5 NAR n', 'Nuevo', NULL),
(8, 'LIB-008-01', '741.5 SPI s', 'Regular', NULL),
(9, 'LIB-009-01', '863.6 BOL d', 'Bueno', NULL),
(10, 'LIB-010-01', '833.9 KAF m', 'Bueno', NULL),
(11, 'LIB-011-01', '741.5 ARA f', 'Nuevo', NULL),
(12, 'LIB-012-01', '946.1 CAR f', 'Bueno', NULL),
(13, 'LIB-013-01', '813.5 KIN r', 'En reparacion', NULL),
(14, 'LIB-014-01', '863.4 COR r', 'Bueno', NULL),
(15, 'LIB-015-01', '823.9 TOL h', 'Bueno', NULL),
(3, 'LIB-003-02', '863.4 GAB c', 'Nuevo', NULL),
(6, 'LIB-006-02', '863.6 RUI s', 'En reparacion', NULL),
(1, 'LIB-001-03', '863.3 QUI d', 'Nuevo', NULL),
(7, 'LIB-007-02', '741.5 NAR n', 'Bueno', NULL),
(5, 'LIB-005-04', '823.9 HAR h', 'Bueno', NULL);

-- 5. Prestamos
INSERT INTO prestamos_juanjorpa (id_ejemplar, id_socio, fecha_prestamo, fecha_limite, fecha_devolucion) VALUES
(2, 1, '2026-02-01', '2026-02-15', NULL),
(3, 2, '2026-02-03', '2026-02-17', NULL),
(6, 3, '2026-01-10', '2026-01-24', '2026-01-20'),
(8, 4, '2026-02-05', '2026-02-19', NULL),
(11, 5, '2026-01-12', '2026-01-26', '2026-01-25'),
(12, 6, '2026-02-10', '2026-02-24', NULL),
(16, 7, '2026-02-14', '2026-02-28', NULL),
(18, 8, '2026-01-05', '2026-01-19', '2026-01-18');