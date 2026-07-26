<?php
require_once __DIR__ . '/../config/conexion.php';

class Servicio
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Conexion::getConexion();
    }

    public function listarTodos(): array
    {
        $stmt = $this->db->query('SELECT * FROM servicios ORDER BY nombre ASC');
        return $stmt->fetchAll();
    }

    public function buscarPorId(int $id): array|false
    {
        $stmt = $this->db->prepare('SELECT * FROM servicios WHERE id_servicio = :id');
        $stmt->execute([':id' => $id]);
        return $stmt->fetch();
    }

    public function buscar(string $texto): array
    {
        $stmt = $this->db->prepare('SELECT * FROM servicios WHERE nombre LIKE :texto ORDER BY nombre ASC');
        $stmt->execute([':texto' => '%' . $texto . '%']);
        return $stmt->fetchAll();
    }

    public function crear(array $datos): int
    {
        $sql = 'INSERT INTO servicios (nombre, descripcion, precio) VALUES (:nombre, :descripcion, :precio)';
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':nombre'      => $datos['nombre'],
            ':descripcion' => $datos['descripcion'],
            ':precio'      => $datos['precio'],
        ]);
        return (int) $this->db->lastInsertId();
    }

    public function actualizar(int $id, array $datos): bool
    {
        $sql = 'UPDATE servicios SET nombre = :nombre, descripcion = :descripcion, precio = :precio
                WHERE id_servicio = :id';
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':nombre'      => $datos['nombre'],
            ':descripcion' => $datos['descripcion'],
            ':precio'      => $datos['precio'],
            ':id'          => $id,
        ]);
    }

    public function eliminar(int $id): bool
    {
        $stmt = $this->db->prepare('DELETE FROM servicios WHERE id_servicio = :id');
        return $stmt->execute([':id' => $id]);
    }
}
