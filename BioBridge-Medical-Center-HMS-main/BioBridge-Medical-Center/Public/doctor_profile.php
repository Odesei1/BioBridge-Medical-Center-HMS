<?php
session_start();

// Prevent caching
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Expires: 0");

// Restrict access
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'doctor') {
    header("Location: access_denied.php");
    exit();
}

require_once __DIR__ . "/../Config/database.php";
require_once __DIR__ . "/../Class/specialization.php";

$database = new Database();
$conn = $database->connect();

$doctor_id = $_SESSION['doc_id'] ?? null;

$doctor = null;
$schedule = null;

// 🩺 Fetch doctor details with specialization name
if ($doctor_id) {
    $stmt = $conn->prepare("
        SELECT d.DOC_ID, d.DOC_FIRST_NAME, d.DOC_LAST_NAME, d.DOC_EMAIL, d.DOC_CONTACT_NUM, 
               d.DOC_CREATED_AT, d.DOC_UPDATED_AT, s.SPEC_NAME
        FROM doctor d
        LEFT JOIN specialization s ON d.SPEC_ID = s.SPEC_ID
        WHERE d.DOC_ID = :id
    ");
    $stmt->execute([':id' => $doctor_id]);
    $doctor = $stmt->fetch(PDO::FETCH_ASSOC);

    // Fetch doctor schedule (only one per doctor)
    $stmtSched = $conn->prepare("
        SELECT SCHED_ID, SCHED_DAYS, SCHED_START_TIME, SCHED_END_TIME 
        FROM schedule 
        WHERE DOC_ID = :id LIMIT 1
    ");
    $stmtSched->execute([':id' => $doctor_id]);
    $schedule = $stmtSched->fetch(PDO::FETCH_ASSOC);
}

// ✅ Handle AJAX update request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax_update'])) {
    $first = trim($_POST['doc_first_name']);
    $last = trim($_POST['doc_last_name']);
    $email = trim($_POST['doc_email']);
    $contact = trim($_POST['doc_contact_num']);
    $days = trim($_POST['sched_days']);
    $start = trim($_POST['sched_start']);
    $end = trim($_POST['sched_end']);

    try {
        // Update doctor info
        $stmt = $conn->prepare("
            UPDATE doctor SET 
                DOC_FIRST_NAME = :first,
                DOC_LAST_NAME = :last,
                DOC_EMAIL = :email,
                DOC_CONTACT_NUM = :contact,
                DOC_UPDATED_AT = NOW()
            WHERE DOC_ID = :id
        ");
        $stmt->execute([
            ':first' => $first,
            ':last' => $last,
            ':email' => $email,
            ':contact' => $contact,
            ':id' => $doctor_id
        ]);

        // Update or insert schedule
        $stmtCheck = $conn->prepare("SELECT SCHED_ID FROM schedule WHERE DOC_ID = :id LIMIT 1");
        $stmtCheck->execute([':id' => $doctor_id]);
        $existing = $stmtCheck->fetch(PDO::FETCH_ASSOC);

        if ($existing) {
            $stmtUpd = $conn->prepare("
                UPDATE schedule 
                SET SCHED_DAYS = :days, 
                    SCHED_START_TIME = :start, 
                    SCHED_END_TIME = :end, 
                    SCHED_UPDATED_AT = NOW()
                WHERE DOC_ID = :id
            ");
            $stmtUpd->execute([':days' => $days, ':start' => $start, ':end' => $end, ':id' => $doctor_id]);
        } else {
            $stmtIns = $conn->prepare("
                INSERT INTO schedule (DOC_ID, SCHED_DAYS, SCHED_START_TIME, SCHED_END_TIME, SCHED_CREATED_AT)
                VALUES (:id, :days, :start, :end, NOW())
            ");
            $stmtIns->execute([':id' => $doctor_id, ':days' => $days, ':start' => $start, ':end' => $end]);
        }

        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}
?>

<?php include "../Includes/header.html"; ?>
<?php include "../Includes/navbar_doctor_dashboard.html"; ?>
<?php include "../Includes/doctorSidebar.php"; ?>

<main class="flex-grow p-8 max-w-5xl mx-auto">
  <h1 class="text-3xl font-bold text-sky-700 mb-8 text-center">👨‍⚕️ Doctor Profile</h1>

  <?php if ($doctor): ?>
    <div class="bg-white shadow-xl rounded-2xl p-8 border border-gray-200">
      <div class="flex flex-col items-center text-center">
        <div class="w-28 h-28 bg-sky-100 text-sky-700 flex items-center justify-center rounded-full text-5xl font-bold mb-4 shadow-inner">
          <?= strtoupper(substr($doctor['DOC_FIRST_NAME'], 0, 1)) . strtoupper(substr($doctor['DOC_LAST_NAME'], 0, 1)) ?>
        </div>
        <h2 class="text-2xl font-bold text-gray-800 mb-1">
          Dr. <?= htmlspecialchars($doctor['DOC_FIRST_NAME'] . ' ' . $doctor['DOC_LAST_NAME']) ?>
        </h2>
        <p class="text-gray-600 italic mb-4"><?= htmlspecialchars($doctor['SPEC_NAME'] ?? 'No specialization assigned') ?></p>
      </div>

      <div class="border-t border-gray-300 my-6"></div>

      <div class="grid sm:grid-cols-2 gap-6 text-gray-700">
        <div>
          <p class="font-semibold text-gray-600">📧 Email</p>
          <p><?= htmlspecialchars($doctor['DOC_EMAIL']) ?></p>
        </div>
        <div>
          <p class="font-semibold text-gray-600">📞 Contact Number</p>
          <p><?= htmlspecialchars($doctor['DOC_CONTACT_NUM'] ?? 'N/A') ?></p>
        </div>
        <div>
          <p class="font-semibold text-gray-600">🩺 Specialization</p>
          <p><?= htmlspecialchars($doctor['SPEC_NAME'] ?? 'N/A') ?></p>
        </div>
        <div>
          <p class="font-semibold text-gray-600">🕒 Joined</p>
          <p><?= date("F j, Y", strtotime($doctor['DOC_CREATED_AT'])) ?></p>
        </div>
      </div>

      <div class="mt-10">
        <h3 class="text-xl font-semibold text-sky-700 mb-4">📅 Schedule</h3>
        <?php if ($schedule): ?>
          <table class="min-w-full border border-gray-300 text-sm">
            <thead class="bg-sky-700 text-white">
              <tr>
                <th class="p-3 border text-left">Days</th>
                <th class="p-3 border text-left">Start Time</th>
                <th class="p-3 border text-left">End Time</th>
              </tr>
            </thead>
            <tbody>
              <tr>
                <td class="p-3 border"><?= htmlspecialchars($schedule['SCHED_DAYS']) ?></td>
                <td class="p-3 border"><?= date("g:i A", strtotime($schedule['SCHED_START_TIME'])) ?></td>
                <td class="p-3 border"><?= date("g:i A", strtotime($schedule['SCHED_END_TIME'])) ?></td>
              </tr>
            </tbody>
          </table>
        <?php else: ?>
          <p class="text-gray-600 italic">No schedule set yet.</p>
        <?php endif; ?>
      </div>

      <div class="flex justify-center mt-8">
        <button onclick="openEditModal()" class="bg-sky-700 text-white px-6 py-2 rounded-lg hover:bg-sky-800 transition">
          ✏️ Update Profile & Schedule
        </button>
      </div>
    </div>

    <div class="mt-10 text-center text-gray-600">
      <p class="italic">“At <span class='text-sky-700 font-semibold'>BioBridge Medical Center</span>, we connect care, compassion, and innovation — one patient at a time.” 💙</p>
    </div>
  <?php endif; ?>
</main>

<!-- 🧩 Modal for Editing -->
<div id="editModal" class="fixed inset-0 bg-black/40 hidden items-center justify-center z-50">
  <div class="bg-white rounded-2xl shadow-xl p-8 w-full max-w-lg transform scale-95 opacity-0 transition-all duration-300" id="editBox">
    <h2 class="text-2xl font-bold text-sky-700 mb-4 text-center">✏️ Edit Profile & Schedule</h2>

    <form id="editForm" class="space-y-4">
      <input type="hidden" name="ajax_update" value="1">

      <div>
        <label class="block font-semibold text-gray-700 mb-1">First Name</label>
        <input type="text" name="doc_first_name" value="<?= htmlspecialchars($doctor['DOC_FIRST_NAME']) ?>" required class="w-full border rounded-lg px-4 py-2 focus:ring-2 focus:ring-sky-500">
      </div>

      <div>
        <label class="block font-semibold text-gray-700 mb-1">Last Name</label>
        <input type="text" name="doc_last_name" value="<?= htmlspecialchars($doctor['DOC_LAST_NAME']) ?>" required class="w-full border rounded-lg px-4 py-2 focus:ring-2 focus:ring-sky-500">
      </div>

      <div>
        <label class="block font-semibold text-gray-700 mb-1">Email</label>
        <input type="email" name="doc_email" value="<?= htmlspecialchars($doctor['DOC_EMAIL']) ?>" required class="w-full border rounded-lg px-4 py-2 focus:ring-2 focus:ring-sky-500">
      </div>

      <div>
        <label class="block font-semibold text-gray-700 mb-1">Contact Number</label>
        <input type="text" name="doc_contact_num" value="<?= htmlspecialchars($doctor['DOC_CONTACT_NUM']) ?>" class="w-full border rounded-lg px-4 py-2 focus:ring-2 focus:ring-sky-500">
      </div>

      <div class="border-t border-gray-300 my-4"></div>

      <div>
        <label class="block font-semibold text-gray-700 mb-1">Schedule Days</label>
        <input type="text" name="sched_days" value="<?= htmlspecialchars($schedule['SCHED_DAYS'] ?? '') ?>" placeholder="e.g. Monday - Friday" class="w-full border rounded-lg px-4 py-2 focus:ring-2 focus:ring-sky-500">
      </div>

      <div class="grid grid-cols-2 gap-4">
        <div>
          <label class="block font-semibold text-gray-700 mb-1">Start Time</label>
          <input type="time" name="sched_start" value="<?= htmlspecialchars($schedule['SCHED_START_TIME'] ?? '') ?>" class="w-full border rounded-lg px-4 py-2 focus:ring-2 focus:ring-sky-500">
        </div>
        <div>
          <label class="block font-semibold text-gray-700 mb-1">End Time</label>
          <input type="time" name="sched_end" value="<?= htmlspecialchars($schedule['SCHED_END_TIME'] ?? '') ?>" class="w-full border rounded-lg px-4 py-2 focus:ring-2 focus:ring-sky-500">
        </div>
      </div>

      <div class="flex justify-end gap-4 mt-6">
        <button type="button" onclick="closeEditModal()" class="bg-gray-300 text-gray-800 px-5 py-2 rounded-lg hover:bg-gray-400">Cancel</button>
        <button type="submit" class="bg-sky-700 text-white px-5 py-2 rounded-lg hover:bg-sky-800">Save</button>
      </div>
    </form>
  </div>
</div>

<?php include "../Includes/footer.html"; ?>

<script>
function openEditModal() {
  const modal = document.getElementById('editModal');
  const box = document.getElementById('editBox');
  modal.classList.remove('hidden');
  modal.classList.add('flex');
  setTimeout(() => box.classList.remove('opacity-0', 'scale-95'), 50);
}
function closeEditModal() {
  const modal = document.getElementById('editModal');
  const box = document.getElementById('editBox');
  box.classList.add('opacity-0', 'scale-95');
  setTimeout(() => { modal.classList.add('hidden'); modal.classList.remove('flex'); }, 200);
}

document.getElementById('editForm').addEventListener('submit', async (e) => {
  e.preventDefault();
  const formData = new FormData(e.target);
  const res = await fetch('', { method: 'POST', body: formData });
  const data = await res.json();
  if (data.success) {
    alert('Profile & Schedule updated successfully!');
    location.reload();
  } else {
    alert('Error: ' + (data.message || 'Unknown error'));
  }
});

const isLoggedIn = <?php echo isset($_SESSION['role']) ? 'true' : 'false'; ?>;
window.history.pushState(null, null, window.location.href);
window.onpopstate = () => { if (!isLoggedIn) window.location.replace("access_denied.php"); };
</script>
</body>
</html>
