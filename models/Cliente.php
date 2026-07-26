<?php
require_once __DIR__ . '/../config/conexion.php';

class Cliente
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Conexion::getConexion();
    }

    public function listarTodos(): array
    {
        $stmt = $this->db->query('SELECT * FROM cliente ORDER BY apellido, nombre');
        return $stmt->fetchAll();
    }

    public function buscarPorId(int $id): array|false
    {
        $stmt = $this->db->prepare('SELECT * FROM cliente WHERE id_cliente = :id');
        $stmt->execute([':id' => $id]);
        return $stmt->fetch();
    }

    public function buscar(string $texto): array
    {
        // Nota: cada aparición de un placeholder nombrado necesita su propio
        // nombre (:texto1, :texto2, :texto3). Con PDO::ATTR_EMULATE_PREPARES
        // en false (como está configurado en Conexion), reutilizar el mismo
        // nombre varias veces en una sola consulta lanza
        // "SQLSTATE[HY093]: Invalid parameter number".
        $stmt = $this->db->prepare(
            'SELECT * FROM cliente
             WHERE nombre LIKE :texto1 OR apellido LIKE :texto2 OR dni LIKE :texto3
             ORDER BY apellido, nombre'
        );
        $comodin = '%' . $texto . '%';
        $stmt->execute([
            ':texto1' => $comodin,
            ':texto2' => $comodin,
            ':texto3' => $comodin,
        ]);
        return $stmt->fetchAll();
    }

    public function crear(array $datos): int
    {
        $sql = 'INSERT INTO cliente (nombre, apellido, dni, direccion, telefono, email)
                VALUES (:nombre, :apellido, :dni, :direccion, :telefono, :email)';
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':nombre'    => $datos['nombre'],
            ':apellido'  => $datos['apellido'],
            ':dni'       => $datos['dni'],
            ':direccion' => $datos['direccion'],
            ':telefono'  => $datos['telefono'],
            ':email'     => $datos['email'],
        ]);
        return (int) $this->db->lastInsertId();
    }

    public function actualizar(int $id, array $datos): bool
    {
        $sql = 'UPDATE cliente
                SET nombre = :nombre, apellido = :apellido, dni = :dni,
                    direccion = :direccion, telefono = :telefono, email = :email
                WHERE id_cliente = :id';
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':nombre'    => $datos['nombre'],
            ':apellido'  => $datos['apellido'],
            ':dni'       => $datos['dni'],
            ':direccion' => $datos['direccion'],
            ':telefono'  => $datos['telefono'],
            ':email'     => $datos['email'],
            ':id'        => $id,
        ]);
    }

    public function eliminar(int $id): bool
    {
        $stmt = $this->db->prepare('DELETE FROM cliente WHERE id_cliente = :id');
        return $stmt->execute([':id' => $id]);
    }

    public function dniExiste(string $dni, ?int $excluirId = null): bool
    {
        if ($excluirId !== null) {
            $stmt = $this->db->prepare('SELECT COUNT(*) AS total FROM cliente WHERE dni = :dni AND id_cliente != :id');
            $stmt->execute([':dni' => $dni, ':id' => $excluirId]);
        } else {
            $stmt = $this->db->prepare('SELECT COUNT(*) AS total FROM cliente WHERE dni = :dni');
            $stmt->execute([':dni' => $dni]);
        }
        return (int) $stmt->fetch()['total'] > 0;
    }
}