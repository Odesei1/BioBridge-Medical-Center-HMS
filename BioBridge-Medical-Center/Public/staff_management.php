<?php
session_start();

// 🚫 Disable caching so the browser never stores this page
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Expires: 0");

// If not logged in, redirect
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'staff') {
    header("Location: access_denied.php");
    exit();
}

require_once __DIR__ . "/../Config/database.php";
require_once __DIR__ . "/../Class/staff.php";

$database = new Database();
$conn = $database->connect();
$staff = new Staff($conn);

$errorMsg = '';
$successMsg = '';

// Handle Add
if (isset($_POST['add'])) {
    try {
        $result = $staff->add(
            $_POST['fname'],
            $_POST['lname'],
            $_POST['midInit'],
            $_POST['contact'],
            $_POST['email']
        );
        $successMsg = $result ? "✅ Staff member added successfully!" : "❌ Failed to add staff member.";
    } catch (Exception $e) {
        $errorMsg = "Error: " . $e->getMessage();
    }
}

// Handle Update
if (isset($_POST['update'])) {
    try {
        $result = $staff->update(
            $_POST['staff_id'],
            $_POST['fname'],
            $_POST['lname'],
            $_POST['midInit'],
            $_POST['contact'],
            $_POST['email']
        );
        $successMsg = $result ? "✅ Staff details updated successfully!" : "❌ Failed to update staff details.";
    } catch (Exception $e) {
        $errorMsg = "Error: " . $e->getMessage();
    }
}

// Handle Search + Pagination
$keyword = $_GET['search'] ?? "";
$perPage = 10;
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$start = ($page - 1) * $perPage;

// Fetch staff (ordered oldest → newest)
$allStaff = !empty($keyword) ? $staff->searchByName($keyword) : $staff->all();
usort($allStaff, fn($a, $b) => strtotime($a['staff_created_at']) <=> strtotime($b['staff_created_at']));
$totalStaff = count($allStaff);
$staffList = array_slice($allStaff, $start, $perPage);
$totalPages = ceil($totalStaff / $perPage);
?>

<?php include "../Includes/header.html"; ?>
<?php include "../Includes/navbar_staff_management.html"; ?>
<?php include "../Includes/staffSidebar.php"; ?>

<main class="flex-grow container mx-auto p-6">
  <h1 class="text-3xl font-bold text-sky-700 mb-6 text-center">Staff Management</h1>

<!-- Search Bar -->
<form method="GET" class="flex justify-center mb-6 gap-2">
  <input
    type="text"
    name="search"
    value="<?= htmlspecialchars($keyword) ?>"
    placeholder="Search by first or last name"
    class="border border-gray-300 p-2 rounded-l w-1/3 focus:ring-2 focus:ring-sky-600"
  />
  <button
    type="submit"
    class="bg-sky-700 hover:bg-sky-800 text-white px-4 rounded-r"
  >
    Search
  </button>

  <!-- 🔄 Refresh Button -->
  <button
    type="button"
    onclick="window.location.href='staff_management.php'"
    class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded shadow transition"
    title="Refresh Page"
  >
    🔄 Refresh
  </button>
</form>


  <!-- Add Staff Form -->
  <div class="bg-white shadow-md rounded-lg p-6 mb-8">
    <h2 class="text-2xl font-semibold mb-4">Add New Staff</h2>
    <form method="POST" class="grid grid-cols-1 md:grid-cols-2 gap-4">
      <input type="text" name="fname" placeholder="First Name" required class="border p-2 rounded">
      <input type="text" name="midInit" placeholder="Middle Initial" class="border p-2 rounded">
      <input type="text" name="lname" placeholder="Last Name" required class="border p-2 rounded">
      <input type="text" name="contact" placeholder="Contact Number" required class="border p-2 rounded">
      <input type="email" name="email" placeholder="Email Address" required class="border p-2 rounded col-span-2">
      <button type="submit" name="add" class="bg-sky-700 hover:bg-sky-800 text-white py-2 rounded col-span-2">
        Add Staff
      </button>
    </form>
  </div>

  <!-- Staff Table -->
  <div class="bg-white shadow-md rounded-lg p-6 overflow-x-auto">
    <h2 class="text-2xl font-semibold mb-4">Staff List (<?= $totalStaff ?> total)</h2>
    <table class="w-full border-collapse border border-gray-300 text-sm">
      <thead class="bg-sky-700 text-white">
        <tr>
          <th class="p-2 border text-center">#</th>
          <th class="p-2 border text-left">Name</th>
          <th class="p-2 border text-center">Contact</th>
          <th class="p-2 border text-left">Email</th>
          <th class="p-2 border text-center">Created</th>
          <th class="p-2 border text-center">Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php if ($staffList): ?>
          <?php foreach ($staffList as $index => $row): ?>
            <tr class="hover:bg-gray-100 transition">
              <td class="p-2 border text-center font-medium"><?= htmlspecialchars($row['staff_id']) ?></td>
              <td 
                class="p-2 border text-sky-700 font-semibold cursor-pointer hover:underline"
                onclick='openViewModal(<?= json_encode($row) ?>)'
              >
                <?= htmlspecialchars($row['staff_first_name'] . ' ' . $row['staff_last_name']) ?>
              </td>
              <td class="p-2 border text-center"><?= htmlspecialchars($row['staff_contact_num']) ?></td>
              <td class="p-2 border"><?= htmlspecialchars($row['staff_email']) ?></td>
              <td class="p-2 border text-center"><?= htmlspecialchars($row['staff_created_at']) ?></td>
              <td class="p-2 border text-center">
                <button onclick='openEditModal(<?= json_encode($row) ?>)' class="text-blue-600 hover:underline">Edit</button>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php else: ?>
          <tr><td colspan="6" class="text-center p-4 text-gray-500">No staff records found.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>

    <!-- Pagination -->
    <div class="flex justify-center items-center mt-6 space-x-2">
      <?php if ($page > 1): ?>
        <a href="?page=<?= $page - 1 ?>&search=<?= urlencode($keyword) ?>" class="px-3 py-1 bg-gray-300 rounded hover:bg-gray-400">Previous</a>
      <?php endif; ?>

      <?php for ($i = 1; $i <= $totalPages; $i++): ?>
        <a href="?page=<?= $i ?>&search=<?= urlencode($keyword) ?>"
           class="px-3 py-1 rounded <?= $i == $page ? 'bg-sky-700 text-white' : 'bg-gray-200 hover:bg-gray-300' ?>">
           <?= $i ?>
        </a>
      <?php endfor; ?>

      <?php if ($page < $totalPages): ?>
        <a href="?page=<?= $page + 1 ?>&search=<?= urlencode($keyword) ?>" class="px-3 py-1 bg-gray-300 rounded hover:bg-gray-400">Next</a>
      <?php endif; ?>
    </div>
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


<!-- View Modal -->
<div id="viewModal" class="fixed inset-0 bg-black bg-opacity-50 hidden justify-center items-center">
  <div class="bg-white rounded-lg shadow-lg p-6 w-[420px]">
    <h3 class="text-xl font-semibold text-sky-700 mb-4 text-center">Staff Details</h3>
    <div class="space-y-2 text-sm">
      <p><strong>ID:</strong> <span id="view_id"></span></p>
      <p><strong>Full Name:</strong> <span id="view_name"></span></p>
      <p><strong>Contact:</strong> <span id="view_contact"></span></p>
      <p><strong>Email:</strong> <span id="view_email"></span></p>
      <p><strong>Created At:</strong> <span id="view_created"></span></p>
      <p><strong>Updated At:</strong> <span id="view_updated"></span></p>
    </div>
    <div class="flex justify-center mt-6">
      <button onclick="closeViewModal()" class="px-5 py-2 bg-sky-700 text-white rounded hover:bg-sky-800">Close</button>
    </div>
  </div>
</div>

<!-- Edit Modal -->
<div id="editModal" class="fixed inset-0 bg-black bg-opacity-50 hidden justify-center items-center">
  <div class="bg-white rounded-lg shadow-lg p-6 w-96">
    <h3 class="text-lg font-semibold mb-4 text-sky-700">Edit Staff Information</h3>
    <form method="POST" class="grid grid-cols-1 gap-3">
      <input type="hidden" name="staff_id" id="edit_id">
      <input type="text" name="fname" id="edit_fname" required class="border p-2 rounded" placeholder="First Name">
      <input type="text" name="midInit" id="edit_midInit" class="border p-2 rounded" placeholder="Middle Initial">
      <input type="text" name="lname" id="edit_lname" required class="border p-2 rounded" placeholder="Last Name">
      <input type="text" name="contact" id="edit_contact" required class="border p-2 rounded" placeholder="Contact Number">
      <input type="email" name="email" id="edit_email" required class="border p-2 rounded" placeholder="Email">
      <div class="flex justify-end gap-2 mt-4">
        <button type="button" onclick="closeEditModal()" class="px-4 py-2 bg-gray-300 rounded hover:bg-gray-400">Cancel</button>
        <button type="submit" name="update" class="px-4 py-2 bg-sky-700 text-white rounded hover:bg-sky-800">Update</button>
      </div>
    </form>
  </div>
</div>

<!-- Alert Modal -->
<div id="alertModal" class="fixed inset-0 bg-black bg-opacity-50 hidden justify-center items-center">
  <div class="bg-white rounded-lg shadow-lg p-6 w-80 text-center">
    <h3 id="alertTitle" class="text-lg font-semibold mb-2"></h3>
    <p id="alertMessage" class="text-gray-700 mb-4"></p>
    <button onclick="closeModal()" class="bg-sky-700 text-white px-4 py-2 rounded hover:bg-sky-800">OK</button>
  </div>
</div>

<script>
function openViewModal(data) {
  document.getElementById("view_id").textContent = data.staff_id;
  document.getElementById("view_name").textContent = data.staff_first_name + " " + (data.staff_middle_init ? data.staff_middle_init + ". " : "") + data.staff_last_name;
  document.getElementById("view_contact").textContent = data.staff_contact_num;
  document.getElementById("view_email").textContent = data.staff_email;
  document.getElementById("view_created").textContent = data.staff_created_at ?? "—";
  document.getElementById("view_updated").textContent = data.staff_updated_at ?? "—";
  document.getElementById("viewModal").classList.remove("hidden");
  document.getElementById("viewModal").classList.add("flex");
}
function closeViewModal() {
  document.getElementById("viewModal").classList.add("hidden");
}
function openEditModal(data) {
  document.getElementById("edit_id").value = data.staff_id;
  document.getElementById("edit_fname").value = data.staff_first_name;
  document.getElementById("edit_midInit").value = data.staff_middle_init;
  document.getElementById("edit_lname").value = data.staff_last_name;
  document.getElementById("edit_contact").value = data.staff_contact_num;
  document.getElementById("edit_email").value = data.staff_email;
  document.getElementById("editModal").classList.remove("hidden");
  document.getElementById("editModal").classList.add("flex");
}
function closeEditModal() {
  document.getElementById("editModal").classList.add("hidden");
}
function showModal(title, message) {
  document.getElementById("alertTitle").textContent = title;
  document.getElementById("alertMessage").textContent = message;
  document.getElementById("alertModal").classList.remove("hidden");
  document.getElementById("alertModal").classList.add("flex");
}
function closeModal() {
  document.getElementById("alertModal").classList.add("hidden");
}
<?php if ($errorMsg): ?>
  showModal("Error", "<?= htmlspecialchars($errorMsg) ?>");
<?php elseif ($successMsg): ?>
  showModal("Success", "<?= htmlspecialchars($successMsg) ?>");
<?php endif; ?>
</script>
