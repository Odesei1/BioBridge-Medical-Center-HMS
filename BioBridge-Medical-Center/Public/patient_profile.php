<?php
session_start();

// Prevent caching
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Expires: 0");

// Restrict access to patients only
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'patient') {
    header("Location: access_denied.php");
    exit();
}

require_once __DIR__ . "/../Config/database.php";

$database = new Database();
$conn = $database->connect();

$patient_id = $_SESSION['pat_id'] ?? null;

$patient = null;

// 🩺 Fetch patient details
if ($patient_id) {
    $stmt = $conn->prepare("
        SELECT pat_id, pat_first_name, pat_last_name, pat_gender, pat_dob, pat_email, pat_contact_num, pat_address, pat_created_at, pat_updated_at
        FROM patient
        WHERE pat_id = :id
    ");
    $stmt->execute([':id' => $patient_id]);
    $patient = $stmt->fetch(PDO::FETCH_ASSOC);
}

// ✅ Handle AJAX update request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax_update'])) {
    $first = trim($_POST['pat_first_name']);
    $last = trim($_POST['pat_last_name']);
    $gender = trim($_POST['pat_gender']);
    $dob = trim($_POST['pat_dob']);
    $email = trim($_POST['pat_email']);
    $contact = trim($_POST['pat_contact_num']);
    $address = trim($_POST['pat_address']);

    try {
        $stmt = $conn->prepare("
            UPDATE patient SET 
                pat_first_name = :first,
                pat_last_name = :last,
                pat_gender = :gender,
                pat_dob = :dob,
                pat_email = :email,
                pat_contact_num = :contact,
                pat_address = :address,
                pat_updated_at = NOW()
            WHERE pat_id = :id
        ");
        $stmt->execute([
            ':first' => $first,
            ':last' => $last,
            ':gender' => $gender,
            ':dob' => $dob,
            ':email' => $email,
            ':contact' => $contact,
            ':address' => $address,
            ':id' => $patient_id
        ]);

        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}
?>

<?php include "../Includes/header.html"; ?>
<?php include "../Includes/navbar_patient_dashboard.html"; ?>
<?php include "../Includes/patientSidebar.php"; ?>

<main class="flex-grow p-8 max-w-5xl mx-auto">
  <h1 class="text-3xl font-bold text-sky-700 mb-8 text-center">🧍‍♂️ Patient Profile</h1>

  <?php if ($patient): ?>
    <div class="bg-white shadow-xl rounded-2xl p-8 border border-gray-200">
      <div class="flex flex-col items-center text-center">
        <div class="w-28 h-28 bg-sky-100 text-sky-700 flex items-center justify-center rounded-full text-5xl font-bold mb-4 shadow-inner">
          <?= strtoupper(substr($patient['pat_first_name'], 0, 1)) . strtoupper(substr($patient['pat_last_name'], 0, 1)) ?>
        </div>
        <h2 class="text-2xl font-bold text-gray-800 mb-1">
          <?= htmlspecialchars($patient['pat_first_name'] . ' ' . $patient['pat_last_name']) ?>
        </h2>
        <p class="text-gray-600 italic mb-4">Registered Patient</p>
      </div>

      <div class="border-t border-gray-300 my-6"></div>

      <div class="grid sm:grid-cols-2 gap-6 text-gray-700">
        <div>
          <p class="font-semibold text-gray-600">📧 Email</p>
          <p><?= htmlspecialchars($patient['pat_email']) ?></p>
        </div>
        <div>
          <p class="font-semibold text-gray-600">📞 Contact Number</p>
          <p><?= htmlspecialchars($patient['pat_contact_num'] ?? 'N/A') ?></p>
        </div>
        <div>
          <p class="font-semibold text-gray-600">🚻 Gender</p>
          <p><?= htmlspecialchars($patient['pat_gender'] ?? 'N/A') ?></p>
        </div>
        <div>
          <p class="font-semibold text-gray-600">🎂 Date of Birth</p>
          <p><?= htmlspecialchars($patient['pat_dob'] ?? 'N/A') ?></p>
        </div>
        <div class="sm:col-span-2">
          <p class="font-semibold text-gray-600">🏠 Address</p>
          <p><?= htmlspecialchars($patient['pat_address'] ?? 'N/A') ?></p>
        </div>
        <div>
          <p class="font-semibold text-gray-600">🕒 Registered</p>
          <p><?= date("F j, Y", strtotime($patient['pat_created_at'])) ?></p>
        </div>
      </div>

      <?php if (!empty($patient['pat_updated_at'])): ?>
        <p class="mt-6 text-sm text-gray-500 text-center">
          Last updated on <?= date("F j, Y g:i A", strtotime($patient['pat_updated_at'])) ?>
        </p>
      <?php endif; ?>

      <div class="flex justify-center mt-8">
        <button onclick="openEditModal()" class="bg-sky-700 text-white px-6 py-2 rounded-lg hover:bg-sky-800 transition">
          ✏️ Update Profile
        </button>
      </div>
    </div>

    <div class="mt-10 text-center text-gray-600">
      <p class="italic">“At <span class='text-sky-700 font-semibold'>BioBridge Medical Center</span>, we care for your health, because every heartbeat matters.” 💙</p>
    </div>
  <?php else: ?>
    <div class="bg-white rounded-xl shadow-md p-12 text-center text-gray-600">
      <h3 class="text-xl font-semibold">Patient profile not found.</h3>
    </div>
  <?php endif; ?>
</main>

<!-- 🧩 Modal for Editing -->
<div id="editModal" class="fixed inset-0 bg-black/40 hidden items-center justify-center z-50">
  <div class="bg-white rounded-2xl shadow-xl p-8 w-full max-w-lg transform scale-95 opacity-0 transition-all duration-300" id="editBox">
    <h2 class="text-2xl font-bold text-sky-700 mb-4 text-center">✏️ Edit Profile</h2>

    <form id="editForm" class="space-y-4">
      <input type="hidden" name="ajax_update" value="1">

      <div>
        <label class="block font-semibold text-gray-700 mb-1">First Name</label>
        <input type="text" name="pat_first_name" value="<?= htmlspecialchars($patient['pat_first_name']) ?>" required class="w-full border rounded-lg px-4 py-2 focus:ring-2 focus:ring-sky-500">
      </div>

      <div>
        <label class="block font-semibold text-gray-700 mb-1">Last Name</label>
        <input type="text" name="pat_last_name" value="<?= htmlspecialchars($patient['pat_last_name']) ?>" required class="w-full border rounded-lg px-4 py-2 focus:ring-2 focus:ring-sky-500">
      </div>

      <div class="grid grid-cols-2 gap-4">
        <div>
          <label class="block font-semibold text-gray-700 mb-1">Gender</label>
          <select name="pat_gender" class="w-full border rounded-lg px-4 py-2 focus:ring-2 focus:ring-sky-500">
            <option value="Male" <?= $patient['pat_gender'] === 'Male' ? 'selected' : '' ?>>Male</option>
            <option value="Female" <?= $patient['pat_gender'] === 'Female' ? 'selected' : '' ?>>Female</option>
            <option value="Other" <?= $patient['pat_gender'] === 'Other' ? 'selected' : '' ?>>Other</option>
          </select>
        </div>
        <div>
          <label class="block font-semibold text-gray-700 mb-1">Date of Birth</label>
          <input type="date" name="pat_dob" value="<?= htmlspecialchars($patient['pat_dob']) ?>" class="w-full border rounded-lg px-4 py-2 focus:ring-2 focus:ring-sky-500">
        </div>
      </div>

      <div>
        <label class="block font-semibold text-gray-700 mb-1">Email</label>
        <input type="email" name="pat_email" value="<?= htmlspecialchars($patient['pat_email']) ?>" required class="w-full border rounded-lg px-4 py-2 focus:ring-2 focus:ring-sky-500">
      </div>

      <div>
        <label class="block font-semibold text-gray-700 mb-1">Contact Number</label>
        <input type="text" name="pat_contact_num" value="<?= htmlspecialchars($patient['pat_contact_num']) ?>" class="w-full border rounded-lg px-4 py-2 focus:ring-2 focus:ring-sky-500">
      </div>

      <div>
        <label class="block font-semibold text-gray-700 mb-1">Address</label>
        <textarea name="pat_address" rows="2" class="w-full border rounded-lg px-4 py-2 focus:ring-2 focus:ring-sky-500"><?= htmlspecialchars($patient['pat_address']) ?></textarea>
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
    alert('Profile updated successfully!');
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
