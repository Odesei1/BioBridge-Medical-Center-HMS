<?php
class Patient {
    private $conn;
    private $table = "patient";

    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }

    // ➕ Add new patient
    public function add($fname, $midInit, $lname, $dob, $gender, $contact, $email, $address) {
        $stmt = $this->conn->prepare("INSERT INTO {$this->table} 
            (pat_first_name, pat_middle_init, pat_last_name, pat_dob, pat_gender, pat_contact_num, pat_email, pat_address, pat_created_at) 
            VALUES (:fname, :midInit, :lname, :dob, :gender, :contact, :email, :address, NOW())");
        return $stmt->execute([
            ':fname' => $fname,
            ':midInit' => $midInit,
            ':lname' => $lname,
            ':dob' => $dob,
            ':gender' => $gender,
            ':contact' => $contact,
            ':email' => $email,
            ':address' => $address
        ]);
    }

    // 🔍 Search patient by name (limit columns)
    public function searchByName($keyword) {
        $sql = "SELECT pat_id, pat_first_name, pat_middle_init, pat_last_name, pat_gender, pat_address 
                FROM {$this->table}
                WHERE pat_first_name LIKE :keyword OR pat_last_name LIKE :keyword
                ORDER BY pat_created_at ASC"; // oldest first
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([":keyword" => "%$keyword%"]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // 👀 View all patients (limited fields, oldest first)
    public function viewAll() {
        $sql = "SELECT pat_id, pat_first_name, pat_middle_init, pat_last_name, pat_gender, pat_address 
                FROM {$this->table} 
                ORDER BY pat_created_at ASC";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // ❌ Delete patient
    public function delete($pat_id) {
        try {
            $sql = "DELETE FROM {$this->table} WHERE pat_id = :pat_id";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([":pat_id" => $pat_id]);

            $maxSql = "SELECT MAX(pat_id) AS max_id FROM {$this->table}";
            $maxStmt = $this->conn->query($maxSql);
            $maxRow = $maxStmt->fetch(PDO::FETCH_ASSOC);
            $nextId = ($maxRow['max_id'] ?? 0) + 1;
            $resetSQL = "ALTER TABLE {$this->table} AUTO_INCREMENT = {$nextId}";
            $this->conn->exec($resetSQL);
            return true;
        } catch (PDOException $e) {
            echo "Error deleting patient record: " . $e->getMessage();
            return false;
        }
    }

    // 🚫 Cancel appointment
    public function cancel($appt_id) {
        $sql = "UPDATE appointment 
                SET stat_id = (SELECT stat_id FROM status WHERE stat_name = 'Cancelled' LIMIT 1)
                WHERE appt_id = ?";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([$appt_id]);
    }

    // ✏️ Update patient info
    public function update($PAT_ID, $PAT_FNAME, $PAT_MID_INIT, $PAT_LNAME, $PAT_DOB, $PAT_GENDER, $PAT_CONTACT, $PAT_EMAIL, $PAT_ADDRESS) {
        $sql = "UPDATE {$this->table}
                SET pat_first_name = :PAT_FNAME,
                    pat_middle_init = :PAT_MID_INIT,
                    pat_last_name = :PAT_LNAME,
                    pat_dob = :PAT_DOB,
                    pat_gender = :PAT_GENDER,
                    pat_contact_num = :PAT_CONTACT,
                    pat_email = :PAT_EMAIL,
                    pat_address = :PAT_ADDRESS,
                    pat_updated_at = NOW()
                WHERE pat_id = :PAT_ID";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([
            ":PAT_FNAME" => $PAT_FNAME,
            ":PAT_MID_INIT" => $PAT_MID_INIT,
            ":PAT_LNAME" => $PAT_LNAME,
            ":PAT_DOB" => $PAT_DOB,
            ":PAT_GENDER" => $PAT_GENDER,
            ":PAT_CONTACT" => $PAT_CONTACT,
            ":PAT_EMAIL" => $PAT_EMAIL,
            ":PAT_ADDRESS" => $PAT_ADDRESS,
            ":PAT_ID" => $PAT_ID
        ]);
    }
}
?>
