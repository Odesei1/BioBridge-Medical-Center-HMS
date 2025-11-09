<?php
session_start();

// Prevent caching so browser doesn’t store private pages
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Expires: 0");

// Check if logged in
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'patient') {
    header("Location: access_denied.php");
    exit();
}

require_once __DIR__ . "/../Config/database.php";

$database = new Database();
$conn = $database->connect();

// Pagination setup
$limit = 5;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;
$offset = ($page - 1) * $limit;

// Filters
$keyword = $_GET['search'] ?? '';
$spec_id = $_GET['specialization'] ?? '';

// Fetch all specializations
$specStmt = $conn->query("SELECT spec_id, spec_name FROM specialization ORDER BY spec_name ASC");
$specializations = $specStmt->fetchAll(PDO::FETCH_ASSOC);

// Build query
$sql = "SELECT d.doc_id, d.doc_first_name, d.doc_middle_init, d.doc_last_name, 
               d.doc_contact_num, d.doc_email, s.spec_name
        FROM doctor d
        LEFT JOIN specialization s ON d.spec_id = s.spec_id
        WHERE 1";

$params = [];
if (!empty($keyword)) {
    $sql .= " AND (d.doc_first_name LIKE :kw OR d.doc_last_name LIKE :kw)";
    $params[':kw'] = "%$keyword%";
}
if (!empty($spec_id)) {
    $sql .= " AND s.spec_id = :sid";
    $params[':sid'] = $spec_id;
}

// Count total doctors
$totalStmt = $conn->prepare($sql);
$totalStmt->execute($params);
$totalRows = $totalStmt->rowCount();
$totalPages = ceil($totalRows / $limit);

// Fetch paginated doctors
$sql .= " ORDER BY d.doc_last_name ASC LIMIT :limit OFFSET :offset";
$stmt = $conn->prepare($sql);
foreach ($params as $key => $val) {
    $stmt->bindValue($key, $val);
}
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$doctors = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<?php include "../Includes/header.html"; ?>
<?php include "../Includes/navbar_patient_dashboard.html"; ?>
<?php include "../Includes/patientSidebar.php"; ?>

<main class="flex-grow p-6 max-w-6xl mx-auto">
  <h1 class="text-3xl font-bold text-sky-700 mb-6 text-center">Find a Doctor</h1>

  <!-- 🔍 Search & Filter -->
  <form method="GET" class="flex flex-wrap justify-center gap-3 mb-6">
    <input type="text" name="search" placeholder="Search by name..."
           value="<?= htmlspecialchars($keyword) ?>"
           class="border px-4 py-2 rounded-lg w-64 focus:ring-2 focus:ring-sky-500 outline-none">

    <select name="specialization" class="border px-4 py-2 rounded-lg w-64 focus:ring-2 focus:ring-sky-500 outline-none">
      <option value="">All Specializations</option>
      <?php foreach ($specializations as $s): ?>
        <option value="<?= $s['spec_id'] ?>" <?= $spec_id == $s['spec_id'] ? 'selected' : '' ?>>
          <?= htmlspecialchars($s['spec_name']) ?>
        </option>
      <?php endforeach; ?>
    </select>

    <button type="submit" class="bg-sky-700 text-white px-5 py-2 rounded-lg hover:bg-sky-800 transition">
      Search
    </button>
  </form>

  <!-- 🩺 Doctor List -->
  <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
    <?php if ($doctors): ?>
      <?php foreach ($doctors as $doc): ?>
        <div class="bg-white p-5 rounded-2xl shadow hover:shadow-xl hover:-translate-y-1 transition-transform duration-200">
          <h2 class="text-lg font-semibold text-sky-700">
            Dr. <?= htmlspecialchars($doc['doc_first_name'] . ' ' . ($doc['doc_middle_init'] ? $doc['doc_middle_init'] . '. ' : '') . $doc['doc_last_name']) ?>
          </h2>
          <p class="text-gray-600"><?= htmlspecialchars($doc['spec_name'] ?? 'General Practitioner') ?></p>
          <p class="text-sm text-gray-500 mt-2">📞 <?= htmlspecialchars($doc['doc_contact_num']) ?></p>
          <p class="text-sm text-gray-500">✉️ <?= htmlspecialchars($doc['doc_email']) ?></p>

          <a href="patient_appointments.php?doc_id=<?= $doc['doc_id'] ?>" 
             class="mt-4 inline-block bg-sky-700 text-white px-4 py-2 rounded-lg hover:bg-sky-800 transition">
             Book Appointment
          </a>
        </div>
      <?php endforeach; ?>
    <?php else: ?>
      <p class="text-center col-span-3 text-gray-500 py-8">No doctors found.</p>
    <?php endif; ?>
  </div>

  <!-- 📄 Pagination -->
  <?php if ($totalPages > 1): ?>
    <div class="flex justify-between items-center mt-8">
      <div class="text-sm text-gray-600">
        Showing <?= min($totalRows, $offset + 1) ?>–<?= min($offset + $limit, $totalRows) ?> of <?= $totalRows ?> doctors
      </div>

      <div class="flex gap-2">
        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
          <a href="?page=<?= $i ?>&search=<?= urlencode($keyword) ?>&specialization=<?= urlencode($spec_id) ?>"
             class="px-3 py-1 border rounded-lg <?= $i == $page ? 'bg-sky-700 text-white' : 'hover:bg-sky-100 text-sky-700' ?>">
            <?= $i ?>
          </a>
        <?php endfor; ?>
      </div>
    </div>
  <?php endif; ?>
</main>

<?php include "../Includes/footer.html"; ?>

<!-- 🧠 Prevent going back after logout -->
<script>
  const isLoggedIn = <?= isset($_SESSION['role']) ? 'true' : 'false'; ?>;
  window.history.pushState(null, null, window.location.href);

  window.onpopstate = function () {
    if (!isLoggedIn) {
      window.location.replace("access_denied.php");
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
