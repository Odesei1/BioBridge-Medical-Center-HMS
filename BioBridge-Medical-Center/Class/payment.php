<?php
require_once __DIR__ . "/../Config/database.php";

class Payment {
    private $conn;
    private $table = "payment";

    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection(); 
    }

    public function addPaymentRecord($data) {
        $sql = "INSERT INTO payment (pymt_amount_paid, pymt_meth_id, pymt_stat_id, appt_id)
                VALUES (?, ?, ?, ?)";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([
            $data['pymt_amount_paid'],
            $data['pymt_meth_id'],
            $data['pymt_stat_id'],
            $data['appt_id']
        ]);
    }

    public function getAllPayments() {
        $sql = "SELECT
                    p.pymt_id,
                    p.pymt_amount_paid,
                    pm.pymt_meth_name,
                    ps.pymt_stat_name,
                    p.appt_id
                FROM payment p
                LEFT JOIN payment_method pm ON p.pymt_meth_id = pm.pymt_meth_id
                LEFT JOIN payment_status ps ON p.pymt_stat_id = ps.pymt_stat_id
                LEFT JOIN appointment a ON p.appt_id = a.appt_id
                ORDER BY p.pymt_id DESC";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function updatePayment($id, $data) {
        $sql = "UPDATE payment
                SET pymt_amount_paid = ?,
                    pymt_meth_id = ?,
                    pymt_stat_id = ?,
                    appt_id = ?
                WHERE pymt_id = ?";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([
            $data['pymt_amount_paid'],
            $data['pymt_meth_id'],
            $data['pymt_stat_id'],
            $data['appt_id'],
            $id
        ]);
    }

    public function deletePayment($id) {
        $sql = "DELETE FROM payment WHERE pymt_id = :id";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':id', $id);
        return $stmt->execute();
    }

    public function getPaymentById($id) {
        $sql = "SELECT * FROM payment WHERE pymt_id = :id";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
?>
