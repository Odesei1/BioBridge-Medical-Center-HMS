<?php
class Status {
    private $conn;
    private $table = "status";

    public function __construct($db){
        $this->conn = $db;
    }

    // ✅ Add new status
    public function add($stat_name){
        $sql = "INSERT INTO {$this->table} (stat_name, stat_created_at)
                VALUES (:stat_name, NOW())";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([
            ":stat_name" => $stat_name
        ]);
    }

    // ✅ View all statuses with order by column (default stat_id)
    public function all($orderBy = 'stat_id'){
        $allowed = ['stat_id', 'stat_name', 'stat_created_at', 'stat_updated_at']; // whitelist
        if (!in_array($orderBy, $allowed)) {
            $orderBy = 'stat_id';
        }
        $sql = "SELECT * FROM {$this->table} ORDER BY {$orderBy} ASC";
        $stmt = $this->conn->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // ✅ Update status
    public function update($stat_id, $stat_name){
        $sql = "UPDATE {$this->table}
                SET stat_name = :stat_name,
                    stat_updated_at = NOW()
                WHERE stat_id = :stat_id";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([
            ":stat_name" => $stat_name,
            ":stat_id" => $stat_id
        ]);
    }

    // ✅ Delete status
    public function delete($stat_id){
        $sql = "DELETE FROM {$this->table} WHERE stat_id = :stat_id";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([
            ":stat_id" => $stat_id
        ]);
    }
}
?>
