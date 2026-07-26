-- ============================================================
-- SISTEMA DE GESTIÓN HOTELERA
-- Script SQL completo - MySQL 8+
-- Basado en el diagrama Entidad-Relación "HOTEL":
-- Entidades: TIPO_HABITACION, HABITACION, CLIENTE,
--            RESERVA, GASTOS, SERVICIOS
-- Ejecutar directamente en DBeaver conectado a MySQL (XAMPP)
-- ============================================================

DROP DATABASE IF EXISTS hotel_db;
CREATE DATABASE hotel_db
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE hotel_db;

-- ------------------------------------------------------------
-- TABLA: tipo_habitacion
-- Catálogo de tipos de habitación (individual, doble, suite...)
-- ------------------------------------------------------------
CREATE TABLE tipo_habitacion (
    id_tipo_habitacion INT AUTO_INCREMENT PRIMARY KEY,
    nombre             VARCHAR(50)     NOT NULL,
    descripcion        VARCHAR(255)    NULL,
    capacidad          TINYINT         NOT NULL DEFAULT 1,
    precio_base        DECIMAL(10,2)   NOT NULL,
    CONSTRAINT chk_tipo_hab_precio CHECK (precio_base >= 0)
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- TABLA: habitacion
-- Habitaciones físicas del hotel.
-- 1 TIPO_HABITACION : N HABITACION
-- ------------------------------------------------------------
CREATE TABLE habitacion (
    id_habitacion      INT AUTO_INCREMENT PRIMARY KEY,
    numero             VARCHAR(10)     NOT NULL,
    planta             TINYINT         NOT NULL,
    estado             ENUM('disponible','ocupada','mantenimiento') NOT NULL DEFAULT 'disponible',
    id_tipo_habitacion INT             NOT NULL,
    CONSTRAINT fk_habitacion_tipo
        FOREIGN KEY (id_tipo_habitacion) REFERENCES tipo_habitacion(id_tipo_habitacion)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT uq_habitacion_numero UNIQUE (numero)
) ENGINE=InnoDB;

CREATE INDEX idx_habitacion_tipo ON habitacion(id_tipo_habitacion);

-- ------------------------------------------------------------
-- TABLA: cliente
-- Personas que realizan reservas.
-- ------------------------------------------------------------
CREATE TABLE cliente (
    id_cliente      INT AUTO_INCREMENT PRIMARY KEY,
    nombre          VARCHAR(80)     NOT NULL,
    apellido        VARCHAR(80)     NOT NULL,
    dni             VARCHAR(20)     NOT NULL,
    direccion       VARCHAR(150)    NULL,
    telefono        VARCHAR(20)     NOT NULL,
    email           VARCHAR(120)    NULL,
    CONSTRAINT uq_cliente_dni UNIQUE (dni)
) ENGINE=InnoDB;

CREATE INDEX idx_cliente_apellido ON cliente(apellido);

-- ------------------------------------------------------------
-- TABLA: reserva
-- Reserva de una habitación por un cliente.
-- 1 CLIENTE : N RESERVA
-- 1 HABITACION : N RESERVA
-- ------------------------------------------------------------
CREATE TABLE reserva (
    id_reserva      INT AUTO_INCREMENT PRIMARY KEY,
    id_cliente      INT             NOT NULL,
    id_habitacion   INT             NOT NULL,
    fecha_reserva   DATE            NOT NULL DEFAULT (CURRENT_DATE),
    fecha_entrada   DATE            NOT NULL,
    fecha_salida    DATE            NOT NULL,
    estado          ENUM('pendiente','confirmada','cancelada','finalizada') NOT NULL DEFAULT 'pendiente',
    CONSTRAINT fk_reserva_cliente
        FOREIGN KEY (id_cliente) REFERENCES cliente(id_cliente)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_reserva_habitacion
        FOREIGN KEY (id_habitacion) REFERENCES habitacion(id_habitacion)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT chk_reserva_fechas CHECK (fecha_salida > fecha_entrada)
) ENGINE=InnoDB;

CREATE INDEX idx_reserva_cliente ON reserva(id_cliente);
CREATE INDEX idx_reserva_habitacion ON reserva(id_habitacion);
CREATE INDEX idx_reserva_fechas ON reserva(fecha_entrada, fecha_salida);

-- ------------------------------------------------------------
-- TABLA: servicios
-- Catálogo de servicios adicionales del hotel (spa, lavandería...)
-- ------------------------------------------------------------
CREATE TABLE servicios (
    id_servicio     INT AUTO_INCREMENT PRIMARY KEY,
    nombre          VARCHAR(80)     NOT NULL,
    descripcion     VARCHAR(255)    NULL,
    precio          DECIMAL(10,2)   NOT NULL,
    CONSTRAINT chk_servicio_precio CHECK (precio >= 0)
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- TABLA: reserva_servicios (tabla puente N:M)
-- Una RESERVA puede consumir varios SERVICIOS y un SERVICIO
-- puede ser consumido en varias RESERVAS.
-- ------------------------------------------------------------
CREATE TABLE reserva_servicios (
    id_reserva      INT             NOT NULL,
    id_servicio     INT             NOT NULL,
    cantidad        INT             NOT NULL DEFAULT 1,
    fecha_consumo   DATE            NOT NULL DEFAULT (CURRENT_DATE),
    PRIMARY KEY (id_reserva, id_servicio, fecha_consumo),
    CONSTRAINT fk_rs_reserva
        FOREIGN KEY (id_reserva) REFERENCES reserva(id_reserva)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_rs_servicio
        FOREIGN KEY (id_servicio) REFERENCES servicios(id_servicio)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT chk_rs_cantidad CHECK (cantidad > 0)
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- TABLA: gastos
-- Cargos generados sobre una reserva (habitación, servicios, extras)
-- 1 RESERVA : N GASTOS
-- ------------------------------------------------------------
CREATE TABLE gastos (
    id_gasto        INT AUTO_INCREMENT PRIMARY KEY,
    id_reserva      INT             NOT NULL,
    concepto        VARCHAR(120)    NOT NULL,
    monto           DECIMAL(10,2)   NOT NULL,
    fecha           DATE            NOT NULL DEFAULT (CURRENT_DATE),
    CONSTRAINT fk_gasto_reserva
        FOREIGN KEY (id_reserva) REFERENCES reserva(id_reserva)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT chk_gasto_monto CHECK (monto >= 0)
) ENGINE=InnoDB;

CREATE INDEX idx_gastos_reserva ON gastos(id_reserva);

-- ------------------------------------------------------------
-- DATOS DE PRUEBA
-- ------------------------------------------------------------
INSERT INTO tipo_habitacion (nombre, descripcion, capacidad, precio_base) VALUES
('Individual', 'Habitación para una persona', 1, 45.00),
('Doble', 'Habitación para dos personas', 2, 65.00),
('Suite', 'Habitación amplia con sala', 4, 120.00);

INSERT INTO habitacion (numero, planta, estado, id_tipo_habitacion) VALUES
('101', 1, 'disponible', 1),
('102', 1, 'disponible', 2),
('201', 2, 'disponible', 3),
('202', 2, 'disponible', 2);

INSERT INTO cliente (nombre, apellido, dni, direccion, telefono, email) VALUES
('Ana', 'Pérez', '1712345678', 'Calle 10 de Agosto', '0991234567', 'ana.perez@mail.com'),
('Luis', 'Gómez', '1798765432', 'Av. 6 de Diciembre', '0987654321', 'luis.gomez@mail.com');

INSERT INTO servicios (nombre, descripcion, precio) VALUES
('Lavandería', 'Servicio de lavado de ropa', 8.00),
('Spa', 'Acceso a spa y sauna', 25.00),
('Desayuno buffet', 'Desayuno incluido por día', 10.00);

INSERT INTO reserva (id_cliente, id_habitacion, fecha_entrada, fecha_salida, estado) VALUES
(1, 1, '2026-08-01', '2026-08-05', 'confirmada'),
(2, 3, '2026-08-10', '2026-08-12', 'pendiente');

INSERT INTO reserva_servicios (id_reserva, id_servicio, cantidad, fecha_consumo) VALUES
(1, 3, 4, '2026-08-01'),
(1, 2, 1, '2026-08-02');

INSERT INTO gastos (id_reserva, concepto, monto, fecha) VALUES
(1, 'Hospedaje habitación 101 (4 noches)', 180.00, '2026-08-01'),
(1, 'Consumo servicio: Desayuno buffet', 40.00, '2026-08-02'),
(1, 'Consumo servicio: Spa', 25.00, '2026-08-02');

-- ------------------------------------------------------------
-- TABLA: usuarios
-- Cuentas de acceso al panel administrativo (login/logout).
-- La contraseña se guarda hasheada con password_hash() de PHP
-- (bcrypt), nunca en texto plano.
-- ------------------------------------------------------------
CREATE TABLE usuarios (
    id_usuario       INT AUTO_INCREMENT PRIMARY KEY,
    nombre_usuario   VARCHAR(50)     NOT NULL,
    nombre_completo  VARCHAR(100)    NOT NULL,
    password_hash    VARCHAR(255)    NOT NULL,
    rol              ENUM('admin','recepcionista') NOT NULL DEFAULT 'recepcionista',
    activo           TINYINT(1)      NOT NULL DEFAULT 1,
    creado_en        DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT uq_usuarios_nombre_usuario UNIQUE (nombre_usuario)
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- USUARIO POR DEFECTO
-- Usuario: admin
-- Contraseña: admin123   (¡cámbiala después del primer ingreso!)
-- El hash ya viene generado con password_hash() (bcrypt),
-- así que puedes iniciar sesión apenas ejecutes este script.
-- ------------------------------------------------------------
INSERT INTO usuarios (nombre_usuario, nombre_completo, password_hash, rol, activo) VALUES
('admin', 'Administrador del Sistema', '$2y$10$wCPWPnT8nNs63TA6SkzqWezrTq1AQrGGbxKV1JVsr1DAaZslTiIfK', 'admin', 1);