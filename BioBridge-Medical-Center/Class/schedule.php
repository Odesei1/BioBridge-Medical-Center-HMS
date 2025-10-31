<?php
class Schedule {
    private $conn;
    private $table = "schedule";

    public function __construct($db){
        $this->conn = $db;
    }

    public function add($DOC_ID, $SCHED_DAYS, $SCHED_START_TIME, $SCHED_END_TIME){
        $sql = "INSERT INTO {$this->table} (DOC_ID, SCHED_DAYS, SCHED_START_TIME, SCHED_END_TIME, SCHED_CREATED_AT)
                VALUES (:DOC_ID, :SCHED_DAYS, :SCHED_START_TIME, :SCHED_END_TIME, NOW())";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([
            ":DOC_ID" => $DOC_ID,
            ":SCHED_DAYS" => $SCHED_DAYS,
            ":SCHED_START_TIME" => $SCHED_START_TIME,
            ":SCHED_END_TIME" => $SCHED_END_TIME
        ]);
    }

    //  View all schedules (with doctor info)
    public function all(){
        $sql = "SELECT s.SCHED_ID, s.SCHED_DAYS, s.SCHED_START_TIME, s.SCHED_END_TIME,
                       d.DOC_FIRST_NAME, d.DOC_LAST_NAME
                FROM {$this->table} s
                INNER JOIN doctor d ON s.DOC_ID = d.DOC_ID
                ORDER BY s.SCHED_DAYS ASC";
        $stmt = $this->conn->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    //  View today's schedules (based on current weekday)
    public function today(){
        $sql = "SELECT s.SCHED_ID, s.SCHED_DAYS, s.SCHED_START_TIME, s.SCHED_END_TIME,
                       d.DOC_FIRST_NAME, d.DOC_LAST_NAME
                FROM {$this->table} s
                INNER JOIN doctor d ON s.DOC_ID = d.DOC_ID
                WHERE s.SCHED_DAYS = DAYNAME(CURDATE())";
        $stmt = $this->conn->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function update($SCHED_ID, $DOC_ID, $SCHED_DAYS, $SCHED_START_TIME, $SCHED_END_TIME){
        $sql = "UPDATE {$this->table}
                SET DOC_ID = :DOC_ID,
                    SCHED_DAYS = :SCHED_DAYS,
                    SCHED_START_TIME = :SCHED_START_TIME,
                    SCHED_END_TIME = :SCHED_END_TIME,
                    SCHED_UPDATED_AT = NOW()
                WHERE SCHED_ID = :SCHED_ID";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([
            ":DOC_ID" => $DOC_ID,
            ":SCHED_DAYS" => $SCHED_DAYS,
            ":SCHED_START_TIME" => $SCHED_START_TIME,
            ":SCHED_END_TIME" => $SCHED_END_TIME,
            ":SCHED_ID" => $SCHED_ID
        ]);
    }

    public function delete($SCHED_ID){
        $sql = "DELETE FROM {$this->table} WHERE SCHED_ID = :SCHED_ID";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([":SCHED_ID" => $SCHED_ID]);
    }
}
?>