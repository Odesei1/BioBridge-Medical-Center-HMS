<?php
session_start();

// Prevent caching
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Expires: 0");

// Restrict access
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'staff') {
    header("Location: access_denied.php");
    exit();
}

require_once __DIR__ . "/../Config/database.php";

$database = new Database();
$conn = $database->connect();

$staff_id = $_SESSION['staff_id'] ?? null;

$staff = null;

// 🧍 Fetch staff details
if ($staff_id) {
    $stmt = $conn->prepare("
        SELECT STAFF_ID, STAFF_FIRST_NAME, STAFF_LAST_NAME, STAFF_EMAIL, STAFF_CONTACT_NUM, STAFF_CREATED_AT, STAFF_UPDATED_AT
        FROM staff
        WHERE STAFF_ID = :id
    ");
    $stmt->execute([':id' => $staff_id]);
    $staff = $stmt->fetch(PDO::FETCH_ASSOC);
}

// ✅ Handle AJAX update request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax_update'])) {
    $first = trim($_POST['staff_first_name']);
    $last = trim($_POST['staff_last_name']);
    $email = trim($_POST['staff_email']);
    $contact = trim($_POST['staff_contact_num']);

    try {
        $stmt = $conn->prepare("
            UPDATE staff SET 
                STAFF_FIRST_NAME = :first,
                STAFF_LAST_NAME = :last,
                STAFF_EMAIL = :email,
                STAFF_CONTACT_NUM = :contact,
                STAFF_UPDATED_AT = NOW()
            WHERE STAFF_ID = :id
        ");
        $stmt->execute([
            ':first' => $first,
            ':last' => $last,
            ':email' => $email,
            ':contact' => $contact,
            ':id' => $staff_id
        ]);

        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}
?>

<?php include "../Includes/header.html"; ?>
<?php include "../Includes/navbar_staff_dashboard.html"; ?>
<?php include "../Includes/staffSidebar.php"; ?>

<main class="flex-grow p-8 max-w-5xl mx-auto">
  <h1 class="text-3xl font-bold text-sky-700 mb-8 text-center">👩‍💼 Staff Profile</h1>

  <?php if ($staff): ?>
    <div class="bg-white shadow-xl rounded-2xl p-8 border border-gray-200">
      <div class="flex flex-col items-center text-center">
        <div class="w-28 h-28 bg-sky-100 text-sky-700 flex items-center justify-center rounded-full text-5xl font-bold mb-4 shadow-inner">
          <?= strtoupper(substr($staff['STAFF_FIRST_NAME'], 0, 1)) . strtoupper(substr($staff['STAFF_LAST_NAME'], 0, 1)) ?>
        </div>
        <h2 class="text-2xl font-bold text-gray-800 mb-1">
          <?= htmlspecialchars($staff['STAFF_FIRST_NAME'] . ' ' . $staff['STAFF_LAST_NAME']) ?>
        </h2>
        <p class="text-gray-600 italic mb-4">BioBridge Medical Staff</p>
      </div>

      <div class="border-t border-gray-300 my-6"></div>

      <div class="grid sm:grid-cols-2 gap-6 text-gray-700">
        <div>
          <p class="font-semibold text-gray-600">📧 Email</p>
          <p><?= htmlspecialchars($staff['STAFF_EMAIL']) ?></p>
        </div>
        <div>
          <p class="font-semibold text-gray-600">📞 Contact Number</p>
          <p><?= htmlspecialchars($staff['STAFF_CONTACT_NUM'] ?? 'N/A') ?></p>
        </div>
        <div>
          <p class="font-semibold text-gray-600">🕒 Joined</p>
          <p><?= date("F j, Y", strtotime($staff['STAFF_CREATED_AT'])) ?></p>
        </div>
      </div>

      <?php if (!empty($staff['STAFF_UPDATED_AT'])): ?>
        <p class="mt-6 text-sm text-gray-500 text-center">
          Last updated on <?= date("F j, Y g:i A", strtotime($staff['STAFF_UPDATED_AT'])) ?>
        </p>
      <?php endif; ?>

      <div class="flex justify-center mt-8">
        <button onclick="openEditModal()" class="bg-sky-700 text-white px-6 py-2 rounded-lg hover:bg-sky-800 transition">
          ✏️ Update Profile
        </button>
      </div>
    </div>

    <div class="mt-10 text-center text-gray-600">
      <p class="italic">“At <span class='text-sky-700 font-semibold'>BioBridge Medical Center</span>, we empower care through dedication and compassion.” 💙</p>
    </div>
  <?php else: ?>
    <div class="bg-white rounded-xl shadow-md p-12 text-center text-gray-600">
      <h3 class="text-xl font-semibold">Staff profile not found.</h3>
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
        <input type="text" name="staff_first_name" value="<?= htmlspecialchars($staff['STAFF_FIRST_NAME']) ?>" required class="w-full border rounded-lg px-4 py-2 focus:ring-2 focus:ring-sky-500">
      </div>

      <div>
        <label class="block font-semibold text-gray-700 mb-1">Last Name</label>
        <input type="text" name="staff_last_name" value="<?= htmlspecialchars($staff['STAFF_LAST_NAME']) ?>" required class="w-full border rounded-lg px-4 py-2 focus:ring-2 focus:ring-sky-500">
      </div>

      <div>
        <label class="block font-semibold text-gray-700 mb-1">Email</label>
        <input type="email" name="staff_email" value="<?= htmlspecialchars($staff['STAFF_EMAIL']) ?>" required class="w-full border rounded-lg px-4 py-2 focus:ring-2 focus:ring-sky-500">
      </div>

      <div>
        <label class="block font-semibold text-gray-700 mb-1">Contact Number</label>
        <input type="text" name="staff_contact_num" value="<?= htmlspecialchars($staff['STAFF_CONTACT_NUM']) ?>" class="w-full border rounded-lg px-4 py-2 focus:ring-2 focus:ring-sky-500">
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
