<?php
class Doctor {
    private $conn;
    private $table_doctor = "doctor";
    private $table_appointment = "appointment";

    public function __construct() {
       $database = new Database();
       $this->conn = $database->connect();
    }

    public function addDoctor($fname, $mname, $lname, $contact, $email, $spec_id) {
        $sql = "INSERT INTO {$this->table_doctor} 
                (doc_first_name, doc_middle_init, doc_last_name, doc_contact_num, doc_email, spec_id, doc_created_at)
                VALUES (:fname, :mname, :lname, :contact, :email, :spec_id, NOW())";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([
            ':fname' => $fname,
            ':mname' => $mname,
            ':lname' => $lname,
            ':contact' => $contact,
            ':email' => $email,
            ':spec_id' => $spec_id
        ]);
    }

    public function updateDoctor($doc_id, $fname, $mname, $lname, $contact, $email, $spec_id) {
        $sql = "UPDATE {$this->table_doctor}
                SET doc_first_name = :fname,
                    doc_middle_init = :mname,
                    doc_last_name = :lname,
                    doc_contact_num = :contact,
                    doc_email = :email,
                    spec_id = :spec_id,
                    doc_updated_at = NOW()
                WHERE doc_id = :id";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([
            ':fname' => $fname,
            ':mname' => $mname,
            ':lname' => $lname,
            ':contact' => $contact,
            ':email' => $email,
            ':spec_id' => $spec_id,
            ':id' => $doc_id
        ]);
    }

    public function deleteDoctor($doc_id) {
        $sql = "DELETE FROM {$this->table_doctor} WHERE doc_id = :id";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([':id' => $doc_id]);
    }

    public function findDocId($doc_id) {
        $sql = "SELECT d.*, s.spec_name 
                FROM {$this->table_doctor} d
                LEFT JOIN specialization s ON d.spec_id = s.spec_id
                WHERE d.doc_id = :id";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([':id' => $doc_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getAllDoctors() {
        $sql = "SELECT d.*, s.spec_name 
                FROM {$this->table_doctor} d
                LEFT JOIN specialization s ON d.spec_id = s.spec_id
                ORDER BY d.doc_last_name ASC";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function viewTodayAppointments($doc_id) {
        $sql = "SELECT a.*, p.pat_first_name, p.pat_last_name, s.serv_name, st.stat_name
                FROM {$this->table_appointment} a
                LEFT JOIN patient p ON a.pat_id = p.pat_id
                LEFT JOIN service s ON a.serv_id = s.serv_id
                LEFT JOIN status st ON a.stat_id = st.stat_id
                WHERE a.doc_id = :doc_id
                AND DATE(a.appt_date) = CURDATE()
                ORDER BY a.appt_time ASC";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([':doc_id' => $doc_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function viewFutureAppointments($doc_id) {
        $sql = "SELECT a.*, p.pat_first_name, p.pat_last_name, s.serv_name, st.stat_name
                FROM {$this->table_appointment} a
                LEFT JOIN patient p ON a.pat_id = p.pat_id
                LEFT JOIN service s ON a.serv_id = s.serv_id
                LEFT JOIN status st ON a.stat_id = st.stat_id
                WHERE a.doc_id = :doc_id
                AND a.appt_date > CURDATE()
                ORDER BY a.appt_date ASC, a.appt_time ASC";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([':doc_id' => $doc_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function viewPreviousAppointments($doc_id) {
        $sql = "SELECT a.*, p.pat_first_name, p.pat_last_name, s.serv_name, st.stat_name
                FROM {$this->table_appointment} a
                LEFT JOIN patient p ON a.pat_id = p.pat_id
                LEFT JOIN service s ON a.serv_id = s.serv_id
                LEFT JOIN status st ON a.stat_id = st.stat_id
                WHERE a.doc_id = :doc_id
                AND a.appt_date < CURDATE()
                ORDER BY a.appt_date DESC";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([':doc_id' => $doc_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>