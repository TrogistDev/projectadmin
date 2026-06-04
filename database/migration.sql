-- Script de migração para MariaDB / MySQL

DROP TABLE IF EXISTS proyecto_miembro;
DROP TABLE IF EXISTS fases;
DROP TABLE IF EXISTS proyectos;
DROP TABLE IF EXISTS usuarios;

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

INSERT INTO usuarios (nombre, apellidos, correo, contrasena, rol, departamento) VALUES
('Admin', 'Origami', 'admin@origami.test', '$2y$10$G3zC6tCiidlVlBJf2a7EauJKpPoeRFOcyNfKou9JdQe8UEDV1A2Oy', 'administrador', 'Direção'),
('Jefe', 'Proyecto', 'jefe@origami.test', '$2y$10$6wQLZcUhpM5PyY/9OdKHke5PfFXZ5hhtAHX11mitzDGDI5jAHkOKW', 'jefe_proyecto', 'TI'),
('Paula', 'Silva', 'colaborador1@origami.test', '$2y$10$GmTFr9i0x7BZ9xu9gYhVqOJ019SdckJwNCS2iGFD.8WiWwdys24Lu', 'colaborador', 'Desenvolvimento'),
('Carlos', 'Fernández', 'colaborador2@origami.test', '$2y$10$XAAWLPi1IfsDCI2oAM/L/.4K.MZmh7P.jxnp//f80FlJvkDHQE86S', 'colaborador', 'QA'),
('Ana', 'González', 'colaborador3@origami.test', '$2y$10$GEYh5nmUUdREFEnHhS00t.IIDVa5AnOii9P4XDDkdmrvu3/CAbNT2', 'colaborador', 'Analítica');

INSERT INTO proyectos (nombre, descripcion, fecha_inicio, fecha_entrega, estado, responsable_id, porcentaje_avance) VALUES
('Portal Corporativo', 'Desarrollo del nuevo portal interno para empleados.', '2026-06-01', '2026-12-31', 'planificacion', 2, 0),
('Integración ERP', 'Conectar el ERP con el sistema de gestión de proyectos.', '2026-05-15', '2026-09-30', 'en_curso', 2, 40),
('Lanzamiento Mobile', 'Preparar el lanzamiento de la app móvil interna.', '2026-04-01', '2026-08-15', 'pausado', 2, 33),
('Migración de Datos', 'Migrar datos históricos al nuevo almacén de datos.', '2026-01-10', '2026-04-30', 'finalizado', 2, 100);

INSERT INTO fases (nombre, descripcion, orden, completada, fecha_completado, proyecto_id) VALUES
('Definición de requisitos', 'Recopilar y validar los requisitos del portal.', 1, 0, NULL, 1),
('Diseño de experiencia', 'Diseño UX/UI del portal corporativo.', 2, 0, NULL, 1),
('Desarrollo backend', 'Construcción del backend y APIs.', 1, 1, '2026-06-10 14:30:00', 2),
('Desarrollo frontend', 'Implementación de la interfaz de usuario.', 2, 0, NULL, 2),
('Pruebas de integración', 'Validar la conexión entre ERP y gestión de proyectos.', 3, 0, NULL, 2),
('Diseño de prototipo', 'Prototipo de la aplicación móvil.', 1, 1, '2026-05-02 09:00:00', 3),
('Desarrollo de funcionalidades', 'Construcción de las funciones clave de la app móvil.', 2, 0, NULL, 3),
('Revisión de seguridad', 'Auditoría de seguridad antes del lanzamiento.', 3, 0, NULL, 3),
('Análisis de datos', 'Preparación y limpieza de datos para migración.', 1, 1, '2026-02-15 11:20:00', 4),
('Exportación de información', 'Transferencia de datos a la nueva plataforma.', 2, 1, '2026-03-08 16:45:00', 4),
('Validación final', 'Comprobación y cierre de la migración.', 3, 1, '2026-04-20 10:10:00', 4);

INSERT INTO proyecto_miembro (proyecto_id, usuario_id, fecha_add, rol_especifico) VALUES
(1, 3, '2026-06-02 09:00:00', 'Desarrollador'),
(1, 4, '2026-06-03 10:15:00', 'Tester'),
(2, 3, '2026-05-16 11:00:00', 'Analista'),
(2, 5, '2026-05-18 09:40:00', 'Desarrollador'),
(3, 4, '2026-04-02 08:30:00', 'Tester'),
(3, 5, '2026-04-05 13:50:00', 'Desarrollador'),
(4, 3, '2026-01-12 12:00:00', 'Analista');
