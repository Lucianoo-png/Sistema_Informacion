<?php
// =====================================================
// helpers/Validar.php
// Validación centralizada de entradas — 6.2.1 ISO 27002
// =====================================================

class Validar {

    // Texto general: longitud + solo caracteres permitidos
    public static function texto(
        mixed  $valor,
        int    $minLen  = 1,
        int    $maxLen  = 200,
        bool   $requerido = true
    ): string {
        $v = mb_substr(trim((string)$valor), 0, $maxLen);
        if ($requerido && mb_strlen($v) < $minLen) {
            throw new \InvalidArgumentException("Campo requerido (mínimo {$minLen} caracter(es)).");
        }
        return $v;
    }

    // Código de producto: alfanumérico + guiones, máx 15
    public static function codigoProd(mixed $valor): string {
        $v = strtoupper(mb_substr(trim((string)$valor), 0, 15));
        if (!preg_match('/^[A-Z0-9\-]+$/', $v)) {
            throw new \InvalidArgumentException("Código inválido. Solo letras, números y guiones.");
        }
        return $v;
    }

    // Clave de cuenta: exactamente 5 alfanuméricos
    public static function claveCuenta(mixed $valor): string {
        $v = strtoupper(trim((string)$valor));
        if (!preg_match('/^[A-Z0-9]{5}$/', $v)) {
            throw new \InvalidArgumentException("La clave debe tener exactamente 5 caracteres alfanuméricos.");
        }
        return $v;
    }

    // Monto con límites configurables
    public static function monto(mixed $valor, float $min = 0.01, float $max = PHP_FLOAT_MAX): float {
        $v = (float)$valor;
        if ($v < $min) {
            throw new \InvalidArgumentException("El monto debe ser de al menos \${$min}.");
        }
        if ($max < PHP_FLOAT_MAX && $v > $max) {
            throw new \InvalidArgumentException("El monto no puede superar \${$max}.");
        }
        return round($v, 2);
    }

    // Cantidad positiva (admite decimales para kg)
    public static function cantidad(mixed $valor, float $min = 0.001): float {
        $v = (float)$valor;
        if ($v < $min) {
            throw new \InvalidArgumentException("La cantidad debe ser mayor a cero.");
        }
        return round($v, 3);
    }

    // Entero positivo (stock, IDs) con máximo opcional
    public static function enteroPositivo(mixed $valor, int $min = 0, int $max = PHP_INT_MAX): int {
        $v = (int)$valor;
        if ($v < $min) {
            throw new \InvalidArgumentException("El valor debe ser mayor o igual a {$min}.");
        }
        if ($max < PHP_INT_MAX && $v > $max) {
            throw new \InvalidArgumentException("El valor no puede superar {$max}.");
        }
        return $v;
    }

    // Fecha YYYY-MM-DD no futura
    public static function fechaNoFutura(mixed $valor): string {
        $v   = trim((string)$valor);
        $hoy = date('Y-m-d');
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $v)) {
            throw new \InvalidArgumentException("Formato de fecha inválido.");
        }
        if ($v > $hoy) {
            throw new \InvalidArgumentException("La fecha no puede ser futura.");
        }
        return $v;
    }

    // Rango de fechas coherente y no futuro
    public static function rangoFechas(mixed $desde, mixed $hasta): array {
        $d = self::fechaNoFutura($desde);
        $h = self::fechaNoFutura($hasta);
        if ($h < $d) {
            throw new \InvalidArgumentException('"Hasta" no puede ser anterior a "Desde".');
        }
        return [$d, $h];
    }

    // Método de pago: solo valores permitidos
    public static function metodoPago(mixed $valor): string {
        $v = strtolower(trim((string)$valor));
        if (!in_array($v, ['efectivo', 'transferencia'], true)) {
            return 'efectivo'; // Valor por defecto seguro
        }
        return $v;
    }

    // Unidad de producto: solo valores permitidos
    public static function unidad(mixed $valor): string {
        $permitidas = ['pieza', 'kg', 'litro', 'bolsa', 'caja', 'paquete'];
        $v = strtolower(trim((string)$valor));
        if (!in_array($v, $permitidas, true)) {
            throw new \InvalidArgumentException("Unidad no válida: {$v}.");
        }
        return $v;
    }

    // Teléfono: exactamente 10 dígitos
    public static function telefono(mixed $valor): string {
        $v = preg_replace('/\D/', '', (string)$valor);
        if (strlen($v) !== 10) {
            throw new \InvalidArgumentException("El teléfono debe tener exactamente 10 dígitos.");
        }
        return $v;
    }
}
?>
