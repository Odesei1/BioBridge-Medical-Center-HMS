<?php
class Appointment {
    private $conn;
    private $table = "appointment";

    public function __construct($db) {
        $this->conn = $db;
    }

    // ✅ Generate Appointment ID in the format YYYY-MM-0000001
    private function generateAppointmentID($appt_date) {
        $year  = date('Y', strtotime($appt_date));
        $month = date('m', strtotime($appt_date));

        try {
            // Start a transaction to prevent race conditions
            $this->conn->beginTransaction();

            // Fetch the most recent appointment ID
            $sql = "SELECT appt_id 
                    FROM {$this->table}
                    ORDER BY appt_created_at DESC, appt_id DESC
                    LIMIT 1
                    FOR UPDATE"; 
            $stmt = $this->conn->prepare($sql);
            $stmt->execute();
            $lastID = $stmt->fetchColumn();

            if ($lastID) {
                // Extract last sequence from the previous ID
                preg_match('/(\d{7})$/', $lastID, $matches);
                $lastSeq = isset($matches[1]) ? intval($matches[1]) : 0;
                $newSeq  = str_pad($lastSeq + 1, 7, "0", STR_PAD_LEFT);
            } else {
                $newSeq = str_pad(1, 7, "0", STR_PAD_LEFT);
            }

            // ✅ Format: YYYY-MM-0000001
            $appt_id = "$year-$month-$newSeq";

            $this->conn->commit();
            return $appt_id;

        } catch (PDOException $e) {
            $this->conn->rollBack();
            throw new Exception("Failed to generate Appointment ID: " . $e->getMessage());
        }
    }

    // ✅ Create new appointment
    public function create($pat_id, $doc_id, $serv_id, $appt_date, $appt_time) {
        try {
            // Get status ID for “Scheduled”
            $stmt = $this->conn->prepare("SELECT stat_id FROM status WHERE stat_name = 'Scheduled' LIMIT 1");
            $stmt->execute();
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $stat_id = $row ? $row['stat_id'] : 1; // fallback if missing

            // Generate unique Appointment ID
            $appt_id = $this->generateAppointmentID($appt_date);

            // Insert appointment record
            $sql = "INSERT INTO {$this->table} 
                    (appt_id, pat_id, doc_id, serv_id, stat_id, appt_date, appt_time, appt_created_at)
                    VALUES (:appt_id, :pat_id, :doc_id, :serv_id, :stat_id, :appt_date, :appt_time, NOW())";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([
                ":appt_id"   => $appt_id,
                ":pat_id"    => $pat_id,
                ":doc_id"    => $doc_id,
                ":serv_id"   => $serv_id,
                ":stat_id"   => $stat_id,
                ":appt_date" => $appt_date,
                ":appt_time" => $appt_time
            ]);

            return $appt_id;
        } catch (PDOException $e) {
            throw new Exception("Database Error: " . $e->getMessage());
        }
    }

    // ✅ Find appointment by ID
    public function findByID($appt_id) {
        $sql = "SELECT a.*, 
                       p.pat_first_name, p.pat_last_name,
                       d.doc_first_name, d.doc_last_name,
                       s.serv_name, st.stat_name
                FROM {$this->table} a
                JOIN patient p ON a.pat_id = p.pat_id
                JOIN doctor d ON a.doc_id = d.doc_id
                JOIN service s ON a.serv_id = s.serv_id
                JOIN status st ON a.stat_id = st.stat_id
                WHERE a.appt_id = :appt_id";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([":appt_id" => $appt_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    // ✅ Update appointment details
    public function update($appt_id, $doc_id, $serv_id, $appt_date, $appt_time) {
        $sql = "UPDATE {$this->table}
                SET doc_id = :doc_id,
                    serv_id = :serv_id,
                    appt_date = :appt_date,
                    appt_time = :appt_time,
                    appt_updated_at = NOW()
                WHERE appt_id = :appt_id";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([
            ":doc_id"    => $doc_id,
            ":serv_id"   => $serv_id,
            ":appt_date" => $appt_date,
            ":appt_time" => $appt_time,
            ":appt_id"   => $appt_id
        ]);
    }

    // ✅ Cancel appointment (status → “Cancelled”)
    public function cancel($appt_id) {
        $sql = "UPDATE {$this->table}
                SET stat_id = (SELECT stat_id FROM status WHERE stat_name = 'Cancelled' LIMIT 1),
                    appt_updated_at = NOW()
                WHERE appt_id = :appt_id";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([":appt_id" => $appt_id]);
    }

    // ✅ Manually update status
    public function updateStatus($appt_id, $stat_id) {
        $sql = "UPDATE {$this->table}
                SET stat_id = :stat_id,
                    appt_updated_at = NOW()
                WHERE appt_id = :appt_id";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([
            ":stat_id" => $stat_id,
            ":appt_id" => $appt_id
        ]);
    }
}
?>
