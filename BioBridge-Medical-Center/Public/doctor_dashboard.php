<?php
session_start();

// Prevent caching
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Expires: 0");

// Allow only doctors
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'doctor') {
    header("Location: access_denied.php");
    exit();
}

require_once __DIR__ . "/../Config/database.php";
require_once __DIR__ . "/../Class/appointment.php";

$database = new Database();
$conn = $database->connect();
$appointment = new Appointment($conn);

// Doctor ID
$doctor_id = $_SESSION['doc_id'] ?? null;
$today = date('Y-m-d');

$countToday = 0;
$countUpcoming = 0;
$countCompleted = 0;

if ($doctor_id) {
    // ✅ Today's Appointments (only actionable)
    $sql = "SELECT COUNT(*) 
            FROM appointment a
            JOIN status s ON a.stat_id = s.stat_id
            WHERE a.doc_id = :doc_id 
              AND a.appt_date = :today
              AND s.stat_name NOT IN ('Completed','Cancelled','No-Show')";
    $stmt = $conn->prepare($sql);
    $stmt->execute([':doc_id' => $doctor_id, ':today' => $today]);
    $countToday = $stmt->fetchColumn();

    // ✅ Upcoming Appointments (only actionable)
    $sql = "SELECT COUNT(*) 
            FROM appointment a
            JOIN status s ON a.stat_id = s.stat_id
            WHERE a.doc_id = :doc_id 
              AND a.appt_date > :today
              AND s.stat_name NOT IN ('Completed','Cancelled','No-Show')";
    $stmt = $conn->prepare($sql);
    $stmt->execute([':doc_id' => $doctor_id, ':today' => $today]);
    $countUpcoming = $stmt->fetchColumn();

    // ✅ Completed Appointments
    $sql = "SELECT COUNT(*) 
            FROM appointment a
            JOIN status s ON a.stat_id = s.stat_id
            WHERE a.doc_id = :doc_id 
              AND s.stat_name = 'Completed'";
    $stmt = $conn->prepare($sql);
    $stmt->execute([':doc_id' => $doctor_id]);
    $countCompleted = $stmt->fetchColumn();
}
?>

<?php include "../Includes/header.html"; ?>
<?php include "../Includes/navbar_doctor_dashboard.html"; ?>
<?php include "../Includes/doctorSidebar.php"; ?>

<main class="flex-grow p-6 max-w-6xl mx-auto">
  <!-- Welcome Header -->
  <h1 class="text-3xl font-bold text-sky-700 mb-6 text-center">
    👋 Welcome, Dr. <?= htmlspecialchars($_SESSION['username'] ?? 'User') ?>!
  </h1>

  <!-- Stats Overview -->
  <div class="grid grid-cols-1 sm:grid-cols-3 gap-6 mb-10">
    <!-- Today's Appointments -->
    <div class="bg-white p-6 rounded-xl shadow hover:shadow-lg text-center border-t-4 border-sky-700">
      <h3 class="text-lg font-semibold text-gray-600">Today's Appointments</h3>
      <p class="text-4xl font-bold text-sky-700 mt-2"><?= $countToday ?></p>
    </div>

    <!-- Completed Appointments -->
    <div class="bg-white p-6 rounded-xl shadow hover:shadow-lg text-center border-t-4 border-green-600">
      <h3 class="text-lg font-semibold text-gray-600">Completed Appointments</h3>
      <p class="text-4xl font-bold text-green-700 mt-2"><?= $countCompleted ?></p>
    </div>

    <!-- Upcoming Appointments -->
    <div class="bg-white p-6 rounded-xl shadow hover:shadow-lg text-center border-t-4 border-amber-500">
      <h3 class="text-lg font-semibold text-gray-600">Upcoming Appointments</h3>
      <p class="text-4xl font-bold text-amber-600 mt-2"><?= $countUpcoming ?></p>
    </div>
  </div>

  <!-- BioBridge Banner -->
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

  <!-- Inside BioBridge Images -->
  <div class="bg-white shadow-md rounded-2xl p-6 mb-10">
    <h3 class="text-xl font-semibold text-sky-700 mb-4 text-center">Inside BioBridge</h3>
    <div class="flex justify-center overflow-x-auto gap-4 pb-4">
      <img src="/Assets/Facility.png" alt="Facility" class="w-72 h-48 object-cover rounded-lg shadow hover:scale-105 transition-transform duration-300">
      <img src="/Assets/medical_team.png" alt="Medical Team" class="w-72 h-48 object-cover rounded-lg shadow hover:scale-105 transition-transform duration-300">
      <img src="/Assets/patient_care.jpg" alt="Patient Care" class="w-72 h-48 object-cover rounded-lg shadow hover:scale-105 transition-transform duration-300">
    </div>
  </div>

  <!-- Health Updates -->
  <section class="mt-10 bg-white p-6 rounded-2xl shadow hover:shadow-2xl transition">
    <h2 class="text-2xl font-semibold text-sky-700 mb-3">Health Updates</h2>
    <p class="text-gray-600 mb-2">Stay informed with the latest BioBridge Medical Center news and doctor updates.</p>
    <a href="doctor_healthUpdates.php" class="text-sky-700 hover:underline">Read More →</a>
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

<script>
  const isLoggedIn = <?php echo isset($_SESSION['role']) ? 'true' : 'false'; ?>;

  window.history.pushState(null, null, window.location.href);

  window.onpopstate = function () {
    if (!isLoggedIn) {
      window.location.replace("access_denied.php");
    } else {
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
