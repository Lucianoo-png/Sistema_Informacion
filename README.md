# Abarrotes Angy — Sistema de Información v1.0

Sistema CRUD en PHP con arquitectura MVC para gestión de tienda de abarrotes.

## Estructura de Carpetas

```
AbarrotesAngy/
├── control/                  ← Controladores
│   ├── navbar.php
│   ├── ProductoControlador.php
│   ├── VentaControlador.php
│   ├── CompraControlador.php
│   ├── ProveedorControlador.php
│   ├── TransferenciaControlador.php
│   └── ReporteControlador.php
├── estilos/
│   └── abarrotes.css
├── helpers/
│   └── layout.php
├── js/
│   └── abarrotes.js
├── modelo/                   ← Modelos (acceso a BD)
│   ├── Conexion.php
│   ├── Producto.php
│   ├── Venta.php
│   ├── Compra.php
│   ├── Proveedor.php
│   └── Transferencia.php
├── public/
├── vista/                    ← Vistas HTML/PHP
│   ├── admin/
│   │   ├── panel.php
│   │   ├── compras.php
│   │   ├── inventario.php
│   │   ├── proveedores.php
│   │   ├── transferencias.php
│   │   ├── reporte_diario.php
│   │   └── corte_caja.php
│   ├── vendedor/
│   │   └── ventas.php
│   └── 404.php
├── .htaccess
├── index.php                 ← Router principal
└── abarrotes_angy.sql        ← Script de base de datos
```

## Instalación (XAMPP)

1. Copia la carpeta `AbarrotesAngy/` en `C:\xampp\htdocs\`
2. Abre **phpMyAdmin** y ejecuta `abarrotes_angy.sql`
3. Abre el navegador en `http://localhost/AbarrotesAngy/`

## Módulos

| Módulo | Ruta | Descripción |
|--------|------|-------------|
| Panel Principal | `/panel` | Resumen del día |
| Ventas | `/ventas` | Registrar venta con carrito |
| Compras | `/compras` | Registrar compra de proveedor/directa |
| Inventario | `/inventario` | CRUD de productos |
| Proveedores | `/proveedores` | CRUD de proveedores |
| Transferencias | `/transferencias` | Registrar ingresos por transferencia |
| Reporte Diario | `/reporte` | Resumen de operaciones por fecha |
| Corte de Caja | `/corte` | Balance de ingresos y egresos |

## Tecnologías

- **Backend:** PHP 8+ con PDO
- **Base de datos:** MySQL (via XAMPP)
- **Frontend:** HTML5, CSS3, JavaScript vanilla
- **Patrón:** MVC (Modelo-Vista-Controlador)
