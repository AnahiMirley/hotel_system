<?php
require_once __DIR__ . '/../config/conexion.php';

class TipoHabitacion
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Conexion::getConexion();
    }

    public function listarTodos(): array
    {
        $stmt = $this->db->query('SELECT * FROM tipo_habitacion ORDER BY nombre ASC');
        return $stmt->fetchAll();
    }

    public function buscarPorId(int $id): array|false
    {
        $stmt = $this->db->prepare('SELECT * FROM tipo_habitacion WHERE id_tipo_habitacion = :id');
        $stmt->execute([':id' => $id]);
        return $stmt->fetch();
    }

    public function buscar(string $texto): array
    {
        $stmt = $this->db->prepare(
            'SELECT * FROM tipo_habitacion WHERE nombre LIKE :texto ORDER BY nombre ASC'
        );
        $stmt->execute([':texto' => '%' . $texto . '%']);
        return $stmt->fetchAll();
    }

    public function crear(array $datos): int
    {
        $sql = 'INSERT INTO tipo_habitacion (nombre, descripcion, capacidad, precio_base)
                VALUES (:nombre, :descripcion, :capacidad, :precio_base)';
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':nombre'      => $datos['nombre'],
            ':descripcion' => $datos['descripcion'],
            ':capacidad'   => $datos['capacidad'],
            ':precio_base' => $datos['precio_base'],
        ]);
        return (int) $this->db->lastInsertId();
    }

    public function actualizar(int $id, array $datos): bool
    {
        $sql = 'UPDATE tipo_habitacion
                SET nombre = :nombre, descripcion = :descripcion,
                    capacidad = :capacidad, precio_base = :precio_base
                WHERE id_tipo_habitacion = :id';
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':nombre'      => $datos['nombre'],
            ':descripcion' => $datos['descripcion'],
            ':capacidad'   => $datos['capacidad'],
            ':precio_base' => $datos['precio_base'],
            ':id'          => $id,
        ]);
    }

    public function eliminar(int $id): bool
    {
        $stmt = $this->db->prepare('DELETE FROM tipo_habitacion WHERE id_tipo_habitacion = :id');
        return $stmt->execute([':id' => $id]);
    }

    public function enUso(int $id): bool
    {
        $stmt = $this->db->prepare('SELECT COUNT(*) AS total FROM habitacion WHERE id_tipo_habitacion = :id');
        $stmt->execute([':id' => $id]);
        return (int) $stmt->fetch()['total'] > 0;
    }
}
