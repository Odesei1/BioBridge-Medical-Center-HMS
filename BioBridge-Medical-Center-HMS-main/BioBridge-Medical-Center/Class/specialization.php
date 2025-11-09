<?php
class Specialization{
    private $conn;
    private $table = "specialization";

public function __construct(){
    $database = new Database();
    $this->conn = $database->connect();
}
public function add($SPEC_NAME){
    $sql = "INSERT INTO {$this->table} (SPEC_NAME, SPEC_CREATED_AT) 
            VALUES (:SPEC_NAME, NOW())";
    $stmt = $this->conn->prepare($sql);
    return $stmt->execute([":SPEC_NAME" => $SPEC_NAME]);
}

public function all(){
    $sql = "SELECT * FROM {$this->table}";
    $stmt = $this->conn->query($sql);
    return $stmt->fetchAll(PDO::FETCH_ASSOC); 
}

public function findID($SPEC_ID){
    $sql = "SELECT * FROM {$this->table} WHERE SPEC_ID = :SPEC_ID";
    $stmt = $this->conn->prepare($sql);
    $stmt->execute([":SPEC_ID" => $SPEC_ID]);
    return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
}
// ✅ View doctors who specialize in a specific field (e.g., Internal Medicine)
public function getDoctorsBySpecialization($SPEC_NAME){
    $sql = "SELECT d.DOC_ID, d.DOC_FIRST_NAME, d.DOC_LAST_NAME, d.DOC_EMAIL, d.DOC_CONTACT_NUM 
            FROM doctor d
            INNER JOIN specialization s ON d.SPEC_ID = s.SPEC_ID
            WHERE s.SPEC_NAME = :SPEC_NAME";
    $stmt = $this->conn->prepare($sql);
    $stmt->execute([":SPEC_NAME" => $SPEC_NAME]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

public function update($SPEC_ID, $SPEC_NAME){
    $sql = "UPDATE {$this->table} 
        SET SPEC_NAME = :SPEC_NAME, SPEC_UPDATED_AT = NOW() 
        WHERE SPEC_ID = :SPEC_ID";
    $stmt = $this->conn->prepare($sql);
    return $stmt->execute([
        ":SPEC_NAME" => $SPEC_NAME,
        ":SPEC_ID" => $SPEC_ID
        ]);
    }

public function delete($SPEC_ID){
    $sql = "DELETE FROM {$this->table} WHERE SPEC_ID = :SPEC_ID";
    $stmt = $this->conn->prepare($sql);
    return $stmt->execute([":SPEC_ID" => $SPEC_ID]);
    }
}
?>