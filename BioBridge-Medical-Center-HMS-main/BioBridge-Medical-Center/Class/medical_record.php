<?php
require_once __DIR__ . "/../config/database.php";

class MedicalRecords {
    private $conn;
    private $table = "medical_record"; // Make sure this matches your DB

    public function __construct() {
        $database = new Database();
        $this->conn = $database->connect();
    }

    public function addRecord($data){
        $sql = "INSERT INTO {$this->table} (med_rec_diagnosis, med_rec_prescription, med_rec_visit_date, appt_id) 
                VALUES (?, ?, ?, ?)";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([
            $data['med_rec_diagnosis'],
            $data['med_rec_prescription'],
            $data['med_rec_visit_date'],
            $data['appt_id']
        ]);
    }

    public function getAllRecords(){
        $sql = "SELECT 
                    m.med_rec_id,
                    m.med_rec_diagnosis,
                    m.med_rec_prescription,
                    m.med_rec_visit_date,
                    a.appt_id,
                    p.pat_first_name,
                    p.pat_last_name,
                    d.doc_first_name,
                    d.doc_last_name
                FROM {$this->table} m
                LEFT JOIN appointment a ON m.appt_id = a.appt_id
                LEFT JOIN patient p ON a.pat_id = p.pat_id
                LEFT JOIN doctor d ON a.doc_id = d.doc_id
                ORDER BY m.med_rec_visit_date DESC";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function updateRecord($id, $data){
        $sql = "UPDATE {$this->table}
                SET
                    med_rec_diagnosis = ?,
                    med_rec_prescription = ?,
                    med_rec_visit_date = ?,
                    appt_id = ?
                WHERE med_rec_id = ?";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([
            $data['med_rec_diagnosis'],
            $data['med_rec_prescription'],
            $data['med_rec_visit_date'],
            $data['appt_id'],
            $id
        ]);
    }

    public function deleteRecord($id){
        $sql = "DELETE FROM {$this->table} WHERE med_rec_id = ?";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([$id]);           
    }
}
?>
