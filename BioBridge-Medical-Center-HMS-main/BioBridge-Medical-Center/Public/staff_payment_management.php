<?php
session_start();
require_once __DIR__ . "/../Config/database.php";
require_once __DIR__ . "/../Class/payment.php";
require_once __DIR__ . "/../Class/payment_method.php";
require_once __DIR__ . "/../Class/payment_status.php";

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Expires: 0");

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'staff') {
    header("Location: access_denied.php");
    exit();
}

$payment = new Payment();
$method = new PaymentMethod();
$status = new PaymentStatus();

// Pagination
$limit = 10;
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;
$offset = ($page - 1) * $limit;

$allPayments = $payment->getAllPayments();
$totalPayments = count($allPayments);
$totalPages = ceil($totalPayments / $limit);
$payments = array_slice($allPayments, $offset, $limit);

$methods = $method->getAllPaymentMethods()->fetchAll(PDO::FETCH_ASSOC);
$statuses = $status->getAllPaymentStatus()->fetchAll(PDO::FETCH_ASSOC);

$activeTab = $_GET['tab'] ?? $_POST['tab'] ?? 'payments';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_payment'])) {
        $data = [
            'pymt_amount_paid' => $_POST['pymt_amount_paid'],
            'pymt_meth_id' => $_POST['pymt_meth_id'],
            'pymt_stat_id' => $_POST['pymt_stat_id'],
            'appt_id' => $_POST['appt_id']
        ];
        $payment->addPaymentRecord($data);
        header("Location: staff_payment_management.php?tab=payments&added=1");
        exit;
    }

    if (isset($_POST['update_payment'])) {
        $data = [
            'pymt_amount_paid' => $_POST['pymt_amount_paid'],
            'pymt_meth_id' => $_POST['pymt_meth_id'],
            'pymt_stat_id' => $_POST['pymt_stat_id'],
            'appt_id' => $_POST['appt_id']
        ];
        $payment->updatePayment($_POST['pymt_id'], $data);
        header("Location: staff_payment.php?tab=payments&updated=1");
        exit;
    }

    // Add / Update Payment Methods
    if (isset($_POST['add_method'])) {
        $method->addPaymentMethod(['pymt_meth_name' => $_POST['pymt_meth_name']]);
        header("Location: staff_payment.php?tab=methods&added=1");
        exit;
    }
    if (isset($_POST['update_method'])) {
        $method->updatePaymentMethod($_POST['pymt_meth_id'], ['pymt_meth_name' => $_POST['pymt_meth_name']]);
        header("Location: staff_payment.php?tab=methods&updated=1");
        exit;
    }

    // Add / Update Payment Status
    if (isset($_POST['add_status'])) {
        $status->addPaymentStatus(['pymt_stat_name' => $_POST['pymt_stat_name']]);
        header("Location: staff_payment.php?tab=statuses&added=1");
        exit;
    }
    if (isset($_POST['update_status'])) {
        $status->updatePaymentStatus($_POST['pymt_stat_id'], ['pymt_stat_name' => $_POST['pymt_stat_name']]);
        header("Location: staff_payment.php?tab=statuses&updated=1");
        exit;
    }
}
?>

<?php include "../Includes/header.html"; ?>
<?php include "../Includes/navbar_staff_dashboard.html"; ?>
<?php include "../Includes/staffSidebar.php"; ?>

<main class="flex-grow container mx-auto p-6">
  <h1 class="text-3xl font-bold text-sky-700 mb-8 text-center">💰 Payment Management</h1>

  <?php if (isset($_GET['added'])): ?>
    <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-6 text-center">✅ Record added successfully!</div>
  <?php elseif (isset($_GET['updated'])): ?>
    <div class="bg-blue-100 border border-blue-400 text-blue-700 px-4 py-3 rounded mb-6 text-center">✏️ Record updated successfully!</div>
  <?php endif; ?>

  <div class="flex justify-center mb-8 space-x-4">
    <button class="tab-btn <?= $activeTab === 'payments' ? 'bg-sky-700 text-white' : 'bg-sky-700 text-white hover:bg-sky-400' ?>" onclick="switchTab('payments')">Payments</button>
    <button class="tab-btn <?= $activeTab === 'methods' ? 'bg-sky-700 text-white' : 'bg-sky-700 text-white hover:bg-sky-400' ?>" onclick="switchTab('methods')">Payment Methods</button>
    <button class="tab-btn <?= $activeTab === 'statuses' ? 'bg-sky-700 text-white' : 'bg-sky-700 text-white hover:bg-sky-400' ?>" onclick="switchTab('statuses')">Payment Status</button>
  </div>

  <!-- PAYMENTS TAB -->
  <section id="payments" class="<?= $activeTab === 'payments' ? '' : 'hidden' ?>">
    <div id="paymentFormContainer" class="bg-white p-6 rounded-2xl shadow mb-10">
      <h2 id="formTitle" class="text-2xl font-semibold text-sky-700 mb-3">➕ Add Payment Record</h2>
      <form method="POST" id="paymentForm" class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <input type="hidden" name="tab" value="payments">
        <input type="hidden" name="pymt_id" id="pymt_id">
        <input type="number" name="pymt_amount_paid" id="pymt_amount_paid" placeholder="Amount Paid" required class="border p-2 rounded">
        <input type="text" name="appt_id" placeholder="Appointment ID (e.g. 2025-11-0000001)" required class="border p-2 rounded">
        <select name="pymt_meth_id" id="pymt_meth_id" required class="border p-2 rounded">
          <option value="">Select Payment Method</option>
          <?php foreach ($methods as $m): ?>
            <option value="<?= $m['pymt_meth_id'] ?>"><?= htmlspecialchars($m['pymt_meth_name']) ?></option>
          <?php endforeach; ?>
        </select>
        <select name="pymt_stat_id" id="pymt_stat_id" required class="border p-2 rounded">
          <option value="">Select Payment Status</option>
          <?php foreach ($statuses as $s): ?>
            <option value="<?= $s['pymt_stat_id'] ?>"><?= htmlspecialchars($s['pymt_stat_name']) ?></option>
          <?php endforeach; ?>
        </select>
        <button type="submit" id="submitBtn" name="add_payment" class="bg-sky-700 hover:bg-sky-800 text-white py-2 rounded col-span-2">Add Payment</button>
      </form>
    </div>

    <div class="bg-white p-6 rounded-2xl shadow">
      <h2 class="text-2xl font-semibold text-sky-700 mb-3">📋 All Payment Records</h2>
      <table class="w-full border-collapse border border-gray-300 text-sm">
        <thead class="bg-sky-700 text-white">
          <tr>
            <th class="p-2 border text-center">ID</th>
            <th class="p-2 border text-center">Amount</th>
            <th class="p-2 border text-center">Method</th>
            <th class="p-2 border text-center">Status</th>
            <th class="p-2 border text-center">Appointment</th>
            <th class="p-2 border text-center">Action</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($payments as $p): ?>
          <tr class="hover:bg-gray-100">
            <td class="p-2 border text-center"><?= $p['pymt_id'] ?></td>
            <td class="p-2 border text-center"><?= htmlspecialchars($p['pymt_amount_paid']) ?></td>
            <td class="p-2 border text-center"><?= htmlspecialchars($p['pymt_meth_name']) ?></td>
            <td class="p-2 border text-center"><?= htmlspecialchars($p['pymt_stat_name']) ?></td>
            <td class="p-2 border text-center"><?= htmlspecialchars($p['appt_id']) ?></td>
            <td class="p-2 border text-center">
              <button onclick='editPayment(<?= json_encode($p) ?>)' class="bg-green-600 hover:bg-green-700 text-white px-3 py-1 rounded text-xs">✏️ Edit</button>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>

      <?php if ($totalPages > 1): ?>
      <div class="flex justify-between items-center mt-4">
        <p class="text-sm text-gray-600">Showing <?= min($offset+1,$totalPayments) ?>–<?= min($offset+$limit,$totalPayments) ?> of <?= $totalPayments ?></p>
        <div class="flex gap-2">
          <?php for ($i=1;$i<=$totalPages;$i++): ?>
            <a href="?page=<?= $i ?>&tab=payments" class="px-3 py-1 border rounded <?= $i==$page?'bg-sky-700 text-white':'bg-white text-sky-700 hover:bg-sky-100' ?>"><?= $i ?></a>
          <?php endfor; ?>
        </div>
      </div>
      <?php endif; ?>
    </div>
  </section>

  <!-- METHODS TAB -->
  <section id="methods" class="<?= $activeTab === 'methods' ? '' : 'hidden' ?>">
    <div class="bg-white p-6 rounded-2xl shadow mb-10">
      <h2 class="text-2xl font-semibold text-sky-700 mb-3">➕ Add Payment Method</h2>
      <form method="POST" class="flex gap-3">
        <input type="hidden" name="tab" value="methods">
        <input type="text" name="pymt_meth_name" placeholder="e.g. Cash, Debit Card" required class="border p-2 rounded flex-1">
        <button type="submit" name="add_method" class="bg-sky-700 hover:bg-sky-800 text-white px-4 py-2 rounded">Add</button>
      </form>
    </div>

    <div class="bg-white p-6 rounded-2xl shadow">
      <h2 class="text-2xl font-semibold text-sky-700 mb-3">📋 All Payment Methods</h2>
      <table class="w-full border-collapse border border-gray-300 text-sm">
        <thead class="bg-sky-700 text-white"><tr><th>ID</th><th>Method</th></tr></thead>
        <tbody>
          <?php foreach ($methods as $m): ?>
          <tr class="hover:bg-gray-100">
            <td class="p-2 border text-center"><?= $m['pymt_meth_id'] ?></td>
            <td class="p-2 border"><?= htmlspecialchars($m['pymt_meth_name']) ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </section>

  <!-- STATUSES TAB -->
  <section id="statuses" class="<?= $activeTab === 'statuses' ? '' : 'hidden' ?>">
    <div class="bg-white p-6 rounded-2xl shadow mb-10">
      <h2 class="text-2xl font-semibold text-sky-700 mb-3">➕ Add Payment Status</h2>
      <form method="POST" class="flex gap-3">
        <input type="hidden" name="tab" value="statuses">
        <input type="text" name="pymt_stat_name" placeholder="e.g. Paid, Pending" required class="border p-2 rounded flex-1">
        <button type="submit" name="add_status" class="bg-sky-700 hover:bg-sky-800 text-white px-4 py-2 rounded">Add</button>
      </form>
    </div>

    <div class="bg-white p-6 rounded-2xl shadow">
      <h2 class="text-2xl font-semibold text-sky-700 mb-3">📋 All Payment Status</h2>
      <table class="w-full border-collapse border border-gray-300 text-sm">
        <thead class="bg-sky-700 text-white"><tr><th>ID</th><th>Status</th></tr></thead>
        <tbody>
          <?php foreach ($statuses as $s): ?>
          <tr class="hover:bg-gray-100">
            <td class="p-2 border text-center"><?= $s['pymt_stat_id'] ?></td>
            <td class="p-2 border"><?= htmlspecialchars($s['pymt_stat_name']) ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </section>
</main>

<script>
function switchTab(id){
  document.querySelectorAll("section").forEach(s=>s.classList.add("hidden"));
  document.getElementById(id).classList.remove("hidden");
}
function editPayment(data){
  document.getElementById("formTitle").textContent="✏️ Update Payment Record";
  document.getElementById("submitBtn").textContent="Update Payment";
  document.getElementById("submitBtn").name="update_payment";
  document.getElementById("pymt_id").value=data.pymt_id;
  document.getElementById("pymt_amount_paid").value=data.pymt_amount_paid;
  document.getElementById("appt_id").value=data.appt_id;
  document.getElementById("pymt_meth_id").value=data.pymt_meth_id;
  document.getElementById("pymt_stat_id").value=data.pymt_stat_id;
  document.getElementById("paymentFormContainer").scrollIntoView({behavior:"smooth"});
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