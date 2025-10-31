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
$database = new Database();
$conn = $database->connect();

$doctor_id = $_SESSION['doc_id'] ?? null;
$today = date('Y-m-d');

// Determine view tab
$view = $_GET['view'] ?? 'today';
if (!in_array($view, ['today', 'upcoming', 'previous'])) $view = 'today';

// Pagination settings
$perPage = 3;
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;
$offset = ($page - 1) * $perPage;

$appointments = [];
$totalAppointments = 0;

try {
    if ($doctor_id) {
        $baseQuery = "
            FROM appointment a
            JOIN patient p ON a.pat_id = p.pat_id
            JOIN status s ON a.stat_id = s.stat_id
            JOIN service srv ON a.serv_id = srv.serv_id
            WHERE a.doc_id = :doc_id
        ";

        if ($view === 'today') {
            $where = "AND a.appt_date = :today AND s.stat_name NOT IN ('Completed', 'Cancelled', 'No-Show')";
        } elseif ($view === 'upcoming') {
            $where = "AND a.appt_date > :today AND s.stat_name NOT IN ('Completed', 'Cancelled', 'No-Show')";
        } else {
            $where = "AND (a.appt_date < :today OR s.stat_name IN ('Completed', 'Cancelled', 'No-Show'))";
        }

        // Count total
        $countSql = "SELECT COUNT(*) $baseQuery $where";
        $countStmt = $conn->prepare($countSql);
        $countStmt->execute([':doc_id' => $doctor_id, ':today' => $today]);
        $totalAppointments = $countStmt->fetchColumn();

        // Fetch paginated results
        $sql = "SELECT 
                    a.appt_id,
                    a.appt_date,
                    a.appt_time,
                    CONCAT(p.pat_first_name, ' ', p.pat_last_name) AS patient_name,
                    p.pat_email,
                    p.pat_contact_num,
                    s.stat_name,
                    srv.serv_name,
                    srv.serv_price
                $baseQuery $where
                ORDER BY a.appt_date " . ($view === 'previous' ? "DESC" : "ASC") . ", a.appt_time ASC
                LIMIT :limit OFFSET :offset";

        $stmt = $conn->prepare($sql);
        $stmt->bindValue(':doc_id', $doctor_id, PDO::PARAM_INT);
        $stmt->bindValue(':today', $today);
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        $appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (PDOException $e) {
    error_log("Error fetching doctor appointments: " . $e->getMessage());
}

$totalPages = ceil($totalAppointments / $perPage);

function getStatusColor($status) {
    switch (strtolower($status)) {
        case 'scheduled':
        case 'confirmed': return 'bg-blue-100 text-blue-700';
        case 'completed': return 'bg-green-100 text-green-700';
        case 'cancelled': return 'bg-red-100 text-red-700';
        case 'no-show': return 'bg-gray-100 text-gray-700';
        case 'pending': return 'bg-amber-100 text-amber-700';
        case 'in progress': return 'bg-sky-100 text-sky-700';
        default: return 'bg-gray-100 text-gray-700';
    }
}
function formatDate($date) { return date('F j, Y', strtotime($date)); }
function formatTime($time) { return date('g:i A', strtotime($time)); }
?>

<?php include "../Includes/header.html"; ?>
<?php include "../Includes/navbar_doctor_dashboard.html"; ?>
<?php include "../Includes/doctorSidebar.php"; ?>

<main class="flex-grow p-6 max-w-7xl mx-auto">
  <div class="mb-6">
    <h1 class="text-3xl font-bold text-sky-700 mb-2">📅 Appointment Management</h1>
    <p class="text-gray-600">View and manage your appointments</p>
  </div>

  <!-- Tabs -->
  <div class="bg-white rounded-lg shadow-md mb-6 overflow-hidden">
    <div class="flex border-b border-gray-200 relative">
      <div id="tab-indicator" class="absolute bottom-0 h-0.5 bg-sky-700 transition-all duration-300 ease-in-out"
           style="width: 33.33%; left: <?= $view === 'today' ? '0%' : ($view === 'upcoming' ? '33.33%' : '66.66%') ?>;"></div>

      <a href="?view=today" class="flex-1 text-center py-4 px-6 font-semibold <?= $view === 'today' ? 'text-sky-700 bg-sky-50' : 'text-gray-600 hover:text-sky-700 hover:bg-gray-50' ?>">📋 Today's Appointments</a>
      <a href="?view=upcoming" class="flex-1 text-center py-4 px-6 font-semibold <?= $view === 'upcoming' ? 'text-sky-700 bg-sky-50' : 'text-gray-600 hover:text-sky-700 hover:bg-gray-50' ?>">📋 Upcoming Appointments</a>
      <a href="?view=previous" class="flex-1 text-center py-4 px-6 font-semibold <?= $view === 'previous' ? 'text-sky-700 bg-sky-50' : 'text-gray-600 hover:text-sky-700 hover:bg-gray-50' ?>">📋 Previous Appointments</a>
    </div>
  </div>

  <!-- Appointment Count -->
  <div class="bg-sky-50 border-l-4 border-sky-700 p-4 mb-6 rounded">
    <p class="text-sky-700 font-semibold">
      📊 You have <span class="text-2xl"><?= $totalAppointments ?></span> <?= htmlspecialchars($view) ?> appointment(s)
    </p>
  </div>

  <!-- Appointment Cards -->
  <?php if (empty($appointments)): ?>
    <div class="bg-white rounded-xl shadow-md p-12 text-center text-gray-600">
      <h3 class="text-xl font-semibold">No appointments found for this view.</h3>
      <p class="text-gray-500 mt-2">Everything is up to date!</p>
    </div>
  <?php else: ?>
    <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
      <?php foreach ($appointments as $appt): ?>
        <div class="bg-white rounded-xl shadow-md hover:shadow-xl transition-shadow duration-300 overflow-hidden border-l-4 
          <?= $appt['stat_name'] === 'Completed' ? 'border-green-500' : 
              ($appt['stat_name'] === 'Cancelled' || $appt['stat_name'] === 'No-Show' ? 'border-red-500' : 'border-sky-500') ?>">

          <div class="bg-gradient-to-r from-sky-600 to-sky-700 text-white p-4">
            <div class="flex justify-between items-start mb-2">
              <div>
                <p class="text-xs opacity-90">Appointment ID</p>
                <p class="font-mono font-semibold"><?= htmlspecialchars($appt['appt_id']) ?></p>
              </div>
              <span class="px-3 py-1 rounded-full text-xs font-semibold <?= getStatusColor($appt['stat_name']) ?>">
                <?= htmlspecialchars($appt['stat_name']) ?>
              </span>
            </div>
          </div>

          <div class="p-4 space-y-3">
            <div>
              <p class="font-semibold text-gray-800"><?= htmlspecialchars($appt['patient_name']) ?></p>
              <p class="text-xs text-gray-600"><?= htmlspecialchars($appt['pat_email']) ?></p>
              <p class="text-xs text-gray-600"><?= htmlspecialchars($appt['pat_contact_num']) ?></p>
            </div>

            <div class="border-t border-gray-200"></div>

            <div>
              <p class="font-medium text-gray-700"><?= formatDate($appt['appt_date']) ?></p>
              <p class="text-sm text-gray-600"><?= formatTime($appt['appt_time']) ?></p>
            </div>

            <div>
              <p class="font-medium text-gray-700"><?= htmlspecialchars($appt['serv_name']) ?></p>
              <p class="text-sm text-gray-600">₱<?= number_format($appt['serv_price'], 2) ?></p>
            </div>
          </div>

          <div class="bg-gray-50 p-3 flex gap-2">
            <button 
              onclick='showDetails(<?= json_encode($appt, JSON_HEX_APOS | JSON_HEX_QUOT) ?>)'
              class="flex-1 text-center bg-sky-600 hover:bg-sky-700 text-white py-2 px-3 rounded text-sm font-semibold transition">
              View Details
            </button>
          </div>
        </div>
      <?php endforeach; ?>
    </div>

    <!-- Pagination -->
    <?php if ($totalPages > 1): ?>
      <div class="flex justify-center mt-6 space-x-2 items-center">
        <?php if ($page > 1): ?>
          <a href="?view=<?= $view ?>&page=<?= $page - 1 ?>" class="px-3 py-1 rounded border bg-white text-sky-700 hover:bg-sky-100">&laquo; Prev</a>
        <?php endif; ?>

        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
          <a href="?view=<?= $view ?>&page=<?= $i ?>"
             class="px-3 py-1 rounded border <?= $i == $page ? 'bg-sky-700 text-white' : 'bg-white text-sky-700 hover:bg-sky-100' ?>">
            <?= $i ?>
          </a>
        <?php endfor; ?>

        <?php if ($page < $totalPages): ?>
          <a href="?view=<?= $view ?>&page=<?= $page + 1 ?>" class="px-3 py-1 rounded border bg-white text-sky-700 hover:bg-sky-100">Next &raquo;</a>
        <?php endif; ?>
      </div>
    <?php endif; ?>
  <?php endif; ?>
</main>

<!-- Modal -->
<div id="appointmentModal" class="fixed inset-0 bg-black/50 backdrop-blur-sm hidden items-center justify-center z-50 transition-opacity duration-300">
  <div id="modalBox" class="relative bg-white rounded-2xl shadow-2xl w-full max-w-md p-6 transform scale-95 opacity-0 transition-all duration-300">
    <button onclick="closeModal()" class="absolute top-3 right-3 text-gray-500 hover:text-gray-700 text-lg">✕</button>
    <h2 class="text-2xl font-semibold text-sky-700 mb-4">Appointment Details</h2>
    <div id="modalContent" class="text-gray-700 space-y-2"></div>
    <div class="mt-6 flex justify-between">
      <button onclick="seeMedicalRecords()" class="bg-amber-500 hover:bg-amber-600 text-white py-2 px-4 rounded-lg text-sm font-semibold">🩺 See Medical Records</button>
      <button onclick="closeModal()" class="bg-gray-200 hover:bg-gray-300 text-gray-800 py-2 px-4 rounded-lg text-sm font-semibold">Close</button>
    </div>
  </div>
</div>

<?php include "../Includes/footer.html"; ?>

<script>
function showDetails(appt) {
  const content = `
    <p><strong>Appointment ID:</strong> ${appt.appt_id}</p>
    <p><strong>Patient Name:</strong> ${appt.patient_name}</p>
    <p><strong>Email:</strong> ${appt.pat_email}</p>
    <p><strong>Contact:</strong> ${appt.pat_contact_num}</p>
    <p><strong>Service:</strong> ${appt.serv_name} - ₱${Number(appt.serv_price).toLocaleString()}</p>
    <p><strong>Date:</strong> ${appt.appt_date}</p>
    <p><strong>Time:</strong> ${appt.appt_time}</p>
    <p><strong>Status:</strong> ${appt.stat_name}</p>
  `;
  document.getElementById('modalContent').innerHTML = content;
  const modal = document.getElementById('appointmentModal');
  const box = document.getElementById('modalBox');
  modal.classList.remove('hidden');
  modal.classList.add('flex');
  setTimeout(() => box.classList.remove('opacity-0', 'scale-95'), 50);
  window.currentAppointment = appt;
}

function closeModal() {
  const modal = document.getElementById('appointmentModal');
  const box = document.getElementById('modalBox');
  box.classList.add('opacity-0', 'scale-95');
  setTimeout(() => { modal.classList.add('hidden'); modal.classList.remove('flex'); }, 200);
}

function seeMedicalRecords() {
  if (!window.currentAppointment) return;
  const apptId = window.currentAppointment.appt_id;
  window.location.href = 'doctor_medical_records.php?appt_id=' + encodeURIComponent(apptId);
}
</script>
</body>
</html>
