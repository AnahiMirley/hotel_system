<?php
require_once __DIR__ . '/../config/conexion.php';

class Gasto
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Conexion::getConexion();
    }

    private const SELECT_BASE = '
        SELECT g.id_gasto, g.concepto, g.monto, g.fecha, g.id_reserva,
               r.id_reserva AS reserva_codigo,
               CONCAT(c.nombre, " ", c.apellido) AS cliente_nombre
        FROM gastos g
        INNER JOIN reserva r ON r.id_reserva = g.id_reserva
        INNER JOIN cliente c ON c.id_cliente = r.id_cliente';

    public function listarTodos(): array
    {
        $stmt = $this->db->query(self::SELECT_BASE . ' ORDER BY g.fecha DESC');
        return $stmt->fetchAll();
    }

    public function buscarPorId(int $id): array|false
    {
        $stmt = $this->db->prepare(self::SELECT_BASE . ' WHERE g.id_gasto = :id');
        $stmt->execute([':id' => $id]);
        return $stmt->fetch();
    }

    public function buscar(string $texto): array
    {
        $stmt = $this->db->prepare(
            self::SELECT_BASE . '
            WHERE g.concepto LIKE :texto OR c.nombre LIKE :texto OR c.apellido LIKE :texto
            ORDER BY g.fecha DESC'
        );
        $stmt->execute([':texto' => '%' . $texto . '%']);
        return $stmt->fetchAll();
    }

    public function crear(array $datos): int
    {
        $sql = 'INSERT INTO gastos (id_reserva, concepto, monto, fecha)
                VALUES (:id_reserva, :concepto, :monto, :fecha)';
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':id_reserva' => $datos['id_reserva'],
            ':concepto'   => $datos['concepto'],
            ':monto'      => $datos['monto'],
            ':fecha'      => $datos['fecha'],
        ]);
        return (int) $this->db->lastInsertId();
    }

    public function actualizar(int $id, array $datos): bool
    {
        $sql = 'UPDATE gastos SET id_reserva = :id_reserva, concepto = :concepto,
                    monto = :monto, fecha = :fecha
                WHERE id_gasto = :id';
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':id_reserva' => $datos['id_reserva'],
            ':concepto'   => $datos['concepto'],
            ':monto'      => $datos['monto'],
            ':fecha'      => $datos['fecha'],
            ':id'         => $id,
        ]);
    }

    public function eliminar(int $id): bool
    {
        $stmt = $this->db->prepare('DELETE FROM gastos WHERE id_gasto = :id');
        return $stmt->execute([':id' => $id]);
    }

    public function obtenerReservas(): array
    {
        $sql = 'SELECT r.id_reserva, CONCAT(c.nombre, " ", c.apellido) AS cliente_nombre, h.numero
                FROM reserva r
                INNER JOIN cliente c ON c.id_cliente = r.id_cliente
                INNER JOIN habitacion h ON h.id_habitacion = r.id_habitacion
                ORDER BY r.id_reserva DESC';
        return $this->db->query($sql)->fetchAll();
    }
}
