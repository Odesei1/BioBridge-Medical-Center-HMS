<?php
class User {
    private $conn;
    private $table_name = "user";

    // Table columns
    public $USER_ID;
    public $USER_EMAIL;
    public $USER_PASSWORD;
    public $USER_IS_SUPERADMIN;
    public $PAT_ID;
    public $STAFF_ID;
    public $DOC_ID;

    public function __construct($db) {
        $this->conn = $db;
    }

    // REGISTER FUNCTION (for doctor or staff)
    public function register() {
        // Check if email already exists
        $query = "SELECT USER_EMAIL FROM " . $this->table_name . " WHERE USER_EMAIL = :email";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":email", $this->USER_EMAIL);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            return "exists"; // Email already registered
        }

        // Insert new user
        $query = "INSERT INTO " . $this->table_name . " 
                  (USER_EMAIL, USER_PASSWORD, USER_IS_SUPERADMIN, PAT_ID, STAFF_ID, DOC_ID)
                  VALUES (:USER_EMAIL, :USER_PASSWORD, :USER_IS_SUPERADMIN, :PAT_ID, :STAFF_ID, :DOC_ID)";

        $stmt = $this->conn->prepare($query);
        $hashed_password = password_hash($this->USER_PASSWORD, PASSWORD_DEFAULT);

        $stmt->bindParam(":USER_EMAIL", $this->USER_EMAIL);
        $stmt->bindParam(":USER_PASSWORD", $hashed_password);
        $stmt->bindParam(":USER_IS_SUPERADMIN", $this->USER_IS_SUPERADMIN);
        $stmt->bindParam(":PAT_ID", $this->PAT_ID);
        $stmt->bindParam(":STAFF_ID", $this->STAFF_ID);
        $stmt->bindParam(":DOC_ID", $this->DOC_ID);

        if ($stmt->execute()) {
            return "success";
        }
        return "error";
    }

    // LOGIN VALIDATION
    public function login() {
        $query = "SELECT * FROM " . $this->table_name . " WHERE USER_EMAIL = :email LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":email", $this->USER_EMAIL);
        $stmt->execute();

        if ($stmt->rowCount() == 1) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if (password_verify($this->USER_PASSWORD, $row['USER_PASSWORD'])) {
                // Determine user type
                if ($row['DOC_ID'] !== null) {
                    return "doctor";
                } elseif ($row['STAFF_ID'] !== null) {
                    return "staff";
                } elseif ($row['PAT_ID'] !== null) {
                    return "patient";
                } elseif ($row['USER_IS_SUPERADMIN'] == 1) {
                    return "superadmin";
                } else {
                    return "unknown";
                }
            } else {
                return "invalid_password";
            }
        } else {
            return "no_user";
        }
    }
}
?>