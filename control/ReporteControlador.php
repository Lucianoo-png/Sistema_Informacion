<?php
// =====================================================
// control/ReporteControlador.php
// NUEVO: métodos por rango (semanal, mensual, anual, personalizado)
// =====================================================

require_once BASE_PATH . 'modelo/Venta.php';
require_once BASE_PATH . 'modelo/Compra.php';
require_once BASE_PATH . 'modelo/Producto.php';

class ReporteControlador {
    private Venta    $modeloVenta;
    private Compra   $modeloCompra;
    private Producto $modeloProducto;

    public function __construct() {
        $this->modeloVenta    = new Venta();
        $this->modeloCompra   = new Compra();
        $this->modeloProducto = new Producto();
    }

    // ── Panel principal (día actual) ───────────────
    public function resumenPanel(string $fecha = ''): array {
        $fecha   = $fecha ?: date('Y-m-d');
        $totV    = $this->modeloVenta->totalDelDia($fecha);
        $totC    = $this->modeloCompra->totalDelDia($fecha);
        $stkBajo = $this->modeloProducto->contarStockBajo();

        return [
            'ventas_dia'       => $totV['total_ventas']      ?? 0,
            'transacciones'    => $totV['num_transacciones']  ?? 0,
            'efectivo_dia'     => $totV['efectivo']           ?? 0,
            'transferencia_dia'=> $totV['transferencia']      ?? 0,
            'compras_dia'      => $totC,
            'balance'          => ($totV['total_ventas'] ?? 0) - $totC,
            'stock_bajo'       => $stkBajo,
            'ventas_recientes' => $this->modeloVenta->obtenerDelDia($fecha),
            'compras_recientes'=> $this->modeloCompra->obtenerDelDia($fecha),
        ];
    }

    // ── Reporte un solo día ────────────────────────
    public function reporteDiario(string $fecha = ''): array {
        $fecha = $fecha ?: date('Y-m-d');
        return [
            'fecha'        => $fecha,
            'desde'        => $fecha,
            'hasta'        => $fecha,
            'totales'      => $this->modeloVenta->totalDelDia($fecha),
            'total_compras'=> $this->modeloCompra->totalDelDia($fecha),
            'mas_vendidos' => $this->modeloVenta->masVendidos($fecha),
            'ventas_por_dia'=> [],
        ];
    }

    // ── Reporte por rango ──────────────────────────
    public function reporteRango(string $desde, string $hasta): array {
        // Validar orden de fechas
        if ($desde > $hasta) [$desde, $hasta] = [$hasta, $desde];

        $totV = $this->modeloVenta->totalEnRango($desde, $hasta);
        $totC = $this->modeloCompra->totalEnRango($desde, $hasta);

        return [
            'desde'         => $desde,
            'hasta'         => $hasta,
            'totales'       => $totV,
            'total_ventas'  => $totV['total_ventas']     ?? 0,
            'transacciones' => $totV['num_transacciones'] ?? 0,
            'efectivo'      => $totV['efectivo']          ?? 0,
            'transferencia' => $totV['transferencia']     ?? 0,
            'total_compras' => $totC,
            'balance'       => ($totV['total_ventas'] ?? 0) - $totC,
            'mas_vendidos'  => $this->modeloVenta->masVendidosRango($desde, $hasta),
            'ventas_por_dia'=> $this->modeloVenta->ventasPorDia($desde, $hasta),
        ];
    }

    // ── Corte de caja (un día) ─────────────────────
    public function corteDeCaja(string $fecha = ''): array {
        $fecha   = $fecha ?: date('Y-m-d');
        $totales = $this->modeloVenta->totalDelDia($fecha);
        $totC    = $this->modeloCompra->totalDelDia($fecha);

        return [
            'fecha'         => $fecha,
            'desde'         => $fecha,
            'hasta'         => $fecha,
            'efectivo'      => $totales['efectivo']          ?? 0,
            'transferencia' => $totales['transferencia']      ?? 0,
            'total_ingresos'=> $totales['total_ventas']       ?? 0,
            'total_compras' => $totC,
            'balance_final' => ($totales['total_ventas'] ?? 0) - $totC,
            'num_ventas'    => $totales['num_transacciones']  ?? 0,
        ];
    }

    // ── Corte de caja por rango ────────────────────
    public function corteRango(string $desde, string $hasta): array {
        if ($desde > $hasta) [$desde, $hasta] = [$hasta, $desde];
        $totV = $this->modeloVenta->totalEnRango($desde, $hasta);
        $totC = $this->modeloCompra->totalEnRango($desde, $hasta);

        return [
            'desde'         => $desde,
            'hasta'         => $hasta,
            'efectivo'      => $totV['efectivo']          ?? 0,
            'transferencia' => $totV['transferencia']      ?? 0,
            'total_ingresos'=> $totV['total_ventas']       ?? 0,
            'total_compras' => $totC,
            'balance_final' => ($totV['total_ventas'] ?? 0) - $totC,
            'num_ventas'    => $totV['num_transacciones']  ?? 0,
        ];
    }

    // ── Rango predefinido por periodo ──────────────
    public static function rangoDelPeriodo(string $periodo): array {
        $hoy = date('Y-m-d');
        return match ($periodo) {
            'semana'  => [date('Y-m-d', strtotime('monday this week')), $hoy],
            'mes'     => [date('Y-m-01'), $hoy],
            'anio'    => [date('Y-01-01'), $hoy],
            default   => [$hoy, $hoy],
        };
    }
}
?>
