<?php
require_once __DIR__ . '/../config/conexion.php';

class Usuario
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Conexion::getConexion();
    }

    /** Busca un usuario activo por su nombre de usuario (para el login). */
    public function buscarPorNombreUsuario(string $nombreUsuario): array|false
    {
        $stmt = $this->db->prepare(
            'SELECT id_usuario, nombre_usuario, nombre_completo, password_hash, rol
             FROM usuarios
             WHERE nombre_usuario = :nombre_usuario AND activo = 1'
        );
        $stmt->execute([':nombre_usuario' => $nombreUsuario]);
        return $stmt->fetch();
    }

    public function buscarPorId(int $id): array|false
    {
        $stmt = $this->db->prepare(
            'SELECT id_usuario, nombre_usuario, nombre_completo, rol
             FROM usuarios
             WHERE id_usuario = :id AND activo = 1'
        );
        $stmt->execute([':id' => $id]);
        return $stmt->fetch();
    }
}