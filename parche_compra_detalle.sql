-- ================================================================
-- PARCHE: Crear tabla compra_detalle
-- BD: Tienda  |  Esquema: Abarrotes  |  PostgreSQL 14+
-- Ejecutar en pgAdmin ANTES de subir los archivos PHP
-- ================================================================

SET search_path TO "Abarrotes", public;

CREATE TABLE IF NOT EXISTS "Abarrotes".compra_detalle (
    id               SERIAL          PRIMARY KEY,
    compra_id        INTEGER         NOT NULL
                         REFERENCES "Abarrotes".compras(id) ON DELETE CASCADE,
    codigoprod       VARCHAR(15)     NOT NULL
                         REFERENCES "Abarrotes".productos(codigoprod) ON DELETE RESTRICT,
    cantidad         NUMERIC(10,3)   NOT NULL CHECK (cantidad > 0),
    precio_unitario  NUMERIC(10,2)   NOT NULL DEFAULT 0.00,
    subtotal         NUMERIC(10,2)   NOT NULL DEFAULT 0.00
);

COMMENT ON TABLE "Abarrotes".compra_detalle IS
    'Detalle de productos recibidos en cada compra — actualiza stock automáticamente';

CREATE INDEX IF NOT EXISTS idx_compra_detalle_compra
    ON "Abarrotes".compra_detalle(compra_id);

CREATE INDEX IF NOT EXISTS idx_compra_detalle_producto
    ON "Abarrotes".compra_detalle(codigoprod);

-- Verificar creación
SELECT table_name, column_name, data_type
FROM information_schema.columns
WHERE table_schema = 'Abarrotes' AND table_name = 'compra_detalle'
ORDER BY ordinal_position;
