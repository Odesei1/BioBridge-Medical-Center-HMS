<?php
// access_denied.php
session_start();
?>

<?php include "../Includes/header.html"; ?>
<main class="flex flex-col items-center justify-center h-screen bg-gray-100 p-6">
  <div class="bg-white shadow-lg rounded-2xl p-10 max-w-lg text-center">
    <h1 class="text-6xl font-bold text-red-600 mb-4">⛔</h1>
    <h2 class="text-3xl font-semibold text-gray-800 mb-4">Access Denied</h2>
    <p class="text-gray-600 mb-6">
      You do not have permission to access this page. Please contact your administrator if you believe this is an error.
    </p>
    <a href="../index.php" class="bg-red-600 hover:bg-red-700 text-white px-6 py-3 rounded-lg font-medium transition-colors">
      Go Back to Dashboard
    </a>
  </div>
</main>
<?php include "../Includes/footer.html"; ?>
