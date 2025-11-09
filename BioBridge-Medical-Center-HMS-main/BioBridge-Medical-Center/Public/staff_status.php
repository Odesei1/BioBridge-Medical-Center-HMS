<?php
session_start();

// 🚫 Prevent caching
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Expires: 0");

// ✅ Allow staff only
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'staff') {
    header("Location: access_denied.php");
    exit();
}

require_once __DIR__ . "/../Config/database.php";
require_once __DIR__ . "/../Class/status.php";

$database = new Database();
$conn = $database->connect();
$status = new Status($conn);

// Messages
$successMsg = '';
$errorMsg = '';

// ✅ Add new status
if (isset($_POST['add'])) {
    $name = trim($_POST['STAT_NAME']);
    if ($name) {
        if ($status->add($name)) {
            $successMsg = "Status added successfully.";
        } else {
            $errorMsg = "Failed to add status.";
        }
    } else {
        $errorMsg = "Please enter a status name.";
    }
}

// ✅ Update existing status
if (isset($_POST['update'])) {
    $id = $_POST['STAT_ID'];
    $name = trim($_POST['STAT_NAME']);
    if ($name) {
        if ($status->update($id, $name)) {
            $successMsg = "Status updated successfully.";
        } else {
            $errorMsg = "Update failed.";
        }
    } else {
        $errorMsg = "Status name cannot be empty.";
    }
}

// ✅ Fetch all statuses ordered by stat_id
$statuses = $status->all('stat_id');
?>

<?php include "../Includes/header.html"; ?>
<?php include "../Includes/navbar_staff_status.html"; ?>
<?php include "../Includes/staffSidebar.php"; ?>

<main class="flex-grow p-6 max-w-5xl mx-auto">
  <h1 class="text-3xl font-bold text-sky-700 mb-6 text-center">Manage Appointment Status</h1>

  <!-- ✅ Success / Error Messages -->
  <?php if ($successMsg): ?>
    <div class="bg-green-100 text-green-700 px-4 py-2 rounded mb-4 border border-green-400"><?= htmlspecialchars($successMsg) ?></div>
  <?php endif; ?>
  <?php if ($errorMsg): ?>
    <div class="bg-red-100 text-red-700 px-4 py-2 rounded mb-4 border border-red-400"><?= htmlspecialchars($errorMsg) ?></div>
  <?php endif; ?>

  <!-- ➕ Add Status -->
  <div class="bg-white shadow-md rounded-lg p-6 mb-8">
    <h2 class="text-2xl font-semibold text-sky-700 mb-4">➕ Add New Status</h2>
    <form method="POST" class="flex flex-col md:flex-row gap-4 items-center">
      <input type="text" name="STAT_NAME" placeholder="Enter status name (e.g. Pending, Completed)" 
             class="border p-2 rounded w-full md:w-2/3" required>
      <button type="submit" name="add" 
              class="bg-sky-700 hover:bg-sky-800 text-white px-6 py-2 rounded transition">
        Add
      </button>
    </form>
  </div>

  <!-- 📋 Status Table -->
  <div class="bg-white shadow-md rounded-lg p-6 overflow-x-auto">
    <h2 class="text-xl font-semibold text-sky-700 mb-4">📋 Status List</h2>
    <table class="w-full border border-gray-300 text-sm">
      <thead class="bg-sky-700 text-white">
        <tr>
          <th class="p-2 border text-left">ID</th>
          <th class="p-2 border text-left">Status Name</th>
          <th class="p-2 border text-center">Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php if ($statuses): ?>
          <?php foreach ($statuses as $s): ?>
            <tr class="hover:bg-gray-100">
              <td class="p-3 border"><?= htmlspecialchars($s['stat_id']) ?></td>
              <td class="p-3 border"><?= htmlspecialchars($s['stat_name']) ?></td>
              <td class="p-3 border text-center">
                <button 
                  onclick="openEditModal(<?= htmlspecialchars(json_encode($s)) ?>)" 
                  class="bg-amber-500 hover:bg-amber-600 text-white px-3 py-1 rounded">Edit</button>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php else: ?>
          <tr><td colspan="3" class="text-center p-4 text-gray-500">No statuses found.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</main>

<!-- ✏️ Edit Modal -->
<div id="editModal" class="fixed inset-0 bg-black bg-opacity-50 hidden justify-center items-center">
  <div class="bg-white rounded-lg shadow-lg p-6 w-[400px]">
    <h3 class="text-xl font-semibold text-sky-700 mb-4 text-center">Edit Status</h3>
    <form method="POST">
      <input type="hidden" name="STAT_ID" id="edit_STAT_ID">
      <label class="block mb-2 text-gray-700">Status Name:</label>
      <input type="text" name="STAT_NAME" id="edit_STAT_NAME" class="border p-2 rounded w-full mb-4" required>
      <div class="flex justify-end gap-3">
        <button type="button" onclick="closeEditModal()" class="px-4 py-2 bg-gray-300 rounded hover:bg-gray-400">Cancel</button>
        <button type="submit" name="update" class="px-4 py-2 bg-sky-700 text-white rounded hover:bg-sky-800">Save</button>
      </div>
    </form>
  </div>
</div>

<script>
function openEditModal(data) {
  document.getElementById('edit_STAT_ID').value = data.stat_id;
  document.getElementById('edit_STAT_NAME').value = data.stat_name;
  document.getElementById('editModal').classList.remove('hidden');
  document.getElementById('editModal').classList.add('flex');
}

function closeEditModal() {
  document.getElementById('editModal').classList.add('hidden');
}
</script>

<?php include "../Includes/footer.html"; ?>
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