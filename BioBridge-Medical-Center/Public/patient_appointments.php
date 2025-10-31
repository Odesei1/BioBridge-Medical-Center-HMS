<?php
session_start();

// 🚫 Prevent caching
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Expires: 0");

// ✅ Only allow patients
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'patient') {
    header("Location: access_denied.php");
    exit();
}

$pat_id = $_SESSION['pat_id'];

require_once __DIR__ . "/../Config/database.php";
require_once __DIR__ . "/../Class/appointment.php";

$database = new Database();
$conn = $database->connect();
$appointment = new Appointment($conn);

$errorMsg = '';
$successMsg = '';

// ✅ Create Appointment
if (isset($_POST['add'])) {
    try {
        $appt_id = $appointment->create(
            $pat_id,
            $_POST['doc_id'],
            $_POST['serv_id'],
            $_POST['appt_date'],
            $_POST['appt_time']
        );
        $successMsg = "Appointment booked successfully! ID: " . $appt_id;
    } catch (Exception $e) {
        $errorMsg = "Failed to create appointment: " . $e->getMessage();
    }
}

// ✅ Pagination + Search
$limit = 5;
$page = isset($_GET['page']) ? max((int)$_GET['page'], 1) : 1;
$offset = ($page - 1) * $limit;
$search = $_GET['search'] ?? '';

// Count total rows
$countSql = $search
    ? "SELECT COUNT(*) FROM appointment WHERE pat_id = :pat_id AND appt_id LIKE :search"
    : "SELECT COUNT(*) FROM appointment WHERE pat_id = :pat_id";
$countStmt = $conn->prepare($countSql);
$params = [":pat_id" => $pat_id];
if ($search) $params[":search"] = "%$search%";
$countStmt->execute($params);
$totalAppointments = $countStmt->fetchColumn();
$totalPages = ceil($totalAppointments / $limit);

// Fetch appointments (with details)
$sql = "SELECT a.*, d.doc_first_name, d.doc_last_name, sp.spec_name,
               s.serv_name, st.stat_name, st.stat_id,
               p.pymt_id, p.pymt_amount_paid,
               pmeth.pymt_meth_name, pstat.pymt_stat_name
        FROM appointment a
        JOIN doctor d ON a.doc_id = d.doc_id
        LEFT JOIN specialization sp ON d.spec_id = sp.spec_id
        JOIN service s ON a.serv_id = s.serv_id
        JOIN status st ON a.stat_id = st.stat_id
        LEFT JOIN payment p ON a.appt_id = p.appt_id
        LEFT JOIN payment_method pmeth ON p.pymt_meth_id = pmeth.pymt_meth_id
        LEFT JOIN payment_status pstat ON p.pymt_stat_id = pstat.pymt_stat_id
        WHERE a.pat_id = :pat_id";
if ($search) $sql .= " AND a.appt_id LIKE :search";
$sql .= " ORDER BY a.appt_date DESC, a.appt_time DESC LIMIT $limit OFFSET $offset";

$stmt = $conn->prepare($sql);
$stmt->execute($params);
$appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Dropdown data
$doctors = $conn->query("SELECT d.doc_id, d.doc_first_name, d.doc_last_name, sp.spec_name 
                         FROM doctor d 
                         LEFT JOIN specialization sp ON d.spec_id = sp.spec_id 
                         ORDER BY d.doc_last_name ASC")->fetchAll(PDO::FETCH_ASSOC);

$services = $conn->query("SELECT * FROM service ORDER BY serv_name ASC")->fetchAll(PDO::FETCH_ASSOC);
?>

<?php include "../Includes/header.html"; ?>
<?php include "../Includes/navbar_patient_appointments.html"; ?>
<?php include "../Includes/patientSidebar.php"; ?>

<main class="flex-grow container mx-auto p-6">
  <h1 class="text-3xl font-bold text-sky-700 mb-6 text-center">My Appointments</h1>

  <!-- Book Appointment -->
  <div class="bg-white shadow-md rounded-lg p-6 mb-8">
    <a href="patient_findDoctor.php" class="inline-block mb-4 bg-gray-200 text-sky-700 px-4 py-2 rounded hover:bg-gray-300">
      ← Back to Find a Doctor
    </a>
    <h2 class="text-2xl font-semibold mb-4">Book New Appointment</h2>

    <form method="POST" class="grid grid-cols-1 md:grid-cols-2 gap-4">
      <input type="date" name="appt_date" required class="border p-2 rounded">
      <input type="time" name="appt_time" required class="border p-2 rounded">

      <select name="doc_id" required class="border p-2 rounded">
        <option value="">Select Doctor</option>
        <?php foreach ($doctors as $doc): ?>
          <option value="<?= $doc['doc_id'] ?>">
            Dr. <?= htmlspecialchars($doc['doc_first_name'] . " " . $doc['doc_last_name']) ?>
            <?= $doc['spec_name'] ? " - " . htmlspecialchars($doc['spec_name']) : "" ?>
          </option>
        <?php endforeach; ?>
      </select>

      <select name="serv_id" required class="border p-2 rounded">
        <option value="">Select Service</option>
        <?php foreach ($services as $s): ?>
          <option value="<?= $s['serv_id'] ?>"><?= htmlspecialchars($s['serv_name']) ?></option>
        <?php endforeach; ?>
      </select>

      <button type="submit" name="add" class="bg-sky-700 hover:bg-sky-800 text-white py-2 rounded col-span-2">
        Book Appointment
      </button>
    </form>
  </div>

  <!-- Appointments Table -->
  <div class="bg-white shadow-md rounded-lg p-6 overflow-x-auto">
    <div class="flex justify-between items-center mb-4">
      <!-- Pagination -->
      <div class="flex gap-2">
        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
          <a href="?page=<?= $i ?>&search=<?= urlencode($search) ?>"
             class="px-3 py-1 border rounded <?= $i == $page ? 'bg-sky-700 text-white' : 'hover:bg-gray-200' ?>">
            <?= $i ?>
          </a>
        <?php endfor; ?>
      </div>

      <!-- Search -->
      <form method="GET" class="flex items-center gap-2">
        <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Search ID" class="border p-2 rounded">
        <button type="submit" class="bg-sky-700 text-white px-3 py-2 rounded hover:bg-sky-800">Search</button>
        <?php if ($search): ?>
          <a href="patient_appointments.php" class="text-sky-700 hover:underline">Clear</a>
        <?php endif; ?>
      </form>
    </div>

    <table class="w-full border-collapse border border-gray-300 text-sm">
      <thead class="bg-sky-700 text-white">
        <tr>
          <th class="p-2 border text-center">Appointment ID</th>
        </tr>
      </thead>
      <tbody>
        <?php if ($appointments): ?>
          <?php foreach ($appointments as $appt): ?>
            <tr class="hover:bg-gray-100 cursor-pointer" onclick='openViewModal(<?= json_encode($appt) ?>)'>
              <td class="p-3 border text-center text-sky-700 font-semibold hover:underline">
                <?= htmlspecialchars($appt['appt_id']) ?>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php else: ?>
          <tr><td class="text-center p-4 text-gray-500">No appointments found.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</main>

<?php include "../Includes/footer.html"; ?>

<!-- View-Only Modal -->
<div id="viewModal" class="fixed inset-0 bg-black bg-opacity-50 hidden justify-center items-center">
  <div class="bg-white rounded-lg shadow-lg p-6 w-[420px]">
    <h3 class="text-xl font-semibold text-sky-700 mb-4 text-center">Appointment Details</h3>
    <div class="space-y-2 text-sm">
      <p><strong>ID:</strong> <span id="view_appt_id"></span></p>
      <p><strong>Doctor:</strong> <span id="view_doctor"></span></p>
      <p><strong>Service:</strong> <span id="view_service"></span></p>
      <p><strong>Date:</strong> <span id="view_date"></span></p>
      <p><strong>Time:</strong> <span id="view_time"></span></p>
      <p><strong>Status:</strong> <span id="view_status"></span></p>
      <p><strong>Payment Status:</strong> <span id="view_payment_status">---</span></p>
      <p><strong>Payment Method:</strong> <span id="view_payment_method">---</span></p>
    </div>

    <div class="flex justify-center mt-6">
      <button type="button" onclick="closeViewModal()" class="px-5 py-2 bg-sky-700 text-white rounded hover:bg-sky-800">Close</button>
    </div>
  </div>
</div>

<script>
function openViewModal(data) {
  document.getElementById("view_appt_id").textContent = data.appt_id;
  document.getElementById("view_doctor").textContent = "Dr. " + data.doc_first_name + " " + data.doc_last_name + (data.spec_name ? " (" + data.spec_name + ")" : "");
  document.getElementById("view_service").textContent = data.serv_name;
  document.getElementById("view_date").textContent = data.appt_date;
  document.getElementById("view_time").textContent = new Date("1970-01-01T" + data.appt_time).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});
  document.getElementById("view_status").textContent = data.stat_name;
  document.getElementById("view_payment_status").textContent = data.pymt_stat_name ?? "---";
  document.getElementById("view_payment_method").textContent = data.pymt_meth_name ?? "---";
  document.getElementById("viewModal").classList.remove("hidden");
  document.getElementById("viewModal").classList.add("flex");
}
function closeViewModal() { document.getElementById("viewModal").classList.add("hidden"); }
</script>

<script>
const isLoggedIn = <?php echo isset($_SESSION['role']) ? 'true' : 'false'; ?>;
window.history.pushState(null, null, window.location.href);
window.onpopstate = () => { if (!isLoggedIn) window.location.replace("access_denied.php"); };
</script>
</body>
</html>
