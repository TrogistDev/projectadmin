-- ============================================
-- seed.sql — LIMPA TUDO E INSERE 1000 USUÁRIOS + 80 PROJETOS
-- Senha padrão: password123
-- ============================================

SET FOREIGN_KEY_CHECKS = 0;

TRUNCATE TABLE proyecto_miembro;
TRUNCATE TABLE fases;
TRUNCATE TABLE proyectos;
TRUNCATE TABLE usuarios;

SET FOREIGN_KEY_CHECKS = 1;

-- Hash bcrypt válido para "password123"
SET @pw = '$2y$10$2jyOgn3ELjbi3s8rSyGC0O7VsWtRBNrKJGmPyTrQpaMasOy5EMMI6';

-- ============================================
-- 5 ADMINISTRADORES
-- ============================================
INSERT INTO usuarios (nombre, apellidos, correo, contrasena, rol, departamento) VALUES
('Admin', 'Sistema', 'admin@empresa.test', @pw, 'administrador', 'TI'),
('Maria', 'Garcia', 'maria.garcia@empresa.test', @pw, 'administrador', 'RRHH'),
('Carlos', 'Lopez', 'carlos.lopez@empresa.test', @pw, 'administrador', 'Finanzas'),
('Ana', 'Martinez', 'ana.martinez@empresa.test', @pw, 'administrador', 'Operaciones'),
('Pedro', 'Rodriguez', 'pedro.rodriguez@empresa.test', @pw, 'administrador', 'Legal');

-- ============================================
-- 20 JEFES DE PROJETO
-- ============================================
INSERT INTO usuarios (nombre, apellidos, correo, contrasena, rol, departamento) VALUES
('Laura', 'Hernandez', 'laura.hernandez@empresa.test', @pw, 'jefe_proyecto', 'TI'),
('Diego', 'Gomez', 'diego.gomez@empresa.test', @pw, 'jefe_proyecto', 'Marketing'),
('Sofia', 'Diaz', 'sofia.diaz@empresa.test', @pw, 'jefe_proyecto', 'Desarrollo'),
('Javier', 'Munoz', 'javier.munoz@empresa.test', @pw, 'jefe_proyecto', 'QA'),
('Elena', 'Torres', 'elena.torres@empresa.test', @pw, 'jefe_proyecto', 'Diseno'),
('Miguel', 'Vargas', 'miguel.vargas@empresa.test', @pw, 'jefe_proyecto', 'Infraestructura'),
('Paula', 'Rojas', 'paula.rojas@empresa.test', @pw, 'jefe_proyecto', 'Bases de Datos'),
('Andres', 'Flores', 'andres.flores@empresa.test', @pw, 'jefe_proyecto', 'Seguridad'),
('Natalia', 'Reyes', 'natalia.reyes@empresa.test', @pw, 'jefe_proyecto', 'Movil'),
('Ricardo', 'Mendoza', 'ricardo.mendoza@empresa.test', @pw, 'jefe_proyecto', 'Cloud'),
('Carmen', 'Sanchez', 'carmen.sanchez@empresa.test', @pw, 'jefe_proyecto', 'Soporte'),
('Fernando', 'Morales', 'fernando.morales@empresa.test', @pw, 'jefe_proyecto', 'Arquitectura'),
('Isabel', 'Gutierrez', 'isabel.gutierrez@empresa.test', @pw, 'jefe_proyecto', 'BI'),
('Roberto', 'Castro', 'roberto.castro@empresa.test', @pw, 'jefe_proyecto', 'DevOps'),
('Lucia', 'Ortiz', 'lucia.ortiz@empresa.test', @pw, 'jefe_proyecto', 'Frontend'),
('Daniel', 'Jimenez', 'daniel.jimenez@empresa.test', @pw, 'jefe_proyecto', 'Backend'),
('Monica', 'Alvarez', 'monica.alvarez@empresa.test', @pw, 'jefe_proyecto', 'Calidad'),
('Pablo', 'Ruiz', 'pablo.ruiz@empresa.test', @pw, 'jefe_proyecto', 'UX/UI'),
('Silvia', 'Navarro', 'silvia.navarro@empresa.test', @pw, 'jefe_proyecto', 'Data Science'),
('Hector', 'Dominguez', 'hector.dominguez@empresa.test', @pw, 'jefe_proyecto', 'Integraciones');

-- ============================================
-- 975 COLABORADORES (IDs 26 a 1000)
-- ============================================

INSERT INTO usuarios (nombre, apellidos, correo, contrasena, rol, departamento)
SELECT 
    CONCAT('Colab_', seq)                              AS nombre,
    CONCAT('Apellido_', seq)                            AS apellidos,
    CONCAT('colab.', seq, '@empresa.test')              AS correo,
    @pw                                                 AS contrasena,
    'colaborador'                                       AS rol,
    ELT(1 + FLOOR(RAND() * 12),
        'TI','Desarrollo','QA','Marketing','Finanzas',
        'RRHH','Operaciones','Diseno','Legal','Soporte',
        'Infraestructura','Data')                       AS departamento
FROM (
    SELECT @n := @n + 1 AS seq
    FROM (SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5) a,
         (SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5) b,
         (SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5) c,
         (SELECT @n := 25) init
    LIMIT 975
) nums;

-- ============================================
-- 80 PROJETOS
-- ============================================
INSERT INTO proyectos (nombre, descripcion, fecha_inicio, fecha_entrega, estado, responsable_id, porcentaje_avance) VALUES
('Portal Corporativo v2',      'Rediseno completo del portal interno.',                                                      '2025-01-15','2026-06-30','en_curso',     6,  35),
('Migracion a la Nube',        'Migrar toda la infraestructura a AWS.',                                                       '2025-03-01','2026-09-15','en_curso',    10,  50),
('App Movil Empleados',        'Desarrollo de aplicacion movil para RRHH.',                                                   '2025-06-10','2026-12-20','planificacion', 7,  0),
('Sistema de Facturacion',     'Nuevo motor de facturacion electronica.',                                                      '2025-02-20','2026-05-30','finalizado',   8, 100),
('Intranet Social',            'Red social interna para colaboradores.',                                                       '2025-08-01','2026-10-15','pausado',      9,  20),
('Pipeline CI/CD',             'Automatizacion de deploys y testing.',                                                         '2025-04-15','2026-07-30','en_curso',    10,  60),
('Dashboard Financiero',       'Panel de KPIs para direccion financiera.',                                                     '2025-05-01','2026-08-20','planificacion',11,  0),
('API Gateway Unificado',      'Puerta de enlace unica para microservicios.',                                                  '2025-07-15','2026-11-30','en_curso',    12,  40),
('Sistema de Reservas',        'Gestion de salas y recursos compartidos.',                                                     '2025-09-01','2026-12-15','planificacion',13,  0),
('Analisis Predictivo',        'Modelos ML para prediccion de ventas.',                                                        '2025-01-20','2026-06-30','en_curso',    14,  55),
('Refactor Legacy ERP',        'Modernizacion del modulo de pagos.',                                                           '2025-03-10','2026-08-15','en_curso',    15,  30),
('Portal del Cliente',         'Self-service para clientes.',                                                                  '2025-10-01','2027-01-30','planificacion', 6,  0),
('Chatbot Soporte Nivel 1',    'Asistente virtual para atencion inicial.',                                                     '2025-06-15','2026-10-30','en_curso',    16,  45),
('Automatizacion Reportes',    'Generacion automatica de reportes mensuales.',                                                 '2025-04-01','2026-07-15','finalizado',  17, 100),
('Gestion Documental',         'Sistema de manejo de documentos legales.',                                                     '2025-11-01','2027-03-31','planificacion',18,  0),
('Monitorizacion 24/7',        'Infraestructura de monitoreo y alertas.',                                                      '2025-02-01','2026-05-15','finalizado',  19, 100),
('Onboarding Digital',         'Proceso de incorporacion 100% digital.',                                                       '2025-07-01','2026-09-30','pausado',     20,  25),
('Integracion Proveedores',    'API para intercambio con cadena de suministro.',                                                '2025-05-15','2026-10-15','en_curso',    11,  35),
('Plataforma E-learning',      'Campus virtual para capacitacion interna.',                                                    '2025-08-15','2027-02-28','planificacion',12,  0),
('Sistema de Incidencias',     'Ticketing avanzado con SLA.',                                                                  '2025-03-20','2026-08-30','en_curso',    13,  50),
('Migracion Datos Historicos', 'Limpieza y migracion a data warehouse.',                                                        '2025-01-10','2026-04-30','finalizado',  14, 100),
('App de Reuniones',           'Videoconferencia integrada con calendario.',                                                    '2025-09-15','2026-12-31','pausado',     15,  15),
('Motor de ReglasNegocio',     'Motor configurable para flujos internos.',                                                      '2025-06-01','2026-11-15','en_curso',    16,  40),
('Certificaciones ISO',        'Sistema de gestion de certificaciones.',                                                        '2025-04-10','2026-09-30','planificacion',17,  0),
('Backup Automatizado',        'Estrategia de respaldo y recuperacion.',                                                        '2025-02-15','2026-06-15','finalizado',  18, 100),
('Inventario Inteligente',     'Prediccion de stock con IA.',                                                                   '2025-10-15','2027-04-30','planificacion',19,  0),
('Portal de Proveedores',      'Extranet para gestion de proveedores.',                                                         '2025-07-20','2026-12-15','pausado',     20,  20),
('Automatizacion Contable',    'Cierre contable automatico.',                                                                   '2025-05-01','2026-10-31','en_curso',     6,  35),
('Sistema de Turnos',          'Gestion de turnos y horarios laborales.',                                                       '2025-08-01','2027-01-31','planificacion', 7,  0),
('Firma Digital',              'Implementacion de firma electronica.',                                                           '2025-03-15','2026-07-31','en_curso',     8,  55),
('Business Intelligence',      'Plataforma unificada de analitica.',                                                            '2025-01-25','2026-08-15','en_curso',     9,  60),
('Gamificacion Procesos',      'Sistema de incentivos y logros.',                                                                '2025-09-01','2026-11-30','pausado',     10,  10),
('Gestion de Riesgos',         'Matriz de riesgos y planes de contingencia.',                                                    '2025-04-20','2026-09-15','planificacion',11,  0),
('API Gateway v2',             'Puerta de enlace mejorada.',                                                                     '2025-06-15','2026-12-31','en_curso',    12,  30),
('Central de Comunicados',     'Gestion de comunicados internos.',                                                               '2025-02-10','2026-05-31','finalizado',  13, 100),
('Sistema de Encuestas',       'Encuestas pulse y clima laboral.',                                                               '2025-10-01','2027-03-15','planificacion',14,  0),
('Monitorizacion Red',         'Analisis de trafico y seguridad perimetral.',                                                    '2025-07-10','2026-11-30','en_curso',    15,  45),
('Robotizacion RPA',           'Automatizacion de procesos repetitivos.',                                                        '2025-05-20','2026-10-15','pausado',     16,  22),
('Gestion de Vacaciones',      'Sistema de solicitudes y aprobaciones.',                                                         '2025-03-01','2026-07-15','finalizado',  17, 100),
('Plataforma de Eventos',      'Organizacion interna de eventos.',                                                                '2025-11-01','2027-02-28','planificacion',18,  0),
('Data Lake Corporativo',      'Repositorio unificado de datos.',                                                                 '2025-08-20','2027-04-30','en_curso',    19,  25),
('Sistema de Nominas',         'Calculo automatizado de nominas.',                                                                '2025-04-05','2026-09-30','en_curso',    20,  40),
('App de Gastos',              'Registro y aprobacion de gastos.',                                                                '2025-09-10','2026-12-31','pausado',      6,  18),
('Certificados Digitales',     'Emision automatica de certificados.',                                                            '2025-06-20','2026-10-31','planificacion', 7,  0),
('Machine Learning Ops',       'Infraestructura para modelos ML.',                                                                 '2025-01-15','2026-07-15','en_curso',     8,  50),
('Central de Pedidos',         'Gestion centralizada de pedidos internos.',                                                       '2025-10-01','2027-03-31','planificacion', 9,  0),
('Sistema de Auditoria',       'Tracking automatico de cambios.',                                                                 '2025-05-15','2026-11-15','en_curso',    10,  35),
('Portal Bienestar',           'Programa de bienestar laboral digital.',                                                           '2025-07-01','2026-12-31','pausado',     11,  12),
('Automatizacion Legal',       'Generacion de contratos y documentos.',                                                            '2025-03-10','2026-08-15','planificacion',12,  0),
('Gestion de Activos',         'Inventario y mantenimiento de equipos.',                                                           '2025-08-15','2027-01-31','en_curso',    13,  30),
('API Interna Unificada',      'Consolidacion de APIs internas.',                                                                  '2025-04-25','2026-10-15','en_curso',    14,  42),
('Sistema de Permisos Base',   'Flujo digital de aprobaciones.',                                                                   '2025-02-20','2026-06-30','finalizado',  15, 100),
('Realidad Aumentada',         'Prototipo de mantenimiento con AR.',                                                               '2025-11-15','2027-06-30','planificacion',16,  0),
('Blockchain Proveedores',     'Trazabilidad de cadena de suministro.',                                                            '2025-09-01','2027-03-31','pausado',     17,   8),
('Sistema de Reclutamiento',   'ATS con IA para seleccion de CVs.',                                                               '2025-06-01','2026-11-30','en_curso',    18,  38),
('Gestion de Contratos',       'Lifecycle digital de contratos.',                                                                  '2025-05-10','2026-10-31','en_curso',    19,  28),
('Plataforma de Cursos',       'LMS con gamificacion.',                                                                             '2025-10-20','2027-04-30','planificacion',20,  0),
('Automatizacion QA',          'Framework de testing automatizado.',                                                                '2025-03-25','2026-09-15','finalizado',   6, 100),
('Sistema de Fidelizacion',    'Programa de puntos para empleados.',                                                                '2025-07-15','2026-12-31','pausado',      7,  15),
('Data Warehouse',             'Migracion a nuevo DW corporativo.',                                                                 '2025-01-20','2026-06-15','finalizado',   8, 100),
('Gestion de Flotas',          'Control de vehiculos y rutas.',                                                                     '2025-08-10','2027-02-28','planificacion', 9,  0),
('API Facturacion',            'Endpoints para facturacion electronica.',                                                           '2025-04-15','2026-09-30','en_curso',    10,  48),
('Sistema de Novedades',       'Comunicados y alertas push.',                                                                       '2025-06-10','2026-11-15','planificacion',11,  0),
('Monitorizacion UX',          'Heatmaps y analytics de experiencias.',                                                             '2025-09-15','2026-12-15','pausado',     12,  20),
('Automatizacion Almacen',     'WMS con robots autonomos.',                                                                         '2025-05-01','2026-10-15','en_curso',    13,  33),
('Gestion de Proyectos Base',  'Metodologias agiles a escala.',                                                                     '2025-02-15','2026-07-31','finalizado',  14, 100),
('Portal Gobierno',            'Transparencia y rendicion de cuentas.',                                                             '2025-11-01','2027-05-31','planificacion',15,  0),
('Sistema de Cobros',          'Pasarela de pagos integrada.',                                                                       '2025-07-01','2026-11-30','pausado',     16,  22),
('Machine Learning Ventas',    'Prediccion de demanda.',                                                                            '2025-04-01','2026-09-30','en_curso',    17,  40),
('Gestion de Certificados',    'Emision y validacion digital.',                                                                     '2025-10-15','2027-03-31','planificacion',18,  0),
('API Movilidad',              'Servicios para app de transporte.',                                                                  '2025-06-20','2026-12-31','en_curso',    19,  35),
('Sistema de Permisos v2',     'Refactor con microservicios.',                                                                      '2025-03-15','2026-08-15','finalizado',  20, 100),
('Analitica Predictiva',       'Modelos forecasting ventas.',                                                                       '2025-08-01','2027-01-31','pausado',      6,  10),
('Blockchain Identidad',       'Verificacion de identidad descentralizada.',                                                         '2025-05-15','2026-11-15','planificacion', 7,  0),
('Sistema de Capacitacion',    'Plataforma e-learning.',                                                                             '2025-09-10','2027-04-30','en_curso',     8,  25),
('Gestion Documental v2',      'ECM con IA.',                                                                                        '2025-01-25','2026-07-15','en_curso',     9,  50),
('API Socios',                 'Integracion B2B.',                                                                                   '2025-10-01','2027-03-31','planificacion',10,  0),
('Automatizacion Legal v2',    'Smart contracts.',                                                                                  '2025-07-15','2026-12-31','pausado',     11,  18),
('Sistema de Inventario',      'Gestion de stock predictiva.',                                                                       '2025-04-10','2026-09-30','finalizado',  12, 100),
('Plataforma Streaming',       'Servicio interno de video.',                                                                         '2025-11-15','2027-06-30','planificacion',13,  0),
('Monitorizacion IoT',         'Sensores industriales.',                                                                            '2025-06-01','2026-11-30','en_curso',    14,  38),
('Gestion de Turnos v2',       'Optimizacion con algoritmos.',                                                                       '2025-08-15','2027-02-28','planificacion',15,  0),
('API Pagos',                  'Pasarela de pagos global.',                                                                           '2025-03-01','2026-08-15','en_curso',    16,  45),
('Sistema de Encuestas v2',    'Analytics avanzado.',                                                                                '2025-09-01','2026-12-31','pausado',     17,  20),
('Blockchain Trazabilidad',    'Supply chain tracking.',                                                                             '2025-05-20','2026-10-31','planificacion',18,  0),
('Gestion de Convenios',       'CRM de partners.',                                                                                   '2025-10-10','2027-05-31','en_curso',    19,  30),
('Realidad Virtual',           'Prototipo capacitacion.',                                                                            '2025-07-01','2027-02-28','pausado',     20,   8),
('Sistema de Nominas v2',      'Nomina multi-pais.',                                                                                 '2025-04-15','2026-09-30','en_curso',     6,  35),
('App de Gastos v2',           'OCR para tickets.',                                                                                  '2025-11-01','2027-04-30','planificacion', 7,  0),
('Data Governance',            'Gobierno de datos.',                                                                                 '2025-06-10','2026-12-15','en_curso',     8,  40),
('Sistema de Reclutamiento v2','Video entrevistas.',                                                                                 '2025-08-20','2027-03-31','planificacion', 9,  0),
('API Open Banking',           'Open Finance.',                                                                                      '2025-02-10','2026-07-31','finalizado',  10, 100),
('Gamificacion RRHH',          'Engagement medido.',                                                                                 '2025-10-15','2027-05-31','pausado',     11,  15),
('Gestion de Riesgos v2',      'Ciberriesgos.',                                                                                      '2025-05-01','2026-10-15','en_curso',    12,  28),
('Portal del Cliente v2',      'Omnicanalidad.',                                                                                     '2025-09-15','2027-02-28','planificacion',13,  0),
('Sistema de Incidencias v2',  'IA para clasificacion.',                                                                             '2025-03-20','2026-09-15','en_curso',    14,  42),
('Blockchain Logistica v2',    'NFT envios.',                                                                                        '2025-07-10','2026-12-31','pausado',     15,  12),
('Analitica Desempeno',        'KPIs empleados.',                                                                                    '2025-04-01','2026-09-30','en_curso',    16,  35),
('API Gobierno',               'Datos abiertos.',                                                                                    '2025-11-10','2027-06-30','planificacion',17,  0),
('Sistema de Permisos v3',     'Low-code.',                                                                                         '2025-06-15','2026-11-30','finalizado',  18, 100),
('Monitorizacion SAP',         'Monitoring ERP.',                                                                                    '2025-08-01','2027-01-31','en_curso',    19,  25),
('Gestion de Proyectos v2',    'SAFe a escala.',                                                                                     '2025-05-15','2026-11-15','pausado',     20,  18),
('Plataforma Innovacion',      'Gestion de ideas.',                                                                                  '2025-10-01','2027-04-30','planificacion', 6,  0),
('Automatizacion RPA v2',      'Orchestrator.',                                                                                     '2025-03-10','2026-08-15','en_curso',     7,  38),
('Sistema de Nominas v3',      'Multi-entidad.',                                                                                    '2025-09-01','2027-02-28','planificacion', 8,  0),
('API Partners v2',            'GraphQL.',                                                                                          '2025-07-15','2026-12-31','pausado',      9,  15),
('Gestion de Activos v2',      'IoT + Blockchain.',                                                                                  '2025-04-20','2026-10-15','en_curso',    10,  30),
('Realidad Mixta',             'Hologramas oficina.',                                                                                '2025-11-15','2027-06-30','planificacion',11,  0),
('Sistema de Cobros v2',       'BNPL interno.',                                                                                     '2025-06-01','2026-11-30','finalizado',  12, 100),
('Data Lake v2',               'Lakehouse.',                                                                                        '2025-08-10','2027-02-28','pausado',     13,  10),
('API Social',                 'Graph social enterprise.',                                                                            '2025-05-01','2026-10-15','planificacion',14,  0),
('Gamificacion Ventas',        'Leaderboards.',                                                                                      '2025-10-01','2027-03-31','en_curso',    15,  22),
('Gestion de Certificados v2', 'W3C Verifiable Credentials.',                                                                       '2025-07-01','2026-12-31','pausado',     16,  12),
('Sistema de Inventario v2',   'RFID.',                                                                                              '2025-03-15','2026-09-15','finalizado',  17, 100),
('Plataforma de Eventos v2',   'Hibrida.',                                                                                          '2025-09-15','2027-04-30','planificacion',18,  0),
('Monitorizacion Green',       'Huella carbono TI.',                                                                                 '2025-06-20','2026-12-15','en_curso',    19,  35),
('API Ambiental',              'Datos medioambientales.',                                                                            '2025-11-01','2027-05-31','planificacion',20,  0),
('Sistema de Turnos v3',       'IA optimize.',                                                                                      '2025-04-10','2026-10-15','pausado',      6,   8),
('Blockchain Energia',         'P2P electrica.',                                                                                    '2025-08-15','2027-02-28','planificacion', 7,  0),
('Gestion de Convenios v2',    'Blockchain contratos.',                                                                             '2025-05-20','2026-11-15','en_curso',     8,  28),
('Realidad Virtual v2',        'Metaverso oficina.',                                                                                '2025-10-10','2027-05-31','pausado',      9,   5),
('Sistema de Nominas v4',      'Real-time payroll.',                                                                                '2025-07-01','2026-12-31','finalizado',  10, 100),
('App de Gastos v3',           'Cripto gastos.',                                                                                    '2025-03-01','2026-08-15','en_curso',    11,  40),
('Data Governance v2',         'Data mesh.',                                                                                        '2025-09-10','2027-03-31','planificacion',12,  0),
('API Fintech',                'Open banking avanzado.',                                                                            '2025-06-15','2026-11-30','pausado',     13,  15),
('Gamificacion Clientes',      'Fidelizacion.',                                                                                      '2025-11-15','2027-06-30','planificacion',14,  0),
('Sistema de Incidencias v3',  'Voicebot.',                                                                                         '2025-04-20','2026-10-15','en_curso',    15,  32),
('Blockchain Inmobiliario',    'Tokenizacion.',                                                                                      '2025-08-01','2027-01-31','pausado',     16,   6),
('Analitica Predictiva v2',    'Gemelos digitales.',                                                                                '2025-05-10','2026-10-31','planificacion',17,  0),
('API Agricola',               'Agritech.',                                                                                         '2025-10-01','2027-04-30','en_curso',    18,  22),
('Gestion de Certificados v3', 'NFT certificados.',                                                                                  '2025-07-15','2026-12-31','finalizado',  19, 100),
('Plataforma de Eventos v3',   'NFT entradas.',                                                                                      '2025-03-10','2026-09-15','pausado',     20,  10),
('Monetizacion Datos',         'Data marketplace.',                                                                                  '2025-09-01','2027-02-28','planificacion', 6,  0),
('Sistema de Turnos v4',       'Blockchain turnos.',                                                                                 '2025-06-01','2026-11-15','en_curso',     7,  18),
('API Quantum',                'Computacion cuantica.',                                                                              '2025-11-10','2027-06-30','planificacion', 8,  0),
('Gestion de Activos v3',      'Gemelos digitales.',                                                                                '2025-04-15','2026-10-15','pausado',      9,   5),
('Realidad Aumentada v2',      'Industrial metaverse.',                                                                             '2025-08-20','2027-03-31','planificacion',10,  0),
('Sistema de Cobros v3',       'CBDC.',                                                                                             '2025-05-01','2026-10-31','en_curso',    11,  25),
('Data Lake v3',               'Z Lake.',                                                                                           '2025-10-15','2027-05-31','pausado',     12,  10),
('API Espacial',               'Satelite data.',                                                                                    '2025-07-10','2026-12-31','planificacion',13,  0),
('Gamificacion Salud',         'Wellness digital.',                                                                                  '2025-03-15','2026-09-15','finalizado',  14, 100),
('Sistema de Inventario v3',   'Drones inventario.',                                                                                '2025-09-10','2027-04-30','planificacion',15,  0),
('Blockchain Logistica v3',    'Trazabilidad tiempo real.',                                                                         '2025-06-20','2026-12-15','pausado',     16,  12),
('Analitica Desempeno v2',     'People analytics.',                                                                                 '2025-11-01','2027-05-31','en_curso',    17,  20),
('API Biotech',                'Genomica.',                                                                                          '2025-04-10','2026-10-15','planificacion',18,  0),
('Gestion de Riesgos v3',      'Quantum risk.',                                                                                      '2025-08-15','2027-02-28','pausado',     19,   8),
('Portal Gobierno v2',         'AI transparency.',                                                                                   '2025-05-15','2026-11-15','en_curso',    20,  30);