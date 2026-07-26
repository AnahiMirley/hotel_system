<?php
require_once __DIR__ . '/../config/conexion.php';

class Reserva
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Conexion::getConexion();
    }

    private const SELECT_BASE = '
        SELECT r.id_reserva, r.fecha_reserva, r.fecha_entrada, r.fecha_salida, r.estado,
               r.id_cliente, r.id_habitacion,
               CONCAT(c.nombre, " ", c.apellido) AS cliente_nombre,
               h.numero AS habitacion_numero
        FROM reserva r
        INNER JOIN cliente c ON c.id_cliente = r.id_cliente
        INNER JOIN habitacion h ON h.id_habitacion = r.id_habitacion';

    public function listarTodos(): array
    {
        $stmt = $this->db->query(self::SELECT_BASE . ' ORDER BY r.fecha_entrada DESC');
        return $stmt->fetchAll();
    }

    public function buscarPorId(int $id): array|false
    {
        $stmt = $this->db->prepare(self::SELECT_BASE . ' WHERE r.id_reserva = :id');
        $stmt->execute([':id' => $id]);
        return $stmt->fetch();
    }

    public function buscar(string $texto): array
    {
        $stmt = $this->db->prepare(
            self::SELECT_BASE . '
            WHERE c.nombre LIKE :texto OR c.apellido LIKE :texto
               OR h.numero LIKE :texto OR r.estado LIKE :texto
            ORDER BY r.fecha_entrada DESC'
        );
        $stmt->execute([':texto' => '%' . $texto . '%']);
        return $stmt->fetchAll();
    }

    public function crear(array $datos): int
    {
        $sql = 'INSERT INTO reserva (id_cliente, id_habitacion, fecha_entrada, fecha_salida, estado)
                VALUES (:id_cliente, :id_habitacion, :fecha_entrada, :fecha_salida, :estado)';
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':id_cliente'    => $datos['id_cliente'],
            ':id_habitacion' => $datos['id_habitacion'],
            ':fecha_entrada' => $datos['fecha_entrada'],
            ':fecha_salida'  => $datos['fecha_salida'],
            ':estado'        => $datos['estado'],
        ]);
        return (int) $this->db->lastInsertId();
    }

    public function actualizar(int $id, array $datos): bool
    {
        $sql = 'UPDATE reserva
                SET id_cliente = :id_cliente, id_habitacion = :id_habitacion,
                    fecha_entrada = :fecha_entrada, fecha_salida = :fecha_salida, estado = :estado
                WHERE id_reserva = :id';
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':id_cliente'    => $datos['id_cliente'],
            ':id_habitacion' => $datos['id_habitacion'],
            ':fecha_entrada' => $datos['fecha_entrada'],
            ':fecha_salida'  => $datos['fecha_salida'],
            ':estado'        => $datos['estado'],
            ':id'            => $id,
        ]);
    }

    public function eliminar(int $id): bool
    {
        $stmt = $this->db->prepare('DELETE FROM reserva WHERE id_reserva = :id');
        return $stmt->execute([':id' => $id]);
    }

    /** Verifica solapamiento de fechas para la misma habitación (regla de negocio). */
    public function existeSolapamiento(int $idHabitacion, string $entrada, string $salida, ?int $excluirId = null): bool
    {
        $sql = 'SELECT COUNT(*) AS total FROM reserva
                WHERE id_habitacion = :id_habitacion
                  AND estado != "cancelada"
                  AND fecha_entrada < :salida AND fecha_salida > :entrada';
        $params = [
            ':id_habitacion' => $idHabitacion,
            ':entrada'       => $entrada,
            ':salida'        => $salida,
        ];
        if ($excluirId !== null) {
            $sql .= ' AND id_reserva != :id';
            $params[':id'] = $excluirId;
        }
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return (int) $stmt->fetch()['total'] > 0;
    }

    public function obtenerClientes(): array
    {
        return $this->db->query('SELECT id_cliente, nombre, apellido FROM cliente ORDER BY apellido')->fetchAll();
    }

    public function obtenerHabitaciones(): array
    {
        $sql = 'SELECT id_habitacion, numero FROM habitacion ORDER BY numero';
        return $this->db->query($sql)->fetchAll();
    }
}
