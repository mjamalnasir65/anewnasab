<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
  <title>My Family Tree</title>
  <!-- Tailwind CSS -->
  <script src="https://cdn.tailwindcss.com"></script>
  <!-- Custom CSS -->
  <link rel="stylesheet" href="assets/css/style.css">
  <!-- PWA Manifest -->
  <link rel="manifest" href="manifest.json">
</head>
<body class="bg-gray-50">
  <!-- Landing Page (Login/Register) -->
  <div id="landing-page"></div>

  <!-- App Root (hidden by default) -->
  <div id="app" class="hidden">
    <!-- Navbar -->
    <div class="fixed top-0 left-0 w-full h-14 bg-white border-b border-gray-200 z-50">
      <div class="flex items-center justify-center relative h-full">
        <div class="flex-1 text-center text-lg font-medium">My Family Tree</div>
        <div class="absolute right-0 top-0 h-full flex items-center pr-4" id="navbar-right"></div>
      </div>
    </div>
    <!-- Form Panel -->
    <div id="form-panel" class="w-full max-w-full min-h-screen box-border p-3 bg-gray-50 border-b border-gray-200 mt-14">
  <div class="bg-white p-4 rounded-lg shadow-sm">
    <h2 class="text-center text-xl font-semibold mb-4">Welcome to Your Family Tree</h2>
    <p class="text-center text-gray-600 mb-6">Start building your family tree by adding your first member</p>
    <button class="w-full bg-blue-600 hover:bg-blue-700 text-white font-medium py-3 px-4 rounded-lg transition-colors" onclick="showAddMemberForm()">Add First Member</button>
  </div>
</div>
  <div id="tree-container" class="w-full min-h-80 bg-white">
    <!-- Page Content -->
    <div class="pt-0">
      <div id="tree-container" class="w-full min-h-80 bg-white">
        <div class="p-4 text-center">
          <p>Family tree will render here</p>
        </div>
      </div>
    </div>
  </div>
  <!-- Custom JS -->
  <script src="assets/js/main.js"></script>
  <!-- Register Service Worker -->
  <script>
    if ('serviceWorker' in navigator) {
      window.addEventListener('load', function() {
        navigator.serviceWorker.register('service-worker.js');
      });
    }
  </script>
</body>
</html> 