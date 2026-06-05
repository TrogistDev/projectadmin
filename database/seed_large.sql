-- ============================================
-- SEED: Dados aleatórios para teste de carga
-- 1000 usuários, ~80 projetos, fases e membros
-- ============================================

-- 1. Limpa dados atuais (mantém estrutura)
SET FOREIGN_KEY_CHECKS = 0;

TRUNCATE TABLE proyecto_miembro;
TRUNCATE TABLE fases;
TRUNCATE TABLE proyectos;
TRUNCATE TABLE usuarios;

SET FOREIGN_KEY_CHECKS = 1;

-- 2. Hash de senha padrão: "password123" (bcrypt)
-- Para gerar: echo -n "password123" | openssl passwd -6 -stdin
-- Hash: $2y$10$abcdefghijklmnopqrstuvwxyz0123456789ABCD
SET @pw_hash = '$2y$10$abcdefghijklmnopqrstuvwxyz0123456789ABCD';

-- 3. INSERT: 1000 usuários com distribuição realista
-- 5 administradores, 20 jefes de proyecto, 975 colaboradores

INSERT INTO usuarios (nombre, apellidos, correo, contrasena, rol, departamento) VALUES
('Admin', 'Sistema', 'admin@empresa.test', @pw_hash, 'administrador', 'TI'),
('Maria', 'García', 'maria.garcia@empresa.test', @pw_hash, 'administrador', 'RRHH'),
('Carlos', 'López', 'carlos.lopez@empresa.test', @pw_hash, 'administrador', 'Finanzas'),
('Ana', 'Martínez', 'ana.martinez@empresa.test', @pw_hash, 'administrador', 'Operaciones'),
('Pedro', 'Rodríguez', 'pedro.rodriguez@empresa.test', @pw_hash, 'administrador', 'Legal');

-- Gerar 20 jefes de proyecto
INSERT INTO usuarios (nombre, apellidos, correo, contrasena, rol, departamento) VALUES
('Laura', 'Hernández', 'laura.hernandez@empresa.test', @pw_hash, 'jefe_proyecto', 'TI'),
('Diego', 'Gómez', 'diego.gomez@empresa.test', @pw_hash, 'jefe_proyecto', 'Marketing'),
('Sofía', 'Díaz', 'sofia.diaz@empresa.test', @pw_hash, 'jefe_proyecto', 'Desarrollo'),
('Javier', 'Muñoz', 'javier.munoz@empresa.test', @pw_hash, 'jefe_proyecto', 'QA'),
('Elena', 'Torres', 'elena.torres@empresa.test', @pw_hash, 'jefe_proyecto', 'Diseño'),
('Miguel', 'Vargas', 'miguel.vargas@empresa.test', @pw_hash, 'jefe_proyecto', 'Infraestructura'),
('Paula', 'Rojas', 'paula.rojas@empresa.test', @pw_hash, 'jefe_proyecto', 'Bases de Datos'),
('Andrés', 'Flores', 'andres.flores@empresa.test', @pw_hash, 'jefe_proyecto', 'Seguridad'),
('Natalia', 'Reyes', 'natalia.reyes@empresa.test', @pw_hash, 'jefe_proyecto', 'Movil'),
('Ricardo', 'Mendoza', 'ricardo.mendoza@empresa.test', @pw_hash, 'jefe_proyecto', 'Cloud'),
('Carmen', 'Sánchez', 'carmen.sanchez@empresa.test', @pw_hash, 'jefe_proyecto', 'Soporte'),
('Fernando', 'Morales', 'fernando.morales@empresa.test', @pw_hash, 'jefe_proyecto', 'Arquitectura'),
('Isabel', 'Gutiérrez', 'isabel.gutierrez@empresa.test', @pw_hash, 'jefe_proyecto', 'BI'),
('Roberto', 'Castro', 'roberto.castro@empresa.test', @pw_hash, 'jefe_proyecto', 'DevOps'),
('Lucía', 'Ortiz', 'lucia.ortiz@empresa.test', @pw_hash, 'jefe_proyecto', 'Frontend'),
('Daniel', 'Jiménez', 'daniel.jimenez@empresa.test', @pw_hash, 'jefe_proyecto', 'Backend'),
('Mónica', 'Álvarez', 'monica.alvarez@empresa.test', @pw_hash, 'jefe_proyecto', 'Calidad'),
('Pablo', 'Ruiz', 'pablo.ruiz@empresa.test', @pw_hash, 'jefe_proyecto', 'UX/UI'),
('Silvia', 'Navarro', 'silvia.navarro@empresa.test', @pw_hash, 'jefe_proyecto', 'Data Science'),
('Héctor', 'Domínguez', 'hector.dominguez@empresa.test', @pw_hash, 'jefe_proyecto', 'Integraciones');

-- Gerar 975 colaboradores com nomes e departamentos variados
INSERT INTO usuarios (nombre, apellidos, correo, contrasena, rol, departamento)
SELECT 
    CONCAT('Colaborador', @i := @i + 1) AS nombre,
    CONCAT('Apellido', @i) AS apellidos,
    CONCAT('colab', @i, '@empresa.test') AS correo,
    @pw_hash AS contrasena,
    'colaborador' AS rol,
    ELT(FLOOR(1 + RAND() * 12), 
        'TI', 'Desarrollo', 'QA', 'Marketing', 'Finanzas', 'RRHH', 
        'Operaciones', 'Diseño', 'Legal', 'Soporte', 'Infraestructura', 'Data') AS departamento
FROM (SELECT @i := 0) init, 
     (SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5 UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9 UNION SELECT 10) a,
     (SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5 UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9 UNION SELECT 10) b,
     (SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5 UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9) c;

-- IDs: 1-25 são admins/jefes, 26-1000 são colaboradores

-- 4. INSERT: 80 projetos com estados variados
INSERT INTO proyectos (nombre, descripcion, fecha_inicio, fecha_entrega, estado, responsable_id, porcentaje_avance) VALUES
('Portal Corporativo v2', 'Rediseño completo del portal interno con nuevas funcionalidades.', '2025-01-15', '2026-06-30', 'en_curso', 6, 35),
('Migración a la Nube', 'Migrar toda la infraestructura a AWS.', '2025-03-01', '2026-09-15', 'en_curso', 10, 50),
('App Móvil Empleados', 'Desarrollo de aplicación móvil para gestión de RRHH.', '2025-06-10', '2026-12-20', 'planificacion', 7, 0),
('Sistema de Facturación', 'Nuevo motor de facturación electrónica.', '2025-02-20', '2026-05-30', 'finalizado', 8, 100),
('Intranet Social', 'Red social interna para colaboradores.', '2025-08-01', '2026-10-15', 'pausado', 9, 20),
('Pipeline CI/CD', 'Automatización de deploys y testing.', '2025-04-15', '2026-07-30', 'en_curso', 10, 60),
('Dashboard Financiero', 'Panel de KPIs para dirección financiera.', '2025-05-01', '2026-08-20', 'planificacion', 11, 0),
('API Gateway Unificado', 'Puerta de enlace única para todos los microservicios.', '2025-07-15', '2026-11-30', 'en_curso', 12, 40),
('Sistema de Reservas', 'Gestión de salas y recursos compartidos.', '2025-09-01', '2026-12-15', 'planificacion', 13, 0),
('Análisis Predictivo', 'Modelos ML para predicción de ventas.', '2025-01-20', '2026-06-30', 'en_curso', 14, 55),
('Refactor Legacy ERP', 'Modernización del módulo de pagos del ERP.', '2025-03-10', '2026-08-15', 'en_curso', 15, 30),
('Portal del Cliente', 'Self-service para clientes consultar facturas.', '2025-10-01', '2027-01-30', 'planificacion', 6, 0),
('Chatbot Soporte Nivel 1', 'Asistente virtual para atención inicial.', '2025-06-15', '2026-10-30', 'en_curso', 16, 45),
('Automatización Reportes', 'Generación automática de reportes mensuales.', '2025-04-01', '2026-07-15', 'finalizado', 17, 100),
('Gestión Documental', 'Sistema de manejo de documentos legales.', '2025-11-01', '2027-03-31', 'planificacion', 18, 0),
('Monitorización 24/7', 'Infraestructura de monitoreo y alertas.', '2025-02-01', '2026-05-15', 'finalizado', 19, 100),
('Onboarding Digital', 'Proceso de incorporación 100% digital.', '2025-07-01', '2026-09-30', 'pausado', 20, 25),
('Integración con Proveedores', 'API para intercambio con cadena de suministro.', '2025-05-15', '2026-10-15', 'en_curso', 11, 35),
('Plataforma E-learning', 'Campus virtual para capacitación interna.', '2025-08-15', '2027-02-28', 'planificacion', 12, 0),
('Sistema de Incidencias', 'Ticketing avanzado con SLA.', '2025-03-20', '2026-08-30', 'en_curso', 13, 50),
('Migración Datos Históricos', 'Limpieza y migración a data warehouse.', '2025-01-10', '2026-04-30', 'finalizado', 14, 100),
('App de Reuniones', 'Videoconferencia integrada con calendario.', '2025-09-15', '2026-12-31', 'pausado', 15, 15),
('Motor de ReglasNegocio', 'Motor configurable para flujos internos.', '2025-06-01', '2026-11-15', 'en_curso', 16, 40),
('Certificaciones ISO', 'Sistema de gestión de certificaciones.', '2025-04-10', '2026-09-30', 'planificacion', 17, 0),
('Backup Automatizado', 'Estrategia de respaldo y recuperación.', '2025-02-15', '2026-06-15', 'finalizado', 18, 100),
('Inventario Inteligente', 'Predicción de stock con IA.', '2025-10-15', '2027-04-30', 'planificacion', 19, 0),
('Portal de Proveedores', 'Extranet para gestión de proveedores.', '2025-07-20', '2026-12-15', 'pausado', 20, 20),
('Automatización Contable', 'Cierre contable automático.', '2025-05-01', '2026-10-31', 'en_curso', 6, 35),
('Sistema de Turnos', 'Gestión de turnos y horarios laborales.', '2025-08-01', '2027-01-31', 'planificacion', 7, 0),
('Firma Digital', 'Implementación de firma electrónica.', '2025-03-15', '2026-07-31', 'en_curso', 8, 55),
('Business Intelligence', 'Plataforma unificada de analítica.', '2025-01-25', '2026-08-15', 'en_curso', 9, 60),
('Gamificación Procesos', 'Sistema de incentivos y logros.', '2025-09-01', '2026-11-30', 'pausado', 10, 10),
('Gestión de Riesgos', 'Matriz de riesgos y planes de contingencia.', '2025-04-20', '2026-09-15', 'planificacion', 11, 0),
('API Pública', 'Exposición de servicios para partners.', '2025-06-15', '2026-12-31', 'en_curso', 12, 30),
('Central de Comunicados', 'Gestión de comunicados internos.', '2025-02-10', '2026-05-31', 'finalizado', 13, 100),
('Sistema de Encuestas', 'Encuestas pulse y clima laboral.', '2025-10-01', '2027-03-15', 'planificacion', 14, 0),
('Monitorización Red', 'Análisis de tráfico y seguridad perimetral.', '2025-07-10', '2026-11-30', 'en_curso', 15, 45),
('Robotización RPA', 'Automatización de procesos repetitivos.', '2025-05-20', '2026-10-15', 'pausado', 16, 22),
('Gestión de Vacaciones', 'Sistema de solicitudes y aprobaciones.', '2025-03-01', '2026-07-15', 'finalizado', 17, 100),
('Plataforma de Eventos', 'Organización interna de eventos.', '2025-11-01', '2027-02-28', 'planificacion', 18, 0),
('Data Lake Corporativo', 'Repositorio unificado de datos.', '2025-08-20', '2027-04-30', 'en_curso', 19, 25),
('Sistema de Nóminas', 'Cálculo automatizado de nóminas.', '2025-04-05', '2026-09-30', 'en_curso', 20, 40),
('App de Gastos', 'Registro y aprobación de gastos.', '2025-09-10', '2026-12-31', 'pausado', 6, 18),
('Certificados Digitales', 'Emisión automática de certificados.', '2025-06-20', '2026-10-31', 'planificacion', 7, 0),
('Machine Learning Ops', 'Infraestructura para modelos ML.', '2025-01-15', '2026-07-15', 'en_curso', 8, 50),
('Central de Pedidos', 'Gestión centralizada de pedidos internos.', '2025-10-10', '2027-05-31', 'planificacion', 9, 0),
('Sistema de Auditoría', 'Tracking automático de cambios.', '2025-05-15', '2026-11-15', 'en_curso', 10, 35),
('Portal Bienestar', 'Programa de bienestar laboral digital.', '2025-07-01', '2026-12-31', 'pausado', 11, 12),
('Automatización Legal', 'Generación de contratos y documentos.', '2025-03-10', '2026-08-15', 'planificacion', 12, 0),
('Gestión de Activos', 'Inventario y mantenimiento de equipos.', '2025-08-15', '2027-01-31', 'en_curso', 13, 30),
('API Interna Unificada', 'Consolidación de APIs internas.', '2025-04-25', '2026-10-15', 'en_curso', 14, 42),
('Sistema de Permisos', 'Flujo digital de aprobaciones.', '2025-02-20', '2026-06-30', 'finalizado', 15, 100),
('Realidad Aumentada', 'Prototipo de mantenimiento con AR.', '2025-11-15', '2027-06-30', 'planificacion', 16, 0),
('Blockchain Proveedores', 'Trazabilidad de cadena de suministro.', '2025-09-01', '2027-03-31', 'pausado', 17, 8),
('Sistema de Reclutamiento', 'ATS con IA para selección de CVs.', '2025-06-01', '2026-11-30', 'en_curso', 18, 38),
('Gestión de Contratos', 'Lifecycle digital de contratos.', '2025-05-10', '2026-10-31', 'en_curso', 19, 28),
('Plataforma de Cursos', 'LMS con gamificación.', '2025-10-20', '2027-04-30', 'planificacion', 20, 0),
('AutomatizaciónQA', 'Framework de testing automatizado.', '2025-03-25', '2026-09-15', 'finalizado', 6, 100),
('Sistema de Fidelización', 'Programa de puntos para empleados.', '2025-07-15', '2026-12-31', 'pausado', 7, 15),
('Data Warehouse', 'Migración a nuevo DW corporativo.', '2025-01-20', '2026-06-15', 'finalizado', 8, 100),
('Gestión de Flotas', 'Control de vehículos y rutas.', '2025-08-10', '2027-02-28', 'planificacion', 9, 0),
('API Facturación', 'Endpoints para facturación electrónica.', '2025-04-15', '2026-09-30', 'en_curso', 10, 48),
('Sistema de Novedades', 'Comunicados y alertas push.', '2025-06-10', '2026-11-15', 'planificacion', 11, 0),
('Monitorización UX', 'Heatmaps y analytics de experiencias.', '2025-09-15', '2026-12-15', 'pausado', 12, 20),
('Automatización Almacén', 'WMS con robots autónomos.', '2025-05-01', '2026-10-15', 'en_curso', 13, 33),
('Gestión de Proyectos', 'Metodologías ágiles a escala.', '2025-02-15', '2026-07-31', 'finalizado', 14, 100),
('Portal Gobierno', 'Transparencia y rendición de cuentas.', '2025-11-01', '2027-05-31', 'planificacion', 15, 0),
('Sistema de Cobros', 'Pasarela de pagos integrada.', '2025-07-01', '2026-11-30', 'pausado', 16, 22),
('Machine Learning Ventas', 'Predicción de demanda.', '2025-04-01', '2026-09-30', 'en_curso', 17, 40),
('Gestión de Certificados', 'Emisión y validación digital.', '2025-10-15', '2027-03-31', 'planificacion', 18, 0),
('API Movilidad', 'Servicios para app de transporte.', '2025-06-20', '2026-12-31', 'en_curso', 19, 35),
('Sistema de Permisos v2', 'Refactor con microservicios.', '2025-03-15', '2026-08-15', 'finalizado', 20, 100),
('Analítica Predictiva', 'Modelos forecasting ventas.', '2025-08-01', '2027-01-31', 'pausado', 6, 10),
('Blockchain Identidad', 'Verificación de identidad descentralizada.', '2025-05-15', '2026-11-15', 'planificacion', 7, 0),
('Sistema de Capacitación', 'Plataforma e-learning.', '2025-09-10', '2027-04-30', 'en_curso', 8, 25),
('Gestión Documental v2', 'ECM con IA.', '2025-01-25', '2026-07-15', 'en_curso', 9, 50),
('API Socios', 'Integración B2B.', '2025-10-01', '2027-03-31', 'planificacion', 10, 0),
('Automatización Legal v2', 'Smart contracts.', '2025-07-15', '2026-12-31', 'pausado', 11, 18),
('Sistema de Inventario', 'Gestión de stock predictiva.', '2025-04-10', '2026-09-30', 'finalizado', 12, 100),
('Plataforma Streaming', 'Servicio interno de video.', '2025-11-15', '2027-06-30', 'planificacion', 13, 0),
('Monitorización IoT', 'Sensores industriales.', '2025-06-01', '2026-11-30', 'en_curso', 14, 38),
('Gestión de Turnos v2', 'Optimización con algoritmos.', '2025-08-15', '2027-02-28', 'planificacion', 15, 0),
('API Pagos', 'Pasarela de pagos global.', '2025-03-01', '2026-08-15', 'en_curso', 16, 45),
('Sistema de Encuestas v2', 'Analytics avanzado.', '2025-09-01', '2026-12-31', 'pausado', 17, 20),
('Blockchain Trazabilidad', 'Supply chain tracking.', '2025-05-20', '2026-10-31', 'planificacion', 18, 0),
('Gestión de Convenios', 'CRM de partners.', '2025-10-10', '2027-05-31', 'en_curso', 19, 30),
('Realidad Virtual', 'Prototipo capacitación.', '2025-07-01', '2027-02-28', 'pausado', 20, 8),
('Sistema de Nóminas v2', 'Nómina multi-país.', '2025-04-15', '2026-09-30', 'en_curso', 6, 35),
('App de Gastos v2', 'OCR para tickets.', '2025-11-01', '2027-04-30', 'planificacion', 7, 0),
('Data Governance', 'Gobierno de datos.', '2025-06-10', '2026-12-15', 'en_curso', 8, 40),
('Sistema de Reclutamiento v2', 'Video entrevistas.', '2025-08-20', '2027-03-31', 'planificacion', 9, 0),
('API Open Banking', 'Open Finance.', '2025-02-10', '2026-07-31', 'finalizado', 10, 100),
('Gamificación RRHH', 'Engagement medido.', '2025-10-15', '2027-05-31', 'pausado', 11, 15),
('Gestión de Riesgos v2', 'Ciberriesgos.', '2025-05-01', '2026-10-15', 'en_curso', 12, 28),
('Portal del Cliente v2', 'Omnicanalidad.', '2025-09-15', '2027-02-28', 'planificacion', 13, 0),
('Sistema de Incidencias v2', 'IA para clasificación.', '2025-03-20', '2026-09-15', 'en_curso', 14, 42),
('Blockchain Logística', 'Trazabilidad tiempo real.', '2025-07-10', '2026-12-31', 'pausado', 15, 12),
('Analítica Desempeño', 'KPIs empleados.', '2025-04-01', '2026-09-30', 'en_curso', 16, 35),
('API Gobierno', 'Datos abiertos.', '2025-11-10', '2027-06-30', 'planificacion', 17, 0),
('Sistema de Permisos v3', 'Low-code.', '2025-06-15', '2026-11-30', 'finalizado', 18, 100),
('Monitorización SAP', 'Monitoring ERP.', '2025-08-01', '2027-01-31', 'en_curso', 19, 25),
('Gestión de Proyectos v2', 'SAFe a escala.', '2025-05-15', '2026-11-15', 'pausado', 20, 18),
('Plataforma Innovación', 'Gestión de ideas.', '2025-10-01', '2027-04-30', 'planificacion', 6, 0),
('Automatización RPA v2', 'Orchestrator.', '2025-03-10', '2026-08-15', 'en_curso', 7, 38),
('Sistema de Nominas v3', 'Multi-entidad.', '2025-09-01', '2027-02-28', 'planificacion', 8, 0),
('API Partners v2', 'GraphQL.', '2025-07-15', '2026-12-31', 'pausado', 9, 15),
('Gestión de Activos v2', 'IoT + Blockchain.', '2025-04-20', '2026-10-15', 'en_curso', 10, 30),
('Realidad Mixta', 'Hologramas oficina.', '2025-11-15', '2027-06-30', 'planificacion', 11, 0),
('Sistema de Cobros v2', 'BNPL interno.', '2025-06-01', '2026-11-30', 'finalizado', 12, 100),
('Data Lake v2', 'Lakehouse.', '2025-08-10', '2027-02-28', 'pausado', 13, 10),
('API Social', 'Graph social enterprise.', '2025-05-01', '2026-10-15', 'planificacion', 14, 0),
('Gamificación Ventas', 'Leaderboards.', '2025-10-01', '2027-03-31', 'en_curso', 15, 22),
('Gestión de Certificados v2', 'W3C Verifiable Credentials.', '2025-07-01', '2026-12-31', 'pausado', 16, 12),
('Sistema de Inventario v2', 'RFID.', '2025-03-15', '2026-09-15', 'finalizado', 17, 100),
('Plataforma de Eventos v2', 'Híbrida.', '2025-09-15', '2027-04-30', 'planificacion', 18, 0),
('Monitorización Green', 'Huella carbono TI.', '2025-06-20', '2026-12-15', 'en_curso', 19, 35),
('API Ambiental', 'Datos medioambientales.', '2025-11-01', '2027-05-31', 'planificacion', 20, 0),
('Sistema de Turnos v3', 'IA optimize.', '2025-04-10', '2026-10-15', 'pausado', 6, 8),
('Blockchain Energía', 'P2P eléctrica.', '2025-08-15', '2027-02-28', 'planificacion', 7, 0),
('Gestión de Convenios v2', 'Blockchain contratos.', '2025-05-20', '2026-11-15', 'en_curso', 8, 28),
('Realidad Virtual v2', 'Metaverso oficina.', '2025-10-10', '2027-05-31', 'pausado', 9, 5),
('Sistema de Nóminas v4', 'Real-time payroll.', '2025-07-01', '2026-12-31', 'finalizado', 10, 100),
('App de Gastos v3', 'Cripto gastos.', '2025-03-01', '2026-08-15', 'en_curso', 11, 40),
('Data Governance v2', 'Data mesh.', '2025-09-10', '2027-03-31', 'planificacion', 12, 0),
('API Fintech', 'Open banking avanzado.', '2025-06-15', '2026-11-30', 'pausado', 13, 15),
('Gamificación Clientes', 'Fidelización.', '2025-11-15', '2027-06-30', 'planificacion', 14, 0),
('Sistema de Incidencias v3', 'Voicebot.', '2025-04-20', '2026-10-15', 'en_curso', 15, 32),
('Blockchain Inmobiliario', 'Tokenización.', '2025-08-01', '2027-01-31', 'pausado', 16, 6),
('Analítica Predictiva v2', 'Gemelos digitales.', '2025-05-10', '2026-10-31', 'planificacion', 17, 0),
('API Agrícola', 'Agritech.', '2025-10-01', '2027-04-30', 'en_curso', 18, 22),
('Gestión de Certificados v3', 'NFT certificados.', '2025-07-15', '2026-12-31', 'finalizado', 19, 100),
('Plataforma de Eventos v3', 'NFT entradas.', '2025-03-10', '2026-09-15', 'pausado', 20, 10),
('Monetización Datos', 'Data marketplace.', '2025-09-01', '2027-02-28', 'planificacion', 6, 0),
('Sistema de Turnos v4', 'Blockchain turnos.', '2025-06-01', '2026-11-15', 'en_curso', 7, 18),
('API Quantum', 'Computación cuántica.', '2025-11-10', '2027-06-30', 'planificacion', 8, 0),
('Gestión de Activos v3', 'Gemelos digitales.', '2025-04-15', '2026-10-15', 'pausado', 9, 5),
('Realidad Aumentada v2', 'Industrial metaverse.', '2025-08-20', '2027-03-31', 'planificacion', 10, 0),
('Sistema de Cobros v3', 'CBDC.', '2025-05-01', '2026-10-31', 'en_curso', 11, 25),
('Data Lake v3', 'Z Lake.', '2025-10-15', '2027-05-31', 'pausado', 12, 10),
('API Espacial', 'Satélite data.', '2025-07-10', '2026-12-31', 'planificacion', 13, 0),
('Gamificación Salud', 'Wellness digital.', '2025-03-15', '2026-09-15', 'finalizado', 14, 100),
('Sistema de Inventario v3', 'Drones inventario.', '2025-09-10', '2027-04-30', 'planificacion', 15, 0),
('Blockchain Logística v2', 'NFT envíos.', '2025-06-20', '2026-12-15', 'pausado', 16, 12),
('Analítica Desempeño v2', 'People analytics.', '2025-11-01', '2027-05-31', 'en_curso', 17, 20),
('API Biotech', 'Genómica.', '2025-04-10', '2026-10-15', 'planificacion', 18, 0),
('Gestión de Riesgos v3', 'Quantum risk.', '2025-08-15', '2027-02-28', 'pausado', 19, 8),
('Portal Gobierno v2', 'AI transparency.', '2025-05-15', '2026-11-15', 'en_curso', 20, 30);

-- 5. INSERT: fases para cada projeto (3-6 fases por projeto, ordens corretas)
INSERT INTO fases (nombre, descripcion, orden, completada, fecha_completado, proyecto_id)
SELECT 
    CONCAT('Fase ', f.orden, ': ', p.nombre) AS nombre,
    CONCAT('Descripción de la fase ', f.orden, ' del proyecto ', p.nombre) AS descripcion,
    f.orden,
    CASE 
        WHEN p.estado = 'finalizado' THEN 1
        WHEN p.estado = 'en_curso' AND f.orden <= 2 THEN 1
        WHEN p.estado = 'pausado' AND f.orden = 1 THEN 1
        ELSE 0
    END AS completada,
    CASE 
        WHEN p.estado IN ('finalizado', 'en_curso') AND f.orden <= 2 
        THEN DATE_ADD(p.fecha_inicio, INTERVAL f.orden * 30 DAY)
        WHEN p.estado = 'pausado' AND f.orden = 1
        THEN DATE_ADD(p.fecha_inicio, INTERVAL 15 DAY)
        ELSE NULL
    END AS fecha_completado,
    p.id
FROM proyectos p
JOIN (
    SELECT 1 AS orden UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5 UNION SELECT 6
) f ON f.orden <= 3 + (p.id % 4);

-- 6. INSERT: miembros de equipo (3-8 por projeto)
INSERT INTO proyecto_miembro (proyecto_id, usuario_id, fecha_add, rol_especifico)
SELECT 
    p.id AS proyecto_id,
    u.id AS usuario_id,
    DATE_ADD(p.fecha_creacion, INTERVAL (u.id % 30) DAY) AS fecha_add,
    ELT(FLOOR(1 + RAND() * 5), 'Desarrollador', 'Analista', 'Tester', 'Diseñador', 'Scrum Master') AS rol_especifico
FROM proyectos p
JOIN usuarios u ON u.rol = 'colaborador'
WHERE u.id BETWEEN 21 AND 1000
  AND (p.id + u.id) % 7 BETWEEN 0 AND 2
LIMIT 500;

-- 7. Ajustar responsable como miembro del proyecto (jefe)
INSERT IGNORE INTO proyecto_miembro (proyecto_id, usuario_id, fecha_add, rol_especifico)
SELECT 
    p.id,
    p.responsable_id,
    p.fecha_creacion,
    'Jefe de Proyecto'
FROM proyectos p;

COMMIT;

-- 8. Recalcular porcentajes y estados finales
UPDATE proyectos p
SET porcentaje_avance = (
    SELECT ROUND(COUNT(f.id_completadas) * 100 / COUNT(f.id))
    FROM fases f
    WHERE f.proyecto_id = p.id
      AND f.completada = 1
)
WHERE EXISTS (SELECT 1 FROM fases f WHERE f.proyecto_id = p.id);
