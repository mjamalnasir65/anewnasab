<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no" />
  <title>My Family Tree</title>
  <!-- Tailwind CSS (local build) -->
  <link href="assets/css/output.css" rel="stylesheet">
  <link rel="manifest" href="manifest.json" />
</head>
<body class="bg-gray-50">

  <div id="landing-page"></div>

  <!-- App Root (always visible for debug) -->
  <div id="app">
    <!-- Navbar -->
    <div class="fixed top-0 left-0 w-full h-14 bg-white border-b border-gray-200 z-50">
      <div class="flex items-center justify-center relative h-full">
        <div class="flex-1 text-center text-lg font-medium">My Family Tree</div>
        <div class="absolute right-0 top-0 h-full flex items-center pr-4" id="navbar-right"></div>
      </div>
    </div>

    <!-- Content: flex column taking full screen minus navbar -->
    <div class="pt-14 w-full h-[calc(100vh-3.5rem)] flex flex-col">
      <!-- Form Panel -->
      <div id="form-panel" class="bg-gray-50 p-4 border-b border-gray-200 flex-shrink-0 overflow-y-auto">
        <div class="bg-white p-4 rounded-lg shadow-sm">
          <h2 class="text-center text-xl font-semibold mb-4">Welcome to Your Family Tree</h2>
          <p class="text-center text-gray-600 mb-6">Start building your family tree by adding your first member</p>
          <button class="w-full bg-blue-600 hover:bg-blue-700 text-white font-medium py-3 px-4 rounded-lg transition-colors" onclick="showAddMemberForm()">Add First Member</button>
        </div>
      </div>

      <!-- Drag Handle -->
      <div id="dragHandle" class="w-full h-3 bg-gray-300 cursor-row-resize"></div>

      <!-- Tree Container -->
      <div id="tree-container" class="flex-grow bg-white overflow-auto">
        <div class="p-4 text-center">
          <p>Family tree will render here</p>
        </div>
      </div>
    </div>
  </div>

  <!-- Custom JS -->
  <script src="assets/js/main.js" defer></script>

  <!-- Draggable script -->
  <script>
    console.log('[DEBUG] index.php loaded, forcing app to display.');
    const dragHandle = document.getElementById('dragHandle');
    const formPanel = document.getElementById('form-panel');

    let isDragging = false;
    let startY = 0;
    let startHeight = 0;

    dragHandle.addEventListener('mousedown', (e) => {
      isDragging = true;
      startY = e.clientY;
      startHeight = formPanel.offsetHeight;
      document.body.style.cursor = 'row-resize';
      console.log('[DEBUG] drag start', {startY, startHeight});
    });

    window.addEventListener('mousemove', (e) => {
      if (!isDragging) return;
      const dy = e.clientY - startY;
      const newHeight = startHeight + dy;
      formPanel.style.height = `${newHeight}px`;
      console.log('[DEBUG] resizing', {dy, newHeight});
    });

    window.addEventListener('mouseup', () => {
      if (isDragging) console.log('[DEBUG] drag end');
      isDragging = false;
      document.body.style.cursor = '';
    });
  </script>

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
