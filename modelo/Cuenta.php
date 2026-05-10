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
}
?>
