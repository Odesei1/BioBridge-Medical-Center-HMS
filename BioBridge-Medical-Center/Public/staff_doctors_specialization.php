<?php
session_start();
require_once __DIR__ . "/../Config/database.php";
require_once __DIR__ . "/../Class/doctor.php";

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Expires: 0");

// ✅ Restrict access to staff
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'staff') {
    header("Location: access_denied.php");
    exit();
}

$db = new Database();
$conn = $db->getConnection();
$doctor = new Doctor();

$specialization = $_GET['specialization'] ?? '';
$doctors = [];
$successMsg = '';
$errorMsg = '';

// ✅ Update specialization name directly
if (isset($_POST['update_specialization'])) {
    try {
        $stmt = $conn->prepare("
            UPDATE specialization 
            SET spec_name = :new_name, spec_updated_at = NOW() 
            WHERE spec_id = :spec_id
        ");
        $stmt->execute([
            ':new_name' => $_POST['new_name'],
            ':spec_id' => $_POST['specialization_id']
        ]);
        $successMsg = "✅ Specialization updated successfully!";
    } catch (Exception $e) {
        $errorMsg = "❌ Failed to update specialization: " . $e->getMessage();
    }
}

// ✅ Fetch doctors by specialization (if provided)
if (!empty($specialization)) {
    $stmt = $conn->prepare("
        SELECT d.doc_first_name, d.doc_last_name, d.doc_email, d.doc_contact_num, s.spec_name 
        FROM doctor d
        JOIN specialization s ON d.spec_id = s.spec_id
        WHERE LOWER(s.spec_name) LIKE LOWER(:spec_name)
        ORDER BY d.doc_last_name ASC
    ");
    $stmt->execute([':spec_name' => "%$specialization%"]);
    $doctors = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// ✅ Fetch all specializations for quick browse and modal
$specializations = $conn->query("
    SELECT spec_id, spec_name, spec_created_at 
    FROM specialization 
    ORDER BY spec_name ASC
")->fetchAll(PDO::FETCH_ASSOC);
?>

<?php include "../Includes/header.html"; ?>
<?php include "../Includes/navbar_staff_doctor_specialization.html"; ?>
<?php include "../Includes/staffSidebar.php"; ?>

<main class="flex-grow container mx-auto p-6">
  <h1 class="text-3xl font-bold text-sky-700 mb-6 text-center">Doctor Specializations</h1>

  <!-- ✅ Alerts -->
  <?php if ($successMsg): ?>
    <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-6 text-center">
      <?= htmlspecialchars($successMsg) ?>
    </div>
  <?php elseif ($errorMsg): ?>
    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6 text-center">
      <?= htmlspecialchars($errorMsg) ?>
    </div>
  <?php endif; ?>

  <p class="text-center text-gray-600 mb-10">
    Browse and update doctor specializations available in BioBridge Medical Center.
  </p>

  <!-- 🔎 Quick Browse by Specialization -->
  <section class="bg-white p-6 rounded-2xl shadow hover:shadow-2xl transition-all duration-300 mb-10">
    <h2 class="text-2xl font-semibold text-sky-700 mb-4">📋 Quick Browse by Specialization</h2>
    <p class="text-gray-600 mb-4">
      Click a specialization to instantly view all doctors who belong to that field.
    </p>
    <div class="flex flex-wrap gap-2">
      <?php foreach ($specializations as $spec): ?>
        <a href="?specialization=<?= urlencode($spec['spec_name']) ?>"
           class="bg-sky-600 text-white px-4 py-2 rounded hover:bg-sky-700 transition">
           <?= htmlspecialchars($spec['spec_name']) ?>
        </a>
      <?php endforeach; ?>
    </div>
  </section>

  <!-- 🔍 Search for a Specific Specialization -->
  <section class="bg-white p-6 rounded-2xl shadow hover:shadow-2xl transition-all duration-300 mb-10">
    <div class="flex justify-between items-center mb-3">
      <h2 class="text-2xl font-semibold text-sky-700">🔍 Search Specialization</h2>
      <button onclick="openSpecializationsModal()" class="bg-sky-500 hover:bg-sky-600 text-white px-4 py-2 rounded text-sm">
        📋 View All Specializations
      </button>
    </div>

    <form method="GET" class="flex gap-3 mb-6">
      <input 
        type="text" 
        name="specialization" 
        placeholder="Enter specialization (e.g., Internal Medicine)" 
        value="<?= htmlspecialchars($specialization) ?>"
        required
        class="flex-1 border border-gray-300 p-2 rounded focus:ring-2 focus:ring-sky-600"
      />
      <button 
        type="submit" 
        class="bg-sky-700 hover:bg-sky-800 text-white px-4 py-2 rounded">
        Browse Doctors →
      </button>
      <a 
        href="staff_doctors_specialization.php" 
        class="bg-sky-400 hover:bg-gray-500 text-white px-4 py-2 rounded no-underline">
        🔄 Refresh
      </a>
    </form>

    <?php if (!empty($specialization)): ?>
      <div class="mt-6">
        <h3 class="text-xl font-semibold text-sky-700 mb-3">
          👩‍⚕️ Doctors Specializing in “<?= htmlspecialchars($specialization) ?>”
        </h3>

        <?php if (count($doctors) > 0): ?>
          <div class="overflow-x-auto">
            <table class="w-full border-collapse border border-gray-300 text-sm">
              <thead class="bg-sky-700 text-white">
                <tr>
                  <th class="p-2 border text-left">Name</th>
                  <th class="p-2 border text-left">Email</th>
                  <th class="p-2 border text-center">Contact</th>
                  <th class="p-2 border text-left">Specialization</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($doctors as $doc): ?>
                  <tr class="hover:bg-gray-100 transition">
                    <td class="p-2 border text-sky-700 font-medium">
                      Dr. <?= htmlspecialchars($doc['doc_first_name'] . " " . $doc['doc_last_name']) ?>
                    </td>
                    <td class="p-2 border"><?= htmlspecialchars($doc['doc_email']) ?></td>
                    <td class="p-2 border text-center"><?= htmlspecialchars($doc['doc_contact_num']) ?></td>
                    <td class="p-2 border"><?= htmlspecialchars($doc['spec_name']) ?></td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php else: ?>
          <p class="text-gray-600 text-center">No doctors found with that specialization.</p>
        <?php endif; ?>
      </div>
    <?php endif; ?>
  </section>

  <!-- ✏️ Update Specialization -->
  <section class="bg-white p-6 rounded-2xl shadow hover:shadow-2xl transition-all duration-300">
    <h2 class="text-2xl font-semibold text-green-700 mb-3">✏️ Update Specialization</h2>
    <p class="text-gray-600 mb-4">
      Modify the name of an existing specialization. The system will confirm once updated.
    </p>

    <form method="POST" class="grid grid-cols-1 md:grid-cols-2 gap-4">
      <input type="number" name="specialization_id" placeholder="Specialization ID" required class="border p-2 rounded">
      <input type="text" name="new_name" placeholder="New Specialization Name" required class="border p-2 rounded">
      <button type="submit" name="update_specialization"
        class="bg-green-700 hover:bg-green-800 text-white py-2 rounded col-span-2">
        Update Specialization
      </button>
    </form>
  </section>
</main>

<!-- 📋 Modal for All Specializations -->
<div id="specializationsModal" class="fixed inset-0 bg-black bg-opacity-50 hidden justify-center items-center z-50">
  <div class="bg-white rounded-lg shadow-lg p-6 w-[90%] md:w-[700px] max-h-[80vh] overflow-y-auto">
    <h3 class="text-2xl font-semibold text-sky-700 mb-4 text-center">📋 All Specializations</h3>
    <?php if (count($specializations) > 0): ?>
      <table class="w-full border-collapse border border-gray-300 text-sm">
        <thead class="bg-sky-700 text-white">
          <tr>
            <th class="p-2 border text-center">ID</th>
            <th class="p-2 border text-left">Specialization</th>
            <th class="p-2 border text-center">Created At</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($specializations as $spec): ?>
            <tr class="hover:bg-gray-100 transition">
              <td class="p-2 border text-center"><?= htmlspecialchars($spec['spec_id']) ?></td>
              <td class="p-2 border font-semibold text-sky-700"><?= htmlspecialchars($spec['spec_name']) ?></td>
              <td class="p-2 border text-center"><?= htmlspecialchars($spec['spec_created_at'] ?? '—') ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php else: ?>
      <p class="text-gray-600 text-center">No specializations found.</p>
    <?php endif; ?>
    <div class="flex justify-center mt-6">
      <button onclick="closeSpecializationsModal()" class="bg-sky-700 hover:bg-sky-800 text-white px-6 py-2 rounded">Close</button>
    </div>
  </div>
</div>

<script>
function openSpecializationsModal() {
  document.getElementById("specializationsModal").classList.remove("hidden");
  document.getElementById("specializationsModal").classList.add("flex");
}
function closeSpecializationsModal() {
  document.getElementById("specializationsModal").classList.add("hidden");
}
</script>

<?php include "../Includes/footer.html"; ?>

<!-- 🧠 Prevent going back after logout -->
<script>
const isLoggedIn = <?php echo isset($_SESSION['role']) ? 'true' : 'false'; ?>;
window.history.pushState(null, null, window.location.href);
window.onpopstate = function () {
  if (!isLoggedIn) window.location.replace("access_denied.php");
};
window.addEventListener("pageshow", function (event) {
  if (event.persisted && !isLoggedIn) window.location.replace("access_denied.php");
});
</script>
</body>
</html>
