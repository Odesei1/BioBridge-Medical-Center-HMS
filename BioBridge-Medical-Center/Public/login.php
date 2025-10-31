<?php
session_start();

$loginError = $_SESSION['login_error'] ?? '';
$activeForm = $_SESSION['active_form'] ?? 'login';

unset($_SESSION['login_error'], $_SESSION['active_form']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>BioBridge Medical Center Login Page</title>

  <link
      rel="icon"
      type="image/png"
      href="../Assets/BioBridge_Medical_Center_Logo.png"
    />
    
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-blue-100 min-h-screen flex items-center justify-center">

  <!-- Logo or header here -->
  <header class="absolute top-6 left-6">
    <img src="Assets/BioBridgeMedicalCenter.png" alt="BioBridge Medical Center" class="h-16" />
  </header>

  <!-- Login form container -->
  <div class="shadow-lg rounded-xl bg-white p-8 w-full max-w-md mx-auto">
    <div class="<?= $activeForm === 'login' ? 'block' : 'hidden' ?>" id="login-form">
      <form action="login_register.php" method="post" class="space-y-6">
        <h2 class="text-2xl font-bold text-center">Log In</h2>

        <!-- Show error if login failed -->
        <?php if (!empty($loginError)): ?>
          <p class="text-red-500 text-sm text-center"><?= htmlspecialchars($loginError) ?></p>
        <?php endif; ?>

        <input type="email" name="email" placeholder="Email" required
               class="w-full p-3 bg-gray-100 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-blue-400" />
        <input type="password" name="password" placeholder="Password" required
               class="w-full p-3 bg-gray-100 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-blue-400" />
        <button type="submit" name="login"
                class="w-full bg-blue-600 hover:bg-blue-700 text-white p-3 rounded font-semibold transition">
          Login
        </button>

        <p class="text-sm text-center">
          Don't have an account?
          <a href="#" onclick="showForm('register')" class="text-blue-600 hover:underline">Register</a>
        </p>
      </form>
    </div>
  </div>

  <footer class="absolute bottom-4 w-full text-center text-gray-500 text-sm">
    <p>&copy; 2025 BioBridge Medical Center. All rights reserved.</p>
  </footer>

  <script src="login.js"></script>
</body>
</html>
