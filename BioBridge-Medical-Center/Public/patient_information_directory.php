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

require_once __DIR__ . "/../Config/database.php";
require_once __DIR__ . "/../Class/patient.php";

$patient = new Patient();

$keyword = $_GET['search'] ?? "";
$perPage = 10;
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$start = ($page - 1) * $perPage;

// 🔍 Search or view all (for patient, limit columns)
if (!empty($keyword)) {
    $allPatients = $patient->searchByName($keyword);
} else {
    $allPatients = $patient->viewAll();
}

// Paginate
$totalPatients = count($allPatients);
$patients = array_slice($allPatients, $start, $perPage);
$totalPages = ceil($totalPatients / $perPage);
?>

<?php include "../Includes/header.html"; ?>
<?php include "../Includes/navbar_patient_information_directory.html"; ?>
<?php include "../Includes/patientSidebar.php"; ?>

<main class="flex-grow container mx-auto p-6">
  <h1 class="text-3xl font-bold text-sky-700 mb-6 text-center">Patient Directory</h1>

<!-- 🔍 Search Form with Refresh -->
<form method="GET" class="mb-4 flex justify-center space-x-2">
  <input type="text" name="search" placeholder="Search by first or last name..." 
         value="<?= htmlspecialchars($keyword) ?>" 
         class="border rounded-l-lg px-4 py-2 w-80 focus:outline-none focus:ring-2 focus:ring-sky-500" />
  <button type="submit" class="bg-sky-700 text-white px-4 py-2 rounded-r-lg hover:bg-sky-800">
    Search
  </button>

  <!-- 🔁 Refresh Button -->
  <a href="<?= basename($_SERVER['PHP_SELF']); ?>" 
     class="bg-gray-500 text-white px-4 py-2 rounded-lg hover:bg-gray-600 transition">
    Refresh
  </a>
</form>


  <!-- 🧾 Patient Table -->
  <div class="bg-white shadow-md rounded-lg p-6 overflow-x-auto">
    <table class="w-full border-collapse border border-gray-300 text-sm">
      <thead class="bg-sky-700 text-white">
        <tr>
          <th class="p-2 border text-center">#</th>
          <th class="p-2 border text-left">Full Name</th>
          <th class="p-2 border text-center">Gender</th>
          <th class="p-2 border text-left">Address</th>
        </tr>
      </thead>
      <tbody>
        <?php if ($patients): ?>
          <?php foreach ($patients as $index => $row): ?>
            <tr class="hover:bg-gray-100 transition">
              <td class="p-2 border text-center font-medium"><?= htmlspecialchars($row['pat_id']) ?></td>
              <td class="p-2 border text-sky-700 font-semibold">
                <?= htmlspecialchars($row['pat_first_name'] . ' ' . ($row['pat_middle_init'] ? $row['pat_middle_init'] . '. ' : '') . $row['pat_last_name']) ?>
              </td>
              <td class="p-2 border text-center"><?= htmlspecialchars($row['pat_gender']) ?></td>
              <td class="p-2 border"><?= htmlspecialchars($row['pat_address']) ?></td>
            </tr>
          <?php endforeach; ?>
        <?php else: ?>
          <tr><td colspan="4" class="text-center p-4 text-gray-500">No records found.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>

    <!-- 📄 Pagination -->
    <?php if ($totalPages > 1): ?>
      <div class="flex justify-center mt-4 space-x-2">
        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
          <a href="?page=<?= $i ?>&search=<?= urlencode($keyword) ?>"
             class="px-3 py-1 rounded border <?= $i == $page ? 'bg-sky-700 text-white' : 'bg-white text-sky-700 hover:bg-sky-100' ?>">
            <?= $i ?>
          </a>
        <?php endfor; ?>
      </div>
    <?php endif; ?>
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
