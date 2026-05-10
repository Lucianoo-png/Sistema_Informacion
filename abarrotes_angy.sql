-- =====================================================
-- BASE DE DATOS: Abarrotes Angy — PostgreSQL
-- =====================================================
-- CAMBIOS APLICADOS vs versión MySQL original:
--   1. Motor: MySQL  →  PostgreSQL
--   2. AUTO_INCREMENT  →  SERIAL
--   3. ENUM('a','b')  →  CHECK (col IN ('a','b'))
--   4. Prefijo "Abarrotes." en TODAS las tablas
--   5. codigoprod: INT  →  VARCHAR(15) (llave de negocio)
--   6. CHECK de teléfono con expresión regular en proveedores
--   7. Nueva tabla cuenta  (sesión/login)
--   8. Nueva tabla bitacora (auditoría, basada en imagen)
-- =====================================================

-- 1. Crear base de datos y conectarse
CREATE DATABASE "Tienda";
\c Tienda;

-- 2. Crear esquema
CREATE SCHEMA IF NOT EXISTS "Abarrotes";

-- ── CUENTA  (autenticación) ────────────────────────
CREATE TABLE "Abarrotes".cuenta (
    ClaveCuenta CHAR(5)     PRIMARY KEY,
    Contrasena  CHAR(64)    NOT NULL,      -- SHA-256 en hex (64 chars)
    Nombre      VARCHAR(50) NOT NULL,
    Apellidos   VARCHAR(50) NOT NULL,
    activo      BOOLEAN     NOT NULL DEFAULT TRUE
);

-- ── PROVEEDORES ────────────────────────────────────
CREATE TABLE "Abarrotes".proveedores (
    id        SERIAL       PRIMARY KEY,
    nombre    VARCHAR(100) NOT NULL,
    telefono  CHAR(10)     NOT NULL
              CONSTRAINT chk_telefono CHECK (telefono ~ '^[0-9]{10}$'),
    DiaVisita VARCHAR(50)
);

-- ── PRODUCTOS  (inventario) ────────────────────────
CREATE TABLE "Abarrotes".productos (
    codigoprod    VARCHAR(15)  NOT NULL PRIMARY KEY,
    nombre        VARCHAR(100) NOT NULL,
    categoria     VARCHAR(50),
    precio_compra DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    precio_venta  DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    stock         INT           NOT NULL DEFAULT 0,
    stock_minimo  INT           NOT NULL DEFAULT 3,
    unidad        VARCHAR(20)   NOT NULL DEFAULT 'pieza',
    created_at    TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP
);

-- ── VENTAS ─────────────────────────────────────────
CREATE TABLE "Abarrotes".ventas (
    id          SERIAL        PRIMARY KEY,
    fecha       DATE          NOT NULL DEFAULT CURRENT_DATE,
    total       DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    metodo_pago VARCHAR(15)   NOT NULL DEFAULT 'efectivo'
                CONSTRAINT chk_metodo_venta
                    CHECK (metodo_pago IN ('efectivo','transferencia')),
    nota        TEXT,
    created_at  TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP
);

-- ── VENTA_DETALLE ──────────────────────────────────
CREATE TABLE "Abarrotes".venta_detalle (
    id              SERIAL        PRIMARY KEY,
    venta_id        INT           NOT NULL,
    codigoprod      VARCHAR(15)   NOT NULL,
    cantidad        INT           NOT NULL CHECK (cantidad > 0),
    precio_unitario DECIMAL(10,2) NOT NULL,
    subtotal        DECIMAL(10,2) NOT NULL,
    CONSTRAINT fk_vd_venta    FOREIGN KEY (venta_id)
        REFERENCES "Abarrotes".ventas(id) ON DELETE CASCADE,
    CONSTRAINT fk_vd_producto FOREIGN KEY (codigoprod)
        REFERENCES "Abarrotes".productos(codigoprod)
);

-- ── COMPRAS ────────────────────────────────────────
CREATE TABLE "Abarrotes".compras (
    id           SERIAL        PRIMARY KEY,
    fecha        DATE          NOT NULL DEFAULT CURRENT_DATE,
    proveedor_id INT           DEFAULT NULL,
    tipo         VARCHAR(10)   NOT NULL DEFAULT 'directa'
                 CONSTRAINT chk_tipo_compra
                     CHECK (tipo IN ('proveedor','directa')),
    total        DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    nota         TEXT,
    created_at   TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_compra_prov FOREIGN KEY (proveedor_id)
        REFERENCES "Abarrotes".proveedores(id) ON DELETE SET NULL
);

-- ── COMPRA_DETALLE ─────────────────────────────────
CREATE TABLE "Abarrotes".compra_detalle (
    id              SERIAL        PRIMARY KEY,
    compra_id       INT           NOT NULL,
    codigoprod      VARCHAR(15)   NOT NULL,
    cantidad        INT           NOT NULL CHECK (cantidad > 0),
    precio_unitario DECIMAL(10,2) NOT NULL,
    subtotal        DECIMAL(10,2) NOT NULL,
    CONSTRAINT fk_cd_compra   FOREIGN KEY (compra_id)
        REFERENCES "Abarrotes".compras(id) ON DELETE CASCADE,
    CONSTRAINT fk_cd_producto FOREIGN KEY (codigoprod)
        REFERENCES "Abarrotes".productos(codigoprod)
);

-- ── TRANSFERENCIAS ─────────────────────────────────
CREATE TABLE "Abarrotes".transferencias (
    id          SERIAL        PRIMARY KEY,
    fecha       DATE          NOT NULL DEFAULT CURRENT_DATE,
    monto       DECIMAL(10,2) NOT NULL CHECK (monto > 0),
    concepto    VARCHAR(200),
    referencia  VARCHAR(100),
    created_at  TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP
);

-- ── BITÁCORA  (auditoría) ──────────────────────────
-- Estructura basada en imagen de referencia (Veracruz.bitacora):
--   no_bitacora SERIAL PK, clave_cuenta CHAR(5) FK,
--   descripcion TEXT NOT NULL, fechayhora TIMESTAMP NOT NULL,
--   estado CHAR(1) NOT NULL DEFAULT 'C'  CHECK IN ('C','E')
--     C = Completado correctamente
--     E = Error / operación fallida
CREATE TABLE "Abarrotes".bitacora (
    no_bitacora  SERIAL      PRIMARY KEY,
    clave_cuenta CHAR(5)     NOT NULL,
    descripcion  TEXT        NOT NULL,
    fechayhora   TIMESTAMP   NOT NULL DEFAULT CURRENT_TIMESTAMP,
    estado       CHAR(1)     NOT NULL DEFAULT 'C'
                 CONSTRAINT bitacora_estado_check
                     CHECK (estado IN ('C','E')),
    CONSTRAINT fk_bitacora_cuenta
        FOREIGN KEY (clave_cuenta)
        REFERENCES "Abarrotes".cuenta(ClaveCuenta)
);

CREATE INDEX idx_bitacora_fecha  ON "Abarrotes".bitacora (fechayhora DESC);
CREATE INDEX idx_bitacora_cuenta ON "Abarrotes".bitacora (clave_cuenta);

-- ══════════════════════════════════════════════════
-- DATOS DE EJEMPLO
-- ══════════════════════════════════════════════════

-- Cuenta admin — contraseña: admin1
-- Requiere pgcrypto (ya instalado con: CREATE EXTENSION IF NOT EXISTS pgcrypto)
INSERT INTO "Abarrotes".cuenta VALUES
('ADM01',
 crypt('admin1', gen_salt('md5')),
 'Administrador', 'General', TRUE);

-- NOTA: Para cambiar la contraseña, usa:
--   UPDATE "Abarrotes".cuenta
--      SET Contrasena = crypt('nueva_clave', gen_salt('md5'))
--    WHERE ClaveCuenta = 'ADM01';

-- Proveedores
INSERT INTO "Abarrotes".proveedores (nombre, telefono, DiaVisita) VALUES
('Distribuidora Coca-Cola', '2281234567', 'Lunes y Jueves'),
('Sabritas Veracruz',       '2289876543', 'Martes'),
('Bimbo Xalapa',            '2285551234', 'Miercoles y Viernes');

-- Productos
INSERT INTO "Abarrotes".productos
    (codigoprod, nombre, categoria, precio_compra, precio_venta, stock, stock_minimo, unidad)
VALUES
('001', 'Coca-Cola 600ml',         'Bebidas',   12.50, 18.00, 24, 10, 'pieza'),
('002', 'Sabritas Original 45g',   'Botanas',    9.00, 14.00, 30, 10, 'bolsa'),
('003', 'Leche Lala 1L',           'Lacteos',   18.00, 24.00, 15,  8, 'litro'),
('004', 'Pan Bimbo Blanco',        'Panaderia', 28.00, 38.00, 10,  5, 'pieza'),
('005', 'Jabón Zote',              'Limpieza',   8.00, 12.00, 20,  8, 'pieza'),
('006', 'Arroz Morelos 1kg',       'Granos',    18.00, 25.00,  8, 10, 'kg'),
('007', 'Frijol Negro 1kg',        'Granos',    22.00, 30.00,  6, 10, 'kg'),
('008', 'Aceite Nutrioli 1L',      'Aceites',   35.00, 48.00, 12,  5, 'litro'),
('009', 'Azucar Estándar 1kg',     'Abarrotes', 16.00, 22.00,  7,  8, 'kg'),
('010', 'Sal La Fina 1kg',         'Abarrotes',  9.00, 15.00, 18,  5, 'kg'),
('011', 'Pepsi 600ml',             'Bebidas',   12.00, 18.00,  2, 10, 'pieza'),
('012', 'Papel Higiénico Regio x4','Higiene',   28.00, 38.00,  1,  5, 'pieza');
