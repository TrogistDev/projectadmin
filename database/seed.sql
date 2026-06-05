-- ============================================
-- seed.sql — Estructura + datos de prueba
-- ============================================

-- Desactivar verificación de FK para limpiar
SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS proyecto_miembro;
DROP TABLE IF EXISTS fases;
DROP TABLE IF EXISTS proyectos;
DROP TABLE IF EXISTS usuarios;
SET FOREIGN_KEY_CHECKS = 1;

-- ============================================
-- Estructura de tablas
-- ============================================

CREATE TABLE usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    apellidos VARCHAR(150) NOT NULL,
    correo VARCHAR(255) NOT NULL UNIQUE,
    contrasena VARCHAR(255) NOT NULL,
    rol ENUM('administrador', 'jefe_proyecto', 'colaborador') NOT NULL,
    departamento VARCHAR(100) DEFAULT NULL,
    fecha_creacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE proyectos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(150) NOT NULL UNIQUE,
    descripcion TEXT NOT NULL,
    fecha_inicio DATE NOT NULL,
    fecha_entrega DATE NOT NULL,
    estado ENUM('planificacion', 'en_curso', 'pausado', 'finalizado') NOT NULL DEFAULT 'planificacion',
    responsable_id INT NOT NULL,
    porcentaje_avance INT NOT NULL DEFAULT 0,
    fecha_creacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (responsable_id) REFERENCES usuarios(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE fases (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(150) NOT NULL,
    descripcion TEXT DEFAULT NULL,
    orden INT NOT NULL DEFAULT 1,
    completada TINYINT(1) NOT NULL DEFAULT 0,
    fecha_completado DATETIME DEFAULT NULL,
    proyecto_id INT NOT NULL,
    fecha_creacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (proyecto_id) REFERENCES proyectos(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE proyecto_miembro (
    proyecto_id INT NOT NULL,
    usuario_id INT NOT NULL,
    fecha_add DATETIME NOT NULL,
    rol_especifico VARCHAR(100) NOT NULL,
    PRIMARY KEY (proyecto_id, usuario_id),
    FOREIGN KEY (proyecto_id) REFERENCES proyectos(id) ON DELETE CASCADE,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Hash bcrypt para "password123"
SET @pw = '$2y$10$2jyOgn3ELjbi3s8rSyGC0O7VsWtRBNrKJGmPyTrQpaMasOy5EMMI6';

-- ============================================
-- 5 Usuarios (diferentes roles)
-- ============================================

-- 2 Administradores
INSERT INTO usuarios (nombre, apellidos, correo, contrasena, rol, departamento) VALUES
('Admin', 'Sistema', 'admin@empresa.test', @pw, 'administrador', 'TI'),
('Maria', 'Garcia', 'maria.garcia@empresa.test', @pw, 'administrador', 'RRHH');

-- 2 Jefes de Proyecto
INSERT INTO usuarios (nombre, apellidos, correo, contrasena, rol, departamento) VALUES
('Laura', 'Hernandez', 'laura.hernandez@empresa.test', @pw, 'jefe_proyecto', 'TI'),
('Diego', 'Gomez', 'diego.gomez@empresa.test', @pw, 'jefe_proyecto', 'Desarrollo');

-- 3 Colaboradores
INSERT INTO usuarios (nombre, apellidos, correo, contrasena, rol, departamento) VALUES
('Paula', 'Silva', 'paula.silva@empresa.test', @pw, 'colaborador', 'Desarrollo'),
('Carlos', 'Fernandez', 'carlos.fernandez@empresa.test', @pw, 'colaborador', 'QA'),
('Ana', 'Gonzalez', 'ana.gonzalez@empresa.test', @pw, 'colaborador', 'Analitica');

-- ============================================
-- 4 Proyectos (diferentes estados)
-- ============================================

INSERT INTO proyectos (nombre, descripcion, fecha_inicio, fecha_entrega, estado, responsable_id, porcentaje_avance) VALUES
('Portal Corporativo', 'Desarrollo del nuevo portal interno para empleados.', '2026-01-15', '2026-06-30', 'planificacion', 3, 0),
('Integracion ERP', 'Conectar el ERP con el sistema de gestion de proyectos.', '2026-02-01', '2026-07-15', 'en_curso', 4, 50),
('Migracion Datos', 'Migrar datos historicos al nuevo almacen de datos.', '2026-01-10', '2026-05-30', 'finalizado', 3, 100),
('App Movil', 'Desarrollo de aplicacion movil interna.', '2026-03-01', '2026-09-15', 'pausado', 4, 30);

-- ============================================
-- Fases de los proyectos
-- ============================================

-- Portal Corporativo (en planificacion - sin fases completadas)
INSERT INTO fases (nombre, descripcion, orden, completada, fecha_completado, proyecto_id) VALUES
('Definicion requisitos', 'Recopilar y validar los requisitos del portal.', 1, 0, NULL, 1),
('Diseno UI/UX', 'Diseño de la interfaz de usuario.', 2, 0, NULL, 1),
('Desarrollo backend', 'Construcción del backend y APIs.', 3, 0, NULL, 1);

-- Integracion ERP (en_curso - fases parcialmente completadas)
INSERT INTO fases (nombre, descripcion, orden, completada, fecha_completado, proyecto_id) VALUES
('Analisis sistema actual', 'Estudio del ERP actual.', 1, 1, '2026-02-15 10:00:00', 2),
('Desarrollo integracion', 'Construcción de la integración.', 2, 1, '2026-03-20 14:30:00', 2),
('Pruebas integracion', 'Validar la conexión.', 3, 0, NULL, 2),
('Documentacion', 'Documentación técnica del proceso.', 4, 0, NULL, 2);

-- Migracion Datos (finalizado - todas las fases completadas)
INSERT INTO fases (nombre, descripcion, orden, completada, fecha_completado, proyecto_id) VALUES
('Analisis datos', 'Preparación y limpieza de datos para migración.', 1, 1, '2026-02-01 09:00:00', 3),
('Exportacion datos', 'Transferencia de datos a la nueva plataforma.', 2, 1, '2026-02-15 11:00:00', 3),
('Validacion final', 'Comprobación y cierre de la migración.', 3, 1, '2026-03-01 16:00:00', 3);

-- App Movil (pausado - fases parcialmente completadas)
INSERT INTO fases (nombre, descripcion, orden, completada, fecha_completado, proyecto_id) VALUES
('Prototipo inicial', 'Diseño del prototipo de la app.', 1, 1, '2026-03-10 10:00:00', 4),
('Desarrollo funcional', 'Construcción de funciones clave.', 2, 0, NULL, 4),
('Pruebas usabilidad', 'Testing con usuarios internos.', 3, 0, NULL, 4);

-- ============================================
-- Miembros del equipo (proyecto_miembro)
-- ============================================

INSERT INTO proyecto_miembro (proyecto_id, usuario_id, fecha_add, rol_especifico) VALUES
(1, 5, '2026-01-20 09:00:00', 'Analista'),
(1, 6, '2026-01-22 10:30:00', 'Desarrollador'),
(2, 5, '2026-02-02 11:00:00', 'Analista'),
(2, 6, '2026-02-05 08:45:00', 'Tester'),
(2, 7, '2026-02-10 09:15:00', 'Desarrollador'),
(3, 5, '2026-02-05 14:00:00', 'Analista'),
(3, 7, '2026-02-10 10:00:00', 'Desarrollador'),
(4, 6, '2026-03-05 09:00:00', 'Desarrollador'),
(4, 7, '2026-03-06 10:30:00', 'Tester');