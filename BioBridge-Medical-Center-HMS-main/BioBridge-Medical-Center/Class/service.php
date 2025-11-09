<?php
class Service {
    private $conn;
    private $table = "service";

    public function __construct(){
        $database = new Database();
        $this->conn = $database->connect();
    }

    // ➕ Add new service with price
    public function add($name, $desc, $price = 0){
        $sql = "INSERT INTO {$this->table} (serv_name, serv_description, serv_price, serv_created_at)
                VALUES (:name, :desc, :price, NOW())";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([
            ":name" => $name,
            ":desc" => $desc,
            ":price" => $price
        ]);
    }

    // 📋 View all services in order by serv_id
    public function all(){
        $sql = "SELECT * FROM {$this->table} ORDER BY serv_id ASC";
        $stmt = $this->conn->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // 📅 View appointments by service
    public function getAppointmentsByService($id){
        $sql = "SELECT a.appt_id, a.appt_date, a.appt_time, 
                       p.pat_first_name, p.pat_last_name, 
                       d.doc_first_name, d.doc_last_name, 
                       s.serv_name, st.stat_name
                FROM appointment a
                JOIN patient p ON a.pat_id = p.pat_id
                JOIN doctor d ON a.doc_id = d.doc_id
                JOIN service s ON a.serv_id = s.serv_id
                JOIN status st ON a.stat_id = st.stat_id
                WHERE s.serv_id = :id
                ORDER BY a.appt_date DESC, a.appt_time DESC";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([":id" => $id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // ✏️ Update service including price
    public function update($id, $name, $desc, $price = 0){
        $sql = "UPDATE {$this->table}
                SET serv_name = :name,
                    serv_description = :desc,
                    serv_price = :price,
                    serv_updated_at = NOW()
                WHERE serv_id = :id";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([
            ":name" => $name,
            ":desc" => $desc,
            ":price" => $price,
            ":id" => $id
        ]);
    }
}
?>
