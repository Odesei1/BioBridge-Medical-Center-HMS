<?php
session_start();
require_once __DIR__ . "/../Config/database.php";
require_once __DIR__ . "/../Class/service.php";

// Disable caching
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Expires: 0");

// Only staff can access
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'staff') {
    header("Location: access_denied.php");
    exit();
}

$service = new Service();
$serviceName = $_GET['service'] ?? '';
$appointments = [];
$services = $service->all();

// Flags for modals
$updated = false;
$added = false;

// Handle Add or Update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_service'])) {
        $new_name = $_POST['new_serv_name'] ?? '';
        $new_desc = $_POST['new_serv_desc'] ?? '';
        $new_price = floatval($_POST['new_serv_price'] ?? 0);
        if ($new_name) {
            $service->add($new_name, $new_desc, $new_price);
            $added = true;
            $services = $service->all();
        }
    }
    if (isset($_POST['update_service'])) {
        $serv_id = $_POST['serv_id'] ?? null;
        $serv_name = $_POST['serv_name'] ?? '';
        $serv_desc = $_POST['serv_desc'] ?? '';
        $serv_price = floatval($_POST['serv_price'] ?? 0);
        if ($serv_id && $serv_name) {
            $service->update($serv_id, $serv_name, $serv_desc, $serv_price);
            $updated = true;
            $services = $service->all();
        }
    }
}

// If service clicked or searched
$selectedServiceID = null;
if (!empty($serviceName)) {
    foreach ($services as $srv) {
        if (strcasecmp($srv['serv_name'], $serviceName) === 0) {
            $selectedServiceID = $srv['serv_id'];
            break;
        }
    }
    if ($selectedServiceID) {
        $appointments = $service->getAppointmentsByService($selectedServiceID);
    }
}
?>

<?php include "../Includes/header.html"; ?>
<?php include "../Includes/navbar_staff_services.html"; ?>
<?php include "../Includes/staffSidebar.php"; ?>

<main class="flex-grow container mx-auto p-6">
  <h1 class="text-3xl font-bold text-sky-700 mb-6 text-center">Service Management</h1>

  <!-- ➕ Add New Service -->
  <section class="bg-white p-6 rounded-2xl shadow mb-10">
    <h2 class="text-2xl font-semibold text-blue-700 mb-3">➕ Add New Service</h2>
    <form method="POST" class="grid grid-cols-1 md:grid-cols-2 gap-4">
      <input type="text" name="new_serv_name" placeholder="Service Name" required class="border p-2 rounded">
      <input type="number" name="new_serv_price" placeholder="Service Price" step="0.01" class="border p-2 rounded">
      <textarea name="new_serv_desc" rows="3" placeholder="Description (optional)" class="border p-2 rounded col-span-2"></textarea>
      <button type="submit" name="add_service" class="bg-blue-700 hover:bg-blue-800 text-white py-2 rounded col-span-2">Add Service</button>
    </form>
  </section>

  <!-- ✏️ Update Service -->
  <section class="bg-white p-6 rounded-2xl shadow mb-10">
    <h2 class="text-2xl font-semibold text-green-700 mb-3">✏️ Update Service</h2>
    <form method="POST" class="grid grid-cols-1 md:grid-cols-2 gap-4">
      <input type="number" name="serv_id" placeholder="Service ID" required class="border p-2 rounded">
      <input type="text" name="serv_name" placeholder="New Service Name" required class="border p-2 rounded">
      <input type="number" name="serv_price" placeholder="New Service Price" step="0.01" class="border p-2 rounded">
      <textarea name="serv_desc" rows="3" placeholder="New Description (optional)" class="border p-2 rounded col-span-2"></textarea>
      <button type="submit" name="update_service" class="bg-green-700 hover:bg-green-800 text-white py-2 rounded col-span-2">Update Service</button>
    </form>
  </section>

  <!-- 📅 Browse Appointments by Service -->
  <section class="bg-white p-6 rounded-2xl shadow mb-10">
    <div class="flex justify-between items-center mb-4">
      <h2 class="text-2xl font-semibold text-sky-700">📅 Browse Appointments by Service</h2>
      <button onclick="openServicesModal()" class="bg-sky-500 hover:bg-sky-600 text-white px-4 py-2 rounded text-sm">
        📋 View All Services
      </button>
    </div>

    <?php if ($selectedServiceID): ?>
      <h3 class="text-xl font-semibold text-sky-700 mb-4">Appointments for “<?= htmlspecialchars($serviceName) ?>”</h3>
      <?php if (count($appointments) > 0): ?>
        <div class="overflow-x-auto">
          <table class="w-full border-collapse border border-gray-300 text-sm">
            <thead class="bg-sky-700 text-white">
              <tr>
                <th class="p-2 border text-center">Appt ID</th>
                <th class="p-2 border text-left">Patient</th>
                <th class="p-2 border text-center">Date</th>
                <th class="p-2 border text-center">Time</th>
                <th class="p-2 border text-left">Doctor</th>
                <th class="p-2 border text-left">Status</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($appointments as $appt): ?>
                <tr class="hover:bg-gray-100 transition">
                  <td class="p-2 border text-center"><?= htmlspecialchars($appt['appt_id']) ?></td>
                  <td class="p-2 border text-sky-700 font-medium"><?= htmlspecialchars($appt['pat_first_name'] . " " . $appt['pat_last_name']) ?></td>
                  <td class="p-2 border text-center"><?= htmlspecialchars($appt['appt_date']) ?></td>
                  <td class="p-2 border text-center"><?= htmlspecialchars($appt['appt_time']) ?></td>
                  <td class="p-2 border">Dr. <?= htmlspecialchars($appt['doc_first_name'] . " " . $appt['doc_last_name']) ?></td>
                  <td class="p-2 border"><?= htmlspecialchars($appt['stat_name']) ?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php else: ?>
        <p class="text-gray-600 text-center">No appointments found for this service.</p>
      <?php endif; ?>
    <?php elseif (!empty($serviceName)): ?>
      <p class="text-red-600 text-center font-semibold">Service not found. Please check the exact name.</p>
    <?php else: ?>
      <p class="text-gray-600 text-center">Click a service from the list below to view its appointments.</p>
    <?php endif; ?>
  </section>
</main>

<!-- 📋 All Services Modal -->
<div id="servicesModal" class="fixed inset-0 bg-black bg-opacity-50 hidden justify-center items-center z-50">
  <div class="bg-white rounded-lg shadow-lg p-6 w-[90%] md:w-[700px] max-h-[80vh] overflow-y-auto">
    <h3 class="text-2xl font-semibold text-sky-700 mb-4 text-center">📋 All Services</h3>
    <?php if (count($services) > 0): ?>
      <table class="w-full border-collapse border border-gray-300 text-sm">
        <thead class="bg-sky-700 text-white">
          <tr>
            <th class="p-2 border text-center">ID</th>
            <th class="p-2 border text-left">Service</th>
            <th class="p-2 border text-left">Description</th>
            <th class="p-2 border text-center">Price</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($services as $serv): ?>
            <tr class="hover:bg-gray-100 transition">
              <td class="p-2 border text-center"><?= htmlspecialchars($serv['serv_id']) ?></td>
              <td class="p-2 border font-semibold text-sky-700">
                <a href="staff_services.php?service=<?= urlencode($serv['serv_name']) ?>" class="hover:underline">
                  <?= htmlspecialchars($serv['serv_name']) ?>
                </a>
              </td>
              <td class="p-2 border"><?= htmlspecialchars($serv['serv_description'] ?? '—') ?></td>
              <td class="p-2 border text-center"><?= htmlspecialchars(number_format($serv['serv_price'], 2)) ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php else: ?>
      <p class="text-gray-600 text-center">No services found.</p>
    <?php endif; ?>
    <div class="flex justify-center mt-6">
      <button onclick="closeServicesModal()" class="bg-sky-700 hover:bg-sky-800 text-white px-6 py-2 rounded">Close</button>
    </div>
  </div>
</div>

<!-- 🔔 Notifications -->
<script>
function openServicesModal() { document.getElementById("servicesModal").classList.remove("hidden"); document.getElementById("servicesModal").classList.add("flex"); }
function closeServicesModal() { document.getElementById("servicesModal").classList.add("hidden"); }
<?php if ($updated): ?> alert("✅ Service updated successfully!"); <?php endif; ?>
<?php if ($added): ?> alert("✅ Service added successfully!"); <?php endif; ?>
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