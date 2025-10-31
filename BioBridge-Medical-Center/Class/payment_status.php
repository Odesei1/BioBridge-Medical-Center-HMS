<?php
require_once __DIR__ . "/../config/database.php";

class PaymentStatus{
    private $conn;
    private $table = "payment_status";

    public function __construct(){
        $database = new Database();
        $this->conn = $database->connect();
    }
    public function addPaymentStatus($data){
        $sql = "INSERT INTO payment_status(pymt_stat_name) VALUES(?)";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([
            $data['pymt_meth_name']
        ]);
    }

    public function getAllPaymentStatus(){
        $sql = "SELECT pymt_stat_id, pymt_stat_name FROM payment_status";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        return $stmt;
    }

    public function getPaymentStatusById($id) {
        $sql = "SELECT pymt_stat_id, pymt_stat_name FROM payment_status WHERE pymt_stat_id = :id";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);  
    }

    public function updatePaymentStatus($id, $data){
        $sql = "UPDATE payment_status SET pymt_stat_name = ? WHERE pymt_stat_id = ?";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([
            $data['pymt_meth_name'],
            $id
        ]);
    }

    public function deletePaymentStatus($id){
        $sql = "DELETE FROM payment_status WHERE pymt_stat_id = :id";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':id', $id);
        return $stmt->execute();
    }
 }
?>