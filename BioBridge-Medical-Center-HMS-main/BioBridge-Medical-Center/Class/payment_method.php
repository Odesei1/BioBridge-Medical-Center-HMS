<?php
require_once __DIR__ . "/../config/database.php";

class PaymentMethod{
    private $conn;
    private $table = "payment_method";

    public function __construct(){
        $database = new Database();
        $this->conn = $database->connect();
    }
    public function addPaymentMethod($data){
        $sql = "INSERT INTO payment_method(pymt_meth_name) VALUES(?)";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([
            $data['pymt_meth_name']
        ]);
    }

    public function getAllPaymentMethods(){
        $sql = "SELECT pymt_meth_id, pymt_meth_name FROM payment_method";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        return $stmt;
    }

    public function getPaymentMethodById($id) {
        $sql = "SELECT pymt_meth_id, pymt_meth_name FROM payment_method WHERE pymt_meth_id = :id";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);  // Fetch single record as associative array
    }

    public function updatePaymentMethod($id, $data){
        $sql = "UPDATE payment_method SET pymt_meth_name = ? WHERE pymt_meth_id = ?";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([
            $data['pymt_meth_name'],
            $id
        ]);
    }

    public function deletePaymentMethod($id){
        $sql = "DELETE FROM payment_method WHERE pymt_meth_id = :id";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':id', $id);
        return $stmt->execute();
    }
 }
?>