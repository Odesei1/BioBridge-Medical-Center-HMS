<?php
session_start();

// Prevent caching so browser doesn’t store private pages
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Expires: 0");

// If not logged in, redirect
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'patient') {
    header("Location: access_denied.php");
    exit();
}
?>

<?php include "../Includes/header.html"; ?>
<?php include "../Includes/navbar_patient_dashboard.html"; ?>
<?php include "../Includes/patientSidebar.php"; ?>

<main class="flex-grow p-6 max-w-6xl mx-auto">
  <!-- Welcome Header -->
  <h1 class="text-3xl font-bold text-sky-700 mb-6 text-center">
    👋 Welcome Patient, <?= htmlspecialchars($_SESSION['username'] ?? 'User') ?>!
  </h1>
  
  <!-- Hero Banner -->
  <div class="bg-gradient-to-r from-sky-700 to-sky-500 text-white rounded-2xl p-8 shadow-lg mb-10 flex flex-col md:flex-row items-center">
    <img src="/Assets/BioBridge_medical_center_logo.png" alt="BioBridge Logo"
         class="w-32 h-32 mb-4 md:mb-0 md:mr-6 rounded-full border-4 border-white shadow-md">
    <div>
      <h2 class="text-2xl font-semibold mb-2">Bridging Care and Innovation</h2>
      <p class="text-white/90 leading-relaxed">
        BioBridge Medical Center is dedicated to delivering compassionate, world-class healthcare
        through innovation, technology, and a team of committed professionals who put patient care first.
      </p>
    </div>
  </div>

  <!-- Mission & Vision Section -->
  <div class="grid md:grid-cols-2 gap-6 mb-10">
    <div class="bg-white shadow-md rounded-xl p-6 border-t-4 border-sky-700">
      <h3 class="text-xl font-semibold text-sky-700 mb-2">🌍 Our Mission</h3>
      <p class="text-gray-700 leading-relaxed">
        To provide accessible and high-quality medical care while ensuring patient safety,
        comfort, and satisfaction through modern technology and professional excellence.
      </p>
    </div>
    <div class="bg-white shadow-md rounded-xl p-6 border-t-4 border-sky-700">
      <h3 class="text-xl font-semibold text-sky-700 mb-2">💡 Our Vision</h3>
      <p class="text-gray-700 leading-relaxed">
        To be the leading healthcare provider in our region—bridging innovation and care to create
        a healthier, more connected community.
      </p>
    </div>
  </div>

  <!-- Optional: Gallery or Quick Info -->
<div class="bg-white shadow-md rounded-2xl p-6 mb-10">
  <h3 class="text-xl font-semibold text-sky-700 mb-4 text-center">Inside BioBridge</h3>
  <div class="flex justify-center overflow-x-auto gap-4 pb-4">
    <img src="/Assets/Facility.png" alt="Facility" class="w-72 h-48 object-cover rounded-lg shadow hover:scale-105 transition-transform duration-300">
    <img src="/Assets/medical_team.png" alt="Medical Team" class="w-72 h-48 object-cover rounded-lg shadow hover:scale-105 transition-transform duration-300">
    <img src="/Assets/patient_care.jpg" alt="Patient Care" class="w-72 h-48 object-cover rounded-lg shadow hover:scale-105 transition-transform duration-300">
  </div>
</div>

<!-- Health Updates -->
 <section class="mt-10 bg-white p-6 rounded-2xl shadow hover:shadow-2xl hover:-translate-y-2 transition-transform transition-shadow duration-300 ease-in-out">
    <h2 class="text-2xl font-semibold text-teal-700 mb-3">Health Updates</h2>
    <p class="text-gray-600 mb-2">Stay informed with the latest BioBridge Medical Center announcements and medical tips.</p>
    <a href="patient_healthUpdates.php" class="text-teal-700 hover:underline">Read More →</a>
  </section>
</main>

  <!-- Footer Quote -->
  <div class="text-center mt-10">
    <blockquote class="italic text-lg text-gray-600">
      “Delivering better healthcare through innovation and empathy.”
    </blockquote>
    <p class="text-sky-700 font-semibold mt-2">— BioBridge Medical Center</p>
  </div>

<?php include "../Includes/footer.html"; ?>

<!-- 🧠 Prevent going back after logout -->
<script>
  const isLoggedIn = <?php echo isset($_SESSION['role']) ? 'true' : 'false'; ?>;

  window.history.pushState(null, null, window.location.href);

  window.onpopstate = function () {
    if (!isLoggedIn) {
      window.location.replace("access_denied.php");
    } else {
      // allow normal navigation
      window.history.back();
    }
  };

  window.addEventListener("pageshow", function (event) {
    if (event.persisted && !isLoggedIn) {
      window.location.replace("access_denied.php");
    }
  });
</script>
</body>
</html>
