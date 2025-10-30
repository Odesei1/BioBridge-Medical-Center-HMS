<?php
class Staff {
    private $conn;
    private $table = "staff";

    public function __construct($db) {
        $this->conn = $db;
    }

    // ➕ Add new staff
    public function add($fname, $lname, $mid_init, $contact, $email) {
        $sql = "INSERT INTO {$this->table} 
                (staff_first_name, staff_last_name, staff_middle_init, staff_contact_num, staff_email, staff_created_at)
                VALUES (:fname, :lname, :mid_init, :contact, :email, NOW())";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([
            ":fname" => $fname,
            ":lname" => $lname,
            ":mid_init" => $mid_init,
            ":contact" => $contact,
            ":email" => $email
        ]);
    }

    // 🔍 Search staff by name
    public function searchByName($keyword) {
        $sql = "SELECT * FROM {$this->table}
                WHERE staff_first_name LIKE :keyword OR staff_last_name LIKE :keyword
                ORDER BY staff_last_name ASC";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([":keyword" => "%$keyword%"]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // 📋 View all staff
    public function all() {
        $sql = "SELECT * FROM {$this->table} ORDER BY staff_created_at DESC";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // ❌ Delete staff by ID
public function delete($staff_id) {
    try {
        // 1️⃣ Delete the record
        $sql = "DELETE FROM {$this->table} WHERE staff_id = :staff_id";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([":staff_id" => $staff_id]);

        // 2️⃣ Get the current maximum staff_id
        $maxSql = "SELECT MAX(staff_id) AS max_id FROM {$this->table}";
        $maxStmt = $this->conn->query($maxSql);
        $maxRow = $maxStmt->fetch(PDO::FETCH_ASSOC);
        $nextId = ($maxRow['max_id'] ?? 0) + 1;

        // 3️⃣ Reset the AUTO_INCREMENT counter to max_id + 1
        $resetSQL = "ALTER TABLE {$this->table} AUTO_INCREMENT = {$nextId}";
        $this->conn->exec($resetSQL);

        return true;
    } catch (PDOException $e) {
        echo "Error deleting record: " . $e->getMessage();
        return false;
    }
}



    // ✏️ Update staff details
    public function update($staff_id, $fname, $lname, $mid_init, $contact, $email) {
        $sql = "UPDATE {$this->table}
                SET staff_first_name = :fname,
                    staff_last_name = :lname,
                    staff_middle_init = :mid_init,
                    staff_contact_num = :contact,
                    staff_email = :email,
                    staff_updated_at = NOW()
                WHERE staff_id = :staff_id";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([
            ":fname" => $fname,
            ":lname" => $lname,
            ":mid_init" => $mid_init,
            ":contact" => $contact,
            ":email" => $email,
            ":staff_id" => $staff_id
        ]);
    }
}
?>
