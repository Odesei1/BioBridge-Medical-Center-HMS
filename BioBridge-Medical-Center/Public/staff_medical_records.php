<?php
session_start();
require_once __DIR__ . "/../Config/database.php";
require_once __DIR__ . "/../Class/medical_record.php";

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Expires: 0");

// If not logged in, redirect
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'staff') {
    header("Location: access_denied.php");
    exit();
}

$medical_records = new MedicalRecords();
$records = $medical_records->getAllRecords();
?>

<?php include "../Includes/header.html"; ?>
<?php include "../Includes/navbar_staff_dashboard.html"; ?>
<?php include "../Includes/staffSidebar.php"; ?>

<main class="flex-grow container mx-auto p-6">
  <h1 class="text-3xl font-bold text-sky-700 mb-6 text-center">Medical Records</h1>
  <p class="text-center text-gray-600 mb-10">
    View all patient medical records linked to appointments.
  </p>

  <div class="bg-white shadow-md rounded-2xl p-6 overflow-x-auto">
    <table class="w-full border-collapse border border-gray-300 text-sm">
      <thead class="bg-sky-700 text-white">
        <tr>
          <th class="p-2 border text-center">Record ID</th>
          <th class="p-2 border text-left">Diagnosis</th>
          <th class="p-2 border text-left">Prescription</th>
          <th class="p-2 border text-center">Visit Date</th>
          <th class="p-2 border text-center">Appointment ID</th>
        </tr>
      </thead>
      <tbody>
        <?php if ($records): ?>
          <?php foreach ($records as $rec): ?>
            <tr class="hover:bg-gray-100 transition">
              <td class="p-2 border text-center"><?= htmlspecialchars($rec['med_rec_id']) ?></td>
              <td class="p-2 border"><?= htmlspecialchars($rec['med_rec_diagnosis']) ?></td>
              <td class="p-2 border"><?= htmlspecialchars($rec['med_rec_prescription']) ?></td>
              <td class="p-2 border text-center"><?= htmlspecialchars($rec['med_rec_visit_date']) ?></td>
              <td class="p-2 border text-center"><?= htmlspecialchars($rec['appt_id'] ?? '—') ?></td>
            </tr>
          <?php endforeach; ?>
        <?php else: ?>
          <tr>
            <td colspan="5" class="text-center p-4 text-gray-500">No medical records found.</td>
          </tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</main>

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

