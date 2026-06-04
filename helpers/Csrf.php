<?php
// =====================================================
// helpers/Csrf.php
// Protección CSRF — 6.2.3 Integridad del mensaje ISO 27002
// =====================================================

class Csrf {

    private const TOKEN_KEY = 'csrf_token';
    private const HEADER    = 'X-CSRF-Token';

    // Genera (o reutiliza) el token de la sesión actual
    public static function generar(): string {
        if (empty($_SESSION[self::TOKEN_KEY])) {
            $_SESSION[self::TOKEN_KEY] = bin2hex(random_bytes(32));
        }
        return $_SESSION[self::TOKEN_KEY];
    }

    // Devuelve el token actual sin regenerar
    public static function obtener(): string {
        return $_SESSION[self::TOKEN_KEY] ?? self::generar();
    }

    // Rota el token después de cada uso exitoso
    public static function rotar(): void {
        $_SESSION[self::TOKEN_KEY] = bin2hex(random_bytes(32));
    }

    // Verifica el token de un formulario HTML (POST field)
    public static function verificarPost(): bool {
        $enviado = $_POST[self::TOKEN_KEY] ?? '';
        return hash_equals(self::obtener(), $enviado);
    }

    // Verifica el token de una petición JSON/AJAX (header HTTP)
    public static function verificarHeader(): bool {
        // Apache / Nginx exponen los headers como HTTP_X_CSRF_TOKEN
        $headerNorm = 'HTTP_' . strtoupper(str_replace('-', '_', self::HEADER));
        $enviado    = $_SERVER[$headerNorm] ?? '';
        return hash_equals(self::obtener(), $enviado);
    }

    // Lanza excepción si el token no es válido
    public static function requerir(bool $esJson = false): void {
        $valido = $esJson ? self::verificarHeader() : self::verificarPost();
        if (!$valido) {
            if ($esJson) {
                header('Content-Type: application/json');
                http_response_code(403);
                echo json_encode(['ok' => false, 'mensaje' => 'Token de seguridad inválido. Recarga la página.']);
                exit;
            }
            http_response_code(403);
            die('Token de seguridad inválido. <a href="javascript:history.back()">Volver</a>');
        }
        // Solo rotar en formularios HTML (no en AJAX/JSON)
        // En AJAX el token debe ser estable durante la sesión para permitir
        // múltiples peticiones sin recargar la página
        if (!$esJson) {
            self::rotar();
        }
    }

    // Retorna el campo hidden HTML listo para insertar en formularios
    public static function campoHtml(): string {
        return '<input type="hidden" name="' . self::TOKEN_KEY
             . '" value="' . htmlspecialchars(self::obtener()) . '">';
    }
}
?>
