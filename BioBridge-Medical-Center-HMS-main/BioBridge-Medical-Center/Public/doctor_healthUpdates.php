<?php
session_start();

// Prevent caching so browser doesn’t store private pages
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Expires: 0");

// If not logged in, redirect
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'doctor') {
    header("Location: access_denied.php");
    exit();
}
?>
<?php include "../Includes/header.html"; ?>
<?php include "../Includes/navbar_doctor_healthUpdates.html"; ?>
<?php include "../Includes/doctorSidebar.php"; ?>

<main class="flex-grow container mx-auto p-6">
  <!-- Page Title -->
  <h1 class="text-3xl font-bold text-sky-700 mb-6 text-center">Health Updates</h1>
  <p class="text-center text-gray-600 mb-10">
    Stay informed with the latest BioBridge Medical Center health tips and world medical updates.
  </p>

  <!-- Health Tips Section -->
  <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
    <!-- Tip 1 -->
    <div
      class="bg-white rounded-2xl overflow-hidden shadow-lg hover:shadow-2xl hover:-translate-y-2 transition-transform duration-300 ease-in-out">
      <img src="../Assets/health-hydration.jpg" alt="Stay Hydrated" class="h-48 w-full object-cover">
      <div class="p-5">
        <h3 class="text-xl font-semibold text-sky-700 mb-2">💧 Stay Hydrated</h3>
        <p class="text-gray-600 text-sm mb-3">
          Drinking enough water helps maintain energy levels, supports brain function, and promotes healthy skin.
        </p>
        <span class="text-sky-600 text-xs font-medium">#HealthyLiving</span>
      </div>
    </div>

    <!-- Tip 2 -->
    <div
      class="bg-white rounded-2xl overflow-hidden shadow-lg hover:shadow-2xl hover:-translate-y-2 transition-transform duration-300 ease-in-out">
      <img src="../Assets/health-vaccine.jpg" alt="Get Vaccinated" class="h-48 w-full object-cover">
      <div class="p-5">
        <h3 class="text-xl font-semibold text-green-600 mb-2">💉 Get Vaccinated</h3>
        <p class="text-gray-600 text-sm mb-3">
          Vaccines are your best defense against preventable diseases. Stay up to date to protect yourself and others.
        </p>
        <span class="text-green-600 text-xs font-medium">#ImmunityMatters</span>
      </div>
    </div>

    <!-- Tip 3 -->
    <div
      class="bg-white rounded-2xl overflow-hidden shadow-lg hover:shadow-2xl hover:-translate-y-2 transition-transform duration-300 ease-in-out">
      <img src="../Assets/health-exercise.png" alt="Exercise Regularly" class="h-48 w-full object-cover">
      <div class="p-5">
        <h3 class="text-xl font-semibold text-red-600 mb-2">🏃‍♀️ Exercise Regularly</h3>
        <p class="text-gray-600 text-sm mb-3">
          30 minutes of daily physical activity strengthens your heart, reduces stress, and improves overall health.
        </p>
        <span class="text-red-600 text-xs font-medium">#StayActive</span>
      </div>
    </div>
  </div>

  <!-- World Health News / Announcements -->
  <section
    class="mt-12 bg-white p-6 rounded-2xl shadow-lg hover:shadow-2xl hover:-translate-y-2 transition-transform duration-300 ease-in-out">
    <h2 class="text-2xl font-semibold text-sky-700 mb-3">🌍 World Health Highlights</h2>
    <p class="text-gray-600 mb-5">
      Real-world updates and announcements from BioBridge and global health organizations.
    </p>

    <?php
    // ✅ Fetch latest WHO headlines (RSS feed)
    $rss_url = "https://www.who.int/feeds/entity/csr/don/en/rss.xml";
    $rss = @simplexml_load_file($rss_url);

    if ($rss && isset($rss->channel->item)) {
        echo '<ul class="list-disc pl-5 text-gray-700 text-sm space-y-2">';
        $count = 0;
        foreach ($rss->channel->item as $item) {
            if ($count >= 3) break; // Show only top 3 headlines
            $title = htmlspecialchars($item->title);
            $link = htmlspecialchars($item->link);
            echo "<li><a href='$link' target='_blank' class='hover:underline text-sky-700'>$title</a></li>";
            $count++;
        }
        echo '</ul>';
    } else {
        echo '<p class="text-gray-500 text-sm italic">Unable to load latest WHO updates at the moment. Please check back later.</p>';
    }
    ?>

    <a href="https://www.who.int/news-room/releases" target="_blank"
      class="mt-4 inline-block text-sky-700 hover:underline text-sm font-medium">View More World Updates →</a>
  </section>
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