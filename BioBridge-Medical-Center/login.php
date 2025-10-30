<?php
session_start();

$errors = [
  'login' => $_SESSION['login_error'] ?? '',
  'register' => $_SESSION['register_error'] ?? ''
];
$activeForm = $_SESSION['active_form'] ?? 'login';

function showError($error) {
  return !empty($error) ? "<p class='text-red-500 text-sm mb-4 text-center'>$error</p>" : '';
}

function isHidden($formname, $activeForm) {
  return $formname === $activeForm ? '' : 'hidden';
}

unset($_SESSION['login_error'], $_SESSION['register_error'], $_SESSION['active_form']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>BioBridge Medical Center</title>
   <link
      rel="icon"
      type="image/png"
      href="../Assets/BioBridge_Medical_Center_Logo.png"
    />
  <script src="https://cdn.tailwindcss.com"></script>

  <style>
    .transition-section {
      transition: all 0.8s ease-in-out;
    }
    .translate-left {
      transform: translateX(-100%);
      opacity: 0;
      pointer-events: none;
    }
    .translate-center {
      transform: translateX(0);
      opacity: 1;
      pointer-events: auto;
    }
  </style>
</head>

<body class="flex min-h-screen bg-white text-gray-900 overflow-hidden">

  <!-- ========== CONTAINER ========== -->
  <div id="container" class="relative flex w-full transition-section">

    <!-- ========== SECTION 1: Landing Page ========== -->
    <section id="landing" class="transition-section translate-center flex w-full min-h-screen">
      <div class="hidden md:flex md:w-1/2">
        <img src="Assets/BioBridge_Medical_Center_Info.png" alt="Clinic Info" class="w-full h-full object-cover" />
      </div>

      <div class="flex flex-col justify-center items-center text-center p-8 w-full md:w-1/2 space-y-6">
        <img src="Assets/BioBridgeMedicalCenter.png" alt="BioBridge Logo" class="w-28 h-auto mb-4" />
        <h1 class="text-4xl font-bold">Welcome to BioBridge Medical Center</h1>
        <p class="text-gray-600 max-w-md">
          Your health, our priority. Book your appointment with ease and connect with our medical professionals.
        </p>
        <button onclick="goToPatientNotice()" class="bg-sky-600 hover:bg-sky-700 text-white px-6 py-3 rounded-lg transition">
          Book an Appointment
        </button>
      </div>
    </section>

    <!-- ========== SECTION 2: Patient Type Notice ========== -->
  <!-- ========== SECTION 2: Patient Type Notice ========== -->
<section id="patient-notice" 
         class="absolute top-0 left-0 w-full min-h-screen flex items-center justify-center bg-gradient-to-r from-sky-100 to-white transition-section translate-left">
  <div class="bg-white shadow-xl rounded-2xl p-10 w-full max-w-lg text-center">
    <h1 class="text-3xl font-bold text-sky-700 mb-4">Before we continue...</h1>
    <p class="text-gray-600 mb-6">
      Please tell us if you’re an <span class="font-semibold">existing patient</span> 
      or a <span class="font-semibold">new patient</span> so we can guide you properly.
    </p>

    <div class="flex flex-col sm:flex-row gap-4 justify-center">
  <!-- ✅ Existing patient triggers login form directly -->
  <button onclick="goToAuth('login')" 
          class="bg-sky-600 hover:bg-sky-700 text-white px-6 py-3 rounded-lg transition shadow-md w-full sm:w-auto">
    I’m an Existing Patient
  </button>

  <!-- ✅ New patient goes to combined register + link page -->
  <a href="patient_register_link.php"
     class="bg-gray-100 hover:bg-gray-200 text-gray-800 px-6 py-3 rounded-lg transition shadow-md w-full sm:w-auto">
    I’m a New Patient
  </a>
</div>

    <button onclick="backToLanding()" 
            class="text-sm text-gray-500 hover:underline mt-6 block mx-auto">
      ← Back to Home
    </button>
  </div>
</section>




    <!-- ========== SECTION 3: Login/Register ========== -->
    <section id="auth" class="absolute top-0 left-0 w-full min-h-screen flex transition-section translate-left">

      <!-- Left side (Login/Register) -->
      <div class="w-full md:w-1/3 flex items-center justify-center p-8">
        <div class="w-full max-w-md space-y-6">

          <!-- Logo -->
          <div class="flex justify-center">
            <img src="Assets/BioBridgeMedicalCenter.png" alt="BioBridge Medical Center Logo" class="w-24 h-auto mb-4" />
          </div>

          <div class="shadow-2xl rounded-xl bg-white p-8 w-full max-w-md mx-auto">
            
            <!-- Login Form -->
            <div id="login-form" class="<?= isHidden('login', $activeForm); ?>">
              <form action="login_register.php" method="post" class="space-y-4" autocomplete="off">
                <h2 class="text-2xl font-bold text-center">Sign in to your account</h2>
                <h3 class="text-sm font-bold text-gray-500 text-opacity-70 text-center">Enter your username and password</h3>
                <?= showError($errors['login']); ?>

                <input type="text" name="username" placeholder="Username" required autocomplete="off"
                       class="w-full p-2 bg-gray-100 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-indigo-500" />

                <div class="relative">
                  <input id="login-password" type="password" name="password" placeholder="Password" required autocomplete="new-password"
                         class="w-full p-2 bg-gray-100 border border-gray-300 rounded pr-10 focus:outline-none focus:ring-2 focus:ring-indigo-500" />
                  <button type="button" onclick="togglePassword('login-password', this)"
                          class="absolute inset-y-0 right-0 px-3 flex items-center text-gray-500 focus:outline-none">
                    👁️
                  </button>
                </div>

                <button type="submit" name="login"
                        class="w-full bg-sky-600 hover:bg-sky-700 text-white p-2 rounded transition">
                  Login
                </button>

              <p class="text-center text-sm leading-relaxed">
                  Are you already a patient at 
                    <span class="font-semibold text-sky-700">BioBridge Medical Center</span> 
                  but don’t have an online account yet?<br>
              <a href="#" onclick="showForm('register')" 
                   class="text-sky-600 hover:text-sky-700 hover:underline font-medium">
                   Link your existing patient record here
              </a>
            </p>
              </form>
            </div>

            <!-- Register Form -->
            <div id="register-form" class="<?= isHidden('register', $activeForm); ?>">
              <form action="login_register.php" method="post" class="space-y-4" autocomplete="off">
                <h2 class="text-2xl font-bold text-center">Register</h2>
                <?= showError($errors['register']); ?>

                <input type="text" name="username" placeholder="Username" required autocomplete="off"
                       class="w-full p-2 bg-gray-100 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-indigo-500" />

                <div class="relative">
                  <input id="register-password" type="password" name="password" placeholder="Password" required
                         autocomplete="new-password"
                         class="w-full p-2 bg-gray-100 border border-gray-300 rounded pr-10 focus:outline-none focus:ring-2 focus:ring-indigo-500" />
                  <button type="button" onclick="togglePassword('register-password', this)"
                          class="absolute inset-y-0 right-0 px-3 flex items-center text-gray-500 focus:outline-none">
                    👁️
                  </button>
                </div>

                <button type="submit" name="register"
                        class="w-full bg-sky-600 hover:bg-sky-700 text-white p-2 rounded transition">
                  Register
                </button>

                <p class="text-center text-sm">
                  Already have an account?
                  <a href="#" onclick="showForm('login')" class="text-sky-600 hover:underline">Log In</a>
                </p>
              </form>
            </div>
          </div>

          <button onclick="backToNotice()" class="text-sm text-gray-500 hover:underline block text-center mt-6">
            ← Back
          </button>
        </div>
      </div>

      <!-- Right image -->
      <div class="hidden md:block md:w-2/3">
        <img src="Assets/BioBridge_Medical_Center_Info.png" alt="BioBridge Medical Center" class="w-full h-full object-cover" />
      </div>
    </section>
  </div>

  <!-- ========== SCRIPTS ========== -->
  <script>
    function showForm(formName) {
      document.getElementById('login-form').classList.toggle('hidden', formName !== 'login');
      document.getElementById('register-form').classList.toggle('hidden', formName !== 'register');
    }

    function togglePassword(inputId, btn) {
      const input = document.getElementById(inputId);
      const isPassword = input.type === 'password';
      input.type = isPassword ? 'text' : 'password';
      btn.textContent = isPassword ? '🙈' : '👁️';
    }

    // Show only the target section
    function showSection(showId) {
      const sections = ['landing', 'patient-notice', 'auth'];
      sections.forEach(id => {
        const el = document.getElementById(id);
        if (id === showId) {
          el.classList.remove('translate-left');
          el.classList.add('translate-center');
        } else {
          el.classList.remove('translate-center');
          el.classList.add('translate-left');
        }
      });
    }

    // Navigation actions
    function goToPatientNotice() {
      showSection('patient-notice');
    }

    function goToAuth(formType) {
      showSection('auth');
      showForm(formType);
    }

    function backToLanding() {
      showSection('landing');
    }

    function backToNotice() {
      showSection('patient-notice');
    }
  </script>
</body>
</html>
