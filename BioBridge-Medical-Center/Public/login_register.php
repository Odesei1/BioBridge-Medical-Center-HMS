<?php
session_start();
require_once __DIR__ . "/../Config/database.php";
require_once __DIR__ . "/../Class/user.php";

$database = new Database();
$conn = $database->connect();

/* ==============================================================
   🧩 REGISTER
   ============================================================== */
if (isset($_POST['register'])) {
    $name = trim($_POST['username']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

    // Check if username/email already exists
    $stmt = $conn->prepare("SELECT user_name FROM user WHERE user_name = ?");
    $stmt->execute([$name]);
    if ($stmt->rowCount() > 0) {
        $_SESSION['register_error'] = 'Username is already taken.';
        $_SESSION['active_form'] = 'register';
        header("Location: login.php");
        exit();
    }

    // Determine which table this email belongs to
    $role = null;
    $roleId = null;

    // Check Doctor
    $check = $conn->prepare("SELECT doc_id FROM doctor WHERE doc_email = ?");
    $check->execute([$name]);
    if ($row = $check->fetch(PDO::FETCH_ASSOC)) {
        $role = 'doctor';
        $roleId = $row['doc_id'];
    }

    // Check Staff
    if (!$role) {
        $check = $conn->prepare("SELECT staff_id FROM staff WHERE staff_email = ?");
        $check->execute([$name]);
        if ($row = $check->fetch(PDO::FETCH_ASSOC)) {
            $role = 'staff';
            $roleId = $row['staff_id'];
        }
    }

    // Check Patient
    if (!$role) {
        $check = $conn->prepare("SELECT pat_id FROM patient WHERE pat_email = ?");
        $check->execute([$name]);
        if ($row = $check->fetch(PDO::FETCH_ASSOC)) {
            $role = 'patient';
            $roleId = $row['pat_id'];
        }
    }

    // Insert new user based on detected role
    if ($role === 'doctor') {
        $stmt = $conn->prepare("INSERT INTO user (user_name, user_password, doc_id, user_is_superadmin) VALUES (?, ?, ?, 0)");
        $stmt->execute([$name, $password, $roleId]);
    } elseif ($role === 'staff') {
        $stmt = $conn->prepare("INSERT INTO user (user_name, user_password, staff_id, user_is_superadmin) VALUES (?, ?, ?, 0)");
        $stmt->execute([$name, $password, $roleId]);
    } elseif ($role === 'patient') {
        $stmt = $conn->prepare("INSERT INTO user (user_name, user_password, pat_id, user_is_superadmin) VALUES (?, ?, ?, 0)");
        $stmt->execute([$name, $password, $roleId]);
    } else {
        $_SESSION['register_error'] = 'Account not found in records.';
        $_SESSION['active_form'] = 'register';
        header("Location: ../BioBridge-Medical-Center/index.php");
        exit();
    }

    // ✅ Success
    $_SESSION['register_success'] = ucfirst($role) . ' account successfully registered!';
    header("Location: ../BioBridge-Medical-Center/index.php");
    exit();
}

/* ==============================================================
   🔐 LOGIN
   ============================================================== */
if (isset($_POST['login'])) {
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT * FROM user WHERE user_name = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($password, $user['user_password'])) {
        // Determine role
        if ($user['user_is_superadmin'] == 1) {
            $role = 'superadmin';
        } elseif (!is_null($user['doc_id'])) {
            $role = 'doctor';
        } elseif (!is_null($user['staff_id'])) {
            $role = 'staff';
        } elseif (!is_null($user['pat_id'])) {
            $role = 'patient';
        } else {
            $role = 'unknown';
        }

        // ✅ Store session data
        $_SESSION['user_id'] = $user['user_id'];
        $_SESSION['username'] = $user['user_name'];
        $_SESSION['role'] = $role;

        // ✅ Store role-specific IDs
        if ($role === 'patient') {
            $_SESSION['pat_id'] = $user['pat_id'];
        } elseif ($role === 'doctor') {
            $_SESSION['doc_id'] = $user['doc_id']; // ✅ this fixes your doctor dashboard issue
        } elseif ($role === 'staff') {
            $_SESSION['staff_id'] = $user['staff_id'];
        }

        // Update last login timestamp
        $update = $conn->prepare("UPDATE user SET user_last_login = NOW() WHERE user_id = ?");
        $update->execute([$user['user_id']]);

        // ✅ Redirect based on role
        switch ($role) {
            case 'superadmin':
                header("Location: /../Public/superadmin_dashboard.php");
                break;
            case 'doctor':
                header("Location: /../Public/doctor_dashboard.php");
                break;
            case 'staff':
                header("Location: /../Public/staff_dashboard.php");
                break;
            case 'patient':
                header("Location: /../Public/patient_dashboard.php");
                break;
            default:
                header("Location: /../Public/access_denied.php");
                break;
        }
        exit();
    } else {
        $_SESSION['login_error'] = "Incorrect username or password.";
        header("Location: ../BioBridge-Medical-Center/index.php");
        exit();
    }
}
?>
