<?php
require_once __DIR__ . '/../config/conexion.php';

class Habitacion
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Conexion::getConexion();
    }

    private const SELECT_BASE = '
        SELECT h.id_habitacion, h.numero, h.planta, h.estado,
               h.id_tipo_habitacion,
               t.nombre AS tipo_nombre, t.precio_base
        FROM habitacion h
        INNER JOIN tipo_habitacion t ON t.id_tipo_habitacion = h.id_tipo_habitacion';

    public function listarTodos(): array
    {
        $stmt = $this->db->query(self::SELECT_BASE . ' ORDER BY h.numero');
        return $stmt->fetchAll();
    }

    public function buscarPorId(int $id): array|false
    {
        $stmt = $this->db->prepare(self::SELECT_BASE . ' WHERE h.id_habitacion = :id');
        $stmt->execute([':id' => $id]);
        return $stmt->fetch();
    }

    public function buscar(string $texto): array
    {
        $stmt = $this->db->prepare(
            self::SELECT_BASE . '
            WHERE h.numero LIKE :texto OR t.nombre LIKE :texto
            ORDER BY h.numero'
        );
        $stmt->execute([':texto' => '%' . $texto . '%']);
        return $stmt->fetchAll();
    }

    public function crear(array $datos): int
    {
        $sql = 'INSERT INTO habitacion (numero, planta, estado, id_tipo_habitacion)
                VALUES (:numero, :planta, :estado, :id_tipo_habitacion)';
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':numero'             => $datos['numero'],
            ':planta'             => $datos['planta'],
            ':estado'             => $datos['estado'],
            ':id_tipo_habitacion' => $datos['id_tipo_habitacion'],
        ]);
        return (int) $this->db->lastInsertId();
    }

    public function actualizar(int $id, array $datos): bool
    {
        $sql = 'UPDATE habitacion
                SET numero = :numero, planta = :planta, estado = :estado,
                    id_tipo_habitacion = :id_tipo_habitacion
                WHERE id_habitacion = :id';
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':numero'             => $datos['numero'],
            ':planta'             => $datos['planta'],
            ':estado'             => $datos['estado'],
            ':id_tipo_habitacion' => $datos['id_tipo_habitacion'],
            ':id'                 => $id,
        ]);
    }

    public function eliminar(int $id): bool
    {
        $stmt = $this->db->prepare('DELETE FROM habitacion WHERE id_habitacion = :id');
        return $stmt->execute([':id' => $id]);
    }

    public function tieneReservas(int $id): bool
    {
        $stmt = $this->db->prepare('SELECT COUNT(*) AS total FROM reserva WHERE id_habitacion = :id');
        $stmt->execute([':id' => $id]);
        return (int) $stmt->fetch()['total'] > 0;
    }
}
