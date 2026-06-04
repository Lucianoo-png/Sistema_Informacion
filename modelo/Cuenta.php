<?php
// =====================================================
// modelo/Cuenta.php — Autenticación con pgcrypto crypt()
// CAMBIO: usa crypt() de PostgreSQL para verificar y
//         guardar contraseñas, compatible con:
//         crypt('Patricio11', gen_salt('md5'))
// =====================================================

require_once __DIR__ . '/Conexion.php';

class Cuenta {
    private PDO $db;

    public function __construct() {
        $this->db = Conexion::obtener();
    }

    /**
     * Verifica credenciales usando pgcrypto crypt().
     * Funciona con cualquier tipo de salt (md5, bf, sha256…)
     * porque crypt(input, stored) == stored cuando es correcto.
     *
     * @return array|null  Fila de cuenta o null si falla
     */
    public function autenticar(string $clave, string $contrasena): ?array {
        // La comparación se hace DENTRO de PostgreSQL con crypt()
        // crypt(:pass, Contrasena) reproduce el hash usando el salt
        // que ya está guardado → si coincide, devuelve la fila.
        $stmt = $this->db->prepare(
            "SELECT ClaveCuenta, Nombre, Apellidos
               FROM cuenta
              WHERE ClaveCuenta = :clave
                AND Contrasena  = crypt(:pass, Contrasena)
                AND activo      = TRUE"
        );
        $stmt->execute([':clave' => $clave, ':pass' => $contrasena]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    // ── CRUD ───────────────────────────────────────

    /** Lista todas las cuentas (sin mostrar contraseña) */
    public function obtenerTodas(): array {
        return $this->db->query(
            "SELECT ClaveCuenta, Nombre, Apellidos, activo FROM cuenta ORDER BY ClaveCuenta"
        )->fetchAll();
    }

    /**
     * Crea una cuenta nueva.
     * La contraseña se hashea con bcrypt (gen_salt('bf')) dentro de PostgreSQL.
     */
    public function crear(array $d): bool {
        $stmt = $this->db->prepare(
            "INSERT INTO cuenta (ClaveCuenta, Contrasena, Nombre, Apellidos)
             VALUES (:clave, crypt(:pass, gen_salt('md5')), :nombre, :apellidos)"
        );
        return $stmt->execute([
            ':clave'    => strtoupper(trim($d['clave'])),
            ':pass'     => $d['contrasena'],
            ':nombre'   => $d['nombre'],
            ':apellidos'=> $d['apellidos'],
        ]);
    }

    /**
     * Cambia la contraseña de una cuenta existente.
     * También usa gen_salt('md5') para ser consistente con el INSERT original.
     */
    public function cambiarContrasena(string $clave, string $nuevaContrasena): bool {
        $stmt = $this->db->prepare(
            "UPDATE cuenta
                SET Contrasena = crypt(:pass, gen_salt('md5'))
              WHERE ClaveCuenta = :clave"
        );
        return $stmt->execute([':pass' => $nuevaContrasena, ':clave' => $clave]);
    }

    /** Activa o desactiva una cuenta */
    public function toggleActivo(string $clave): bool {
        $stmt = $this->db->prepare(
            "UPDATE cuenta SET activo = NOT activo WHERE ClaveCuenta = :clave"
        );
        return $stmt->execute([':clave' => $clave]);
    }

    /**
     * Guarda el token de sesión activa para un usuario.
     * Si la columna no existe la crea la primera vez.
     */
    public function guardarTokenSesion(string $clave, string $token): void {
        // 6.3.2 Gestión de claves: almacenar hash del token, nunca el token en claro
        $tokenHash = hash('sha256', $token);
        try {
            $stmt = $this->db->prepare(
                "UPDATE cuenta SET token_sesion = :token WHERE ClaveCuenta = :clave"
            );
            $stmt->execute([':token' => $tokenHash, ':clave' => $clave]);
        } catch (\Exception $e) {
            // Si la columna no existe todavía, crearla y reintentar
            try {
                $this->db->exec("ALTER TABLE cuenta ADD COLUMN IF NOT EXISTS token_sesion VARCHAR(128) DEFAULT NULL");
                $stmt = $this->db->prepare(
                    "UPDATE cuenta SET token_sesion = :token WHERE ClaveCuenta = :clave"
                );
                $stmt->execute([':token' => $token, ':clave' => $clave]);
            } catch (\Exception $e2) {
                error_log('Cuenta::guardarTokenSesion — ' . $e2->getMessage());
            }
        }
    }

    /** Obtiene el token de sesión activa almacenado para un usuario */
    public function obtenerTokenSesion(string $clave): ?string {
        try {
            $stmt = $this->db->prepare(
                "SELECT token_sesion FROM cuenta WHERE ClaveCuenta = :clave"
            );
            $stmt->execute([':clave' => $clave]);
            $row = $stmt->fetch();
            return $row ? ($row['token_sesion'] ?? null) : null;
        } catch (\Exception $e) {
            return null;
        }
    }

    /** Elimina el token de sesión al cerrar sesión */
    public function limpiarTokenSesion(string $clave): void {
        try {
            $stmt = $this->db->prepare(
                "UPDATE cuenta SET token_sesion = NULL WHERE ClaveCuenta = :clave"
            );
            $stmt->execute([':clave' => $clave]);
        } catch (\Exception $e) {
            error_log('Cuenta::limpiarTokenSesion — ' . $e->getMessage());
        }
    }
}
?>
