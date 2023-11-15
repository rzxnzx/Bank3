<?php

require_once("../database/MysqlConnection.php");

class CuentaBancaria
{
    private $db;

    public function __construct()
    {
        $this->db = MysqlConnection::getInstance()->GetDatabase();
    }

    public function obtenerSaldo($idUsuario)
    {
        $query = "SELECT saldo FROM cuentas_bancarias WHERE id_usuario = :id_usuario";
        $statement = $this->db->prepare($query);
        $statement->bindParam(':id_usuario', $idUsuario);
        $statement->execute();
        $saldo = $statement->fetchColumn();

        return $saldo;
    }

    public function consignar($idUsuario, $monto)
    {
        $saldoActual = $this->obtenerSaldo($idUsuario);
        $nuevoSaldo = $saldoActual + $monto;
        $this->actualizarSaldo($idUsuario, $nuevoSaldo);
        $this->registrarMovimiento($idUsuario, 'consignacion', $monto);
        return $nuevoSaldo;
    }

    public function retirar($idUsuario, $monto)
    {
        $saldoActual = $this->obtenerSaldo($idUsuario);
        if ($saldoActual >= $monto) {
            $nuevoSaldo = $saldoActual - $monto;
            $this->actualizarSaldo($idUsuario, $nuevoSaldo);
            $this->registrarMovimiento($idUsuario, 'retiro', $monto);

            return $nuevoSaldo;
        } else {
            return false;
        }
    }

    public function transferir($idUsuarioOrigen, $idUsuarioDestino, $monto)
    {
        $saldoOrigen = $this->obtenerSaldo($idUsuarioOrigen);
        if ($saldoOrigen >= $monto) {
            $nuevoSaldoOrigen = $saldoOrigen - $monto;
            $this->actualizarSaldo($idUsuarioOrigen, $nuevoSaldoOrigen);

            $saldoDestino = $this->obtenerSaldo($idUsuarioDestino);
            $nuevoSaldoDestino = $saldoDestino + $monto;
            $this->actualizarSaldo($idUsuarioDestino, $nuevoSaldoDestino);
            $this->registrarMovimiento($idUsuarioOrigen, 'transferencia', $monto, $idUsuarioDestino);
            $this->registrarMovimiento($idUsuarioDestino, 'transferencia', $monto, $idUsuarioOrigen);

            return true;
        } else {
            return false;
        }
    }

    private function actualizarSaldo($idUsuario, $nuevoSaldo)
    {
        $query = "UPDATE cuentas_bancarias SET saldo = :nuevo_saldo WHERE id_usuario = :id_usuario";
        $statement = $this->db->prepare($query);
        $statement->bindParam(':nuevo_saldo', $nuevoSaldo);
        $statement->bindParam(':id_usuario', $idUsuario);
        $statement->execute();
    }

    private function registrarMovimiento($idUsuario, $tipoMovimiento, $monto, $idUsuarioDestino = null)
    {
        $idTransaccion = uniqid(); 

        $nombreUsuario = $this->obtenerNombreUsuario($idUsuario);

        $query = "INSERT INTO movimientos (id_transaccion, id_usuario, nombre_usuario, tipo_movimiento, monto, id_usuario_destino)
                  VALUES (:id_transaccion, :id_usuario, :nombre_usuario, :tipo_movimiento, :monto, :id_usuario_destino)";
        $statement = $this->db->prepare($query);
        $statement->bindParam(':id_transaccion', $idTransaccion);
        $statement->bindParam(':id_usuario', $idUsuario);
        $statement->bindParam(':nombre_usuario', $nombreUsuario);
        $statement->bindParam(':tipo_movimiento', $tipoMovimiento);
        $statement->bindParam(':monto', $monto);
        $statement->bindParam(':id_usuario_destino', $idUsuarioDestino);
        $statement->execute();
    }

    private function obtenerNombreUsuario($idUsuario)
    {
        $query = "SELECT nombre FROM usuarios WHERE id = :id_usuario";
        $statement = $this->db->prepare($query);
        $statement->bindParam(':id_usuario', $idUsuario);
        $statement->execute();
        $nombreUsuario = $statement->fetchColumn();

        return $nombreUsuario;
    }
}
?>
