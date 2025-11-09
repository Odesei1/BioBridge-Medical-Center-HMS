<?php
session_start();
require_once __DIR__ . "/../Config/database.php";

$database = new Database();
$conn = $database->connect();

$errorMsg = '';
$successMsg = '';
$showLogin = false; // 👈 Flag to show login form after registration

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fname     = trim($_POST['fname'] ?? '');
    $mname     = trim($_POST['mname'] ?? '');
    $lname     = trim($_POST['lname'] ?? '');
    $dob       = trim($_POST['dob'] ?? '');
    $gender    = trim($_POST['gender'] ?? '');
    $contact   = trim($_POST['contact'] ?? '');
    $email     = trim($_POST['email'] ?? '');
    $address   = trim($_POST['address'] ?? '');
    $username  = trim($_POST['username'] ?? '');
    $password  = $_POST['password'] ?? '';
    $confirm   = $_POST['confirm'] ?? '';

    if (!$fname || !$lname || !$email || !$username || !$password || !$confirm) {
        $errorMsg = "Please fill in all required fields.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errorMsg = "Invalid email address.";
    } elseif ($password !== $confirm) {
        $errorMsg = "Passwords do not match.";
    } else {
        try {
            // check if email already registered
            $check = $conn->prepare("SELECT pat_id FROM patient WHERE pat_email = ?");
            $check->execute([$email]);
            if ($check->fetch()) {
                $errorMsg = "This email is already registered.";
            } else {
                // Insert into patient
                $insertPatient = $conn->prepare("
                    INSERT INTO patient 
                    (pat_first_name, pat_middle_init, pat_last_name, pat_dob, pat_gender, pat_contact_num, pat_email, pat_address)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $insertPatient->execute([$fname, $mname, $lname, $dob, $gender, $contact, $email, $address]);
                $pat_id = $conn->lastInsertId();

                // Insert into user
                $hashed = password_hash($password, PASSWORD_DEFAULT);
                $insertUser = $conn->prepare("
                    INSERT INTO user (user_name, user_password, pat_id, user_is_superadmin)
                    VALUES (?, ?, ?, 0)
                ");
                $insertUser->execute([$username, $hashed, $pat_id]);

                // ✅ Show success message and login form
                $successMsg = "🎉 Registration complete! You can now log in below.";
                $showLogin = true;
            }
        } catch (Exception $e) {
            $errorMsg = "Error: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<title>Patient Registration - BioBridge</title>
<link rel="icon" type="image/png" href="Assets/BioBridge_Medical_Center_Logo.png">
<script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-gradient-to-r from-sky-50 to-white flex items-center justify-center min-h-screen">
  <div class="bg-white shadow-2xl rounded-2xl p-8 w-full max-w-2xl border-t-4 border-sky-600">
    <h1 class="text-2xl font-bold text-center text-sky-700 mb-4">🩺 Patient Registration & Account Setup</h1>

    <?php if ($errorMsg): ?>
      <p class="text-red-500 text-sm text-center mb-4 font-medium"><?= htmlspecialchars($errorMsg) ?></p>
    <?php elseif ($successMsg): ?>
      <p class="text-green-600 text-sm text-center mb-4 font-medium"><?= htmlspecialchars($successMsg) ?></p>
    <?php endif; ?>

    <!-- Registration Form (Hidden after successful registration) -->
    <?php if (!$showLogin): ?>
    <form method="POST" class="grid grid-cols-1 md:grid-cols-2 gap-4">
      <div>
        <label class="block text-sm font-semibold text-gray-700 mb-1">First Name</label>
        <input type="text" name="fname" required class="w-full border p-2 rounded">
      </div>
      <div>
        <label class="block text-sm font-semibold text-gray-700 mb-1">Middle Initial</label>
        <input type="text" name="mname" class="w-full border p-2 rounded">
      </div>
      <div>
        <label class="block text-sm font-semibold text-gray-700 mb-1">Last Name</label>
        <input type="text" name="lname" required class="w-full border p-2 rounded">
      </div>
      <div>
        <label class="block text-sm font-semibold text-gray-700 mb-1">Date of Birth</label>
        <input type="date" name="dob" class="w-full border p-2 rounded">
      </div>
      <div>
        <label class="block text-sm font-semibold text-gray-700 mb-1">Gender</label>
        <select name="gender" class="w-full border p-2 rounded">
          <option value="">Select</option>
          <option>Male</option>
          <option>Female</option>
          <option>Other</option>
        </select>
      </div>
      <div>
        <label class="block text-sm font-semibold text-gray-700 mb-1">Contact Number</label>
        <input type="text" name="contact" class="w-full border p-2 rounded">
      </div>
      <div>
        <label class="block text-sm font-semibold text-gray-700 mb-1">Email</label>
        <input type="email" name="email" required class="w-full border p-2 rounded">
      </div>
      <div>
        <label class="block text-sm font-semibold text-gray-700 mb-1">Address</label>
        <input type="text" name="address" class="w-full border p-2 rounded">
      </div>

      <div class="col-span-2 border-t my-4"></div>

      <div class="col-span-2">
        <h2 class="text-lg font-semibold text-sky-700 mb-2">Account Credentials</h2>
      </div>

      <div>
        <label class="block text-sm font-semibold text-gray-700 mb-1">Username</label>
        <input type="text" name="username" required class="w-full border p-2 rounded">
      </div>
      <div>
        <label class="block text-sm font-semibold text-gray-700 mb-1">Password</label>
        <input type="password" name="password" required class="w-full border p-2 rounded">
      </div>
      <div>
        <label class="block text-sm font-semibold text-gray-700 mb-1">Confirm Password</label>
        <input type="password" name="confirm" required class="w-full border p-2 rounded">
      </div>

      <div class="col-span-2 mt-4">
        <button type="submit" class="w-full bg-sky-700 hover:bg-sky-800 text-white py-2 rounded-lg font-medium transition">
          Register & Create Account
        </button>
      </div>
    </form>
    <?php endif; ?>

    <!-- ✅ Show login form after registration -->
    <?php if ($showLogin): ?>
      <div class="mt-10 border-t pt-6">
        <h2 class="text-2xl font-bold text-center text-sky-700 mb-4">Sign in to Your Account</h2>
        <form action="login_register.php" method="post" class="space-y-4 max-w-md mx-auto">
          <input type="text" name="username" placeholder="Username" required
                 class="w-full p-2 bg-gray-100 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-sky-600" />
          <div class="relative">
            <input id="login-password" type="password" name="password" placeholder="Password" required
                   class="w-full p-2 bg-gray-100 border border-gray-300 rounded pr-10 focus:outline-none focus:ring-2 focus:ring-sky-600" />
            <button type="button" onclick="togglePassword('login-password', this)"
                    class="absolute inset-y-0 right-0 px-3 flex items-center text-gray-500 focus:outline-none">👁️</button>
          </div>
          <button type="submit" name="login"
                  class="w-full bg-sky-600 hover:bg-sky-700 text-white p-2 rounded transition">
            Login
          </button>
        </form>
      </div>

      <script>
      function togglePassword(inputId, btn) {
        const input = document.getElementById(inputId);
        const isPassword = input.type === 'password';
        input.type = isPassword ? 'text' : 'password';
        btn.textContent = isPassword ? '🙈' : '👁️';
      }
      </script>
    <?php endif; ?>

    <div class="text-center mt-4">
      <a href="../index.php" class="text-sky-600 hover:underline text-sm">← Back to Home Page</a>
    </div>
  </div>
</body>
</html>
