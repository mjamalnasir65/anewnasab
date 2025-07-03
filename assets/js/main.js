// Debug flag and logger
window.DEBUG = true;
window.logDebug = function(...args) {
  if (window.DEBUG) {
    console.log('[DEBUG]', ...args);
  }
};
window.logError = function(...args) {
  if (window.DEBUG) {
    console.error('[ERROR]', ...args);
  }
};

// Show login form by default on page load
window.onload = function() {
  checkSession();
};

function showLoginForm(message = '') {
  const landingPage = document.getElementById('landing-page');
  if (!landingPage) {
    logError('#landing-page not found!');
    return;
  }
  landingPage.innerHTML = `
    <div class="max-w-md mx-auto mt-10 p-6">
      <h2 class="text-2xl font-bold text-center mb-6">Login</h2>
      ${message ? `<div class='text-red-500 text-center mb-4'>${message}</div>` : ''}
      <div class="space-y-4">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Email</label>
          <input type="email" id="login-email" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Password</label>
          <input type="password" id="login-password" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
        </div>
      </div>
      <div class="mt-6 space-y-3">
        <button class="w-full bg-blue-500 hover:bg-blue-600 text-white font-medium py-3 px-4 rounded-lg transition-colors" onclick="submitLogin()">Login</button>
        <button class="w-full border border-blue-500 text-blue-500 hover:bg-blue-50 font-medium py-3 px-4 rounded-lg transition-colors" onclick="showRegisterForm()">New? Register</button>
        <button class="w-full text-blue-500 hover:text-blue-600 font-medium py-2 transition-colors" onclick="forgotPassword()">Forgot password?</button>
      </div>
    </div>
  `;
}

function showRegisterForm(message = '') {
  document.getElementById('landing-page').innerHTML = `
    <div class="max-w-md mx-auto mt-10 p-6">
      <h2 class="text-2xl font-bold text-center mb-6">Register</h2>
      ${message ? `<div class='text-red-500 text-center mb-4'>${message}</div>` : ''}
      <div class="space-y-4">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Email</label>
          <input type="email" id="register-email" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Phone (optional)</label>
          <input type="text" id="register-phone" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Password</label>
          <input type="password" id="register-password" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
        </div>
      </div>
      <div class="mt-6 space-y-3">
        <button class="w-full bg-blue-500 hover:bg-blue-600 text-white font-medium py-3 px-4 rounded-lg transition-colors" onclick="submitRegister()">Register</button>
        <button class="w-full border border-blue-500 text-blue-500 hover:bg-blue-50 font-medium py-3 px-4 rounded-lg transition-colors" onclick="showLoginForm()">Back to Login</button>
      </div>
    </div>
  `;
}

function forgotPassword() {
  alert('Forgot password feature coming soon!');
}

function showAppUI() {
  logDebug('showAppUI called');
  const landingPage = document.getElementById('landing-page');
  const app = document.getElementById('app');
  if (landingPage) landingPage.classList.add('hidden');
  if (app) app.classList.remove('hidden');
  let navbarRight = document.getElementById('navbar-right');
  if (!document.getElementById('logout-btn')) {
    let logoutBtn = document.createElement('button');
    logoutBtn.id = 'logout-btn';
    logoutBtn.className = 'text-blue-500 hover:text-blue-600 font-medium px-3 py-2 transition-colors';
    logoutBtn.innerText = 'Logout';
    logoutBtn.onclick = logout;
    navbarRight.appendChild(logoutBtn);
  }
  fetchAndDisplayMembers();
}

function hideAppUI() {
  const app = document.getElementById('app');
  const landingPage = document.getElementById('landing-page');
  if (app) app.classList.add('hidden');
  if (landingPage) landingPage.classList.remove('hidden');
  let logoutBtn = document.getElementById('logout-btn');
  if (logoutBtn) logoutBtn.remove();
}

// Check if members exist and show tree or welcome panel
function checkAndShowTreeOrWelcome() {
  logDebug('checkAndShowTreeOrWelcome called');
  fetch('api/member.php?action=tree')
    .then(res => res.json())
    .then(data => {
      logDebug('Tree API response:', data);
      if (data.success && data.members && data.members.length > 0) {
        logDebug('Members found, showing app UI');
        showAppUI();
        renderFamilyTree(data.members);
        // Hide welcome panel if present
        const formPanel = document.getElementById('form-panel');
        if (formPanel) {
          // Only clear if it contains the welcome panel
          if (formPanel.innerHTML.includes('Welcome to Your Family Tree')) {
            formPanel.innerHTML = '';
          }
        }
      } else {
        logDebug('No members found, showing welcome panel');
        hideAppUI();
        showWelcomePanel();
      }
    })
    .catch((err) => {
      logError('Tree API failed:', err);
      hideAppUI();
      showWelcomePanel();
    });
}

// Update checkSession to use checkAndShowTreeOrWelcome
function checkSession() {
  fetch('api/session.php')
    .then(res => res.json())
    .then(data => {
      logDebug('Session check result:', data);
      if (data.success) {
        logDebug('Session valid, calling checkAndShowTreeOrWelcome');
        checkAndShowTreeOrWelcome();
      } else {
        logDebug('Session invalid, showing login form');
        hideAppUI();
        showLoginForm();
      }
    })
    .catch((err) => {
      logError('Session check failed:', err);
      hideAppUI();
      showLoginForm();
    });
}

function logout() {
  fetch('api/logout.php')
    .then(res => res.json())
    .then(() => {
      hideAppUI();
      showLoginForm('You have been logged out.');
    });
}

// Update submitLogin to use checkSession (which now uses checkAndShowTreeOrWelcome)
function submitLogin() {
  const email = document.getElementById('login-email').value.trim();
  const password = document.getElementById('login-password').value;
  if (!email || !password) {
    showLoginForm('Please enter both email and password.');
    return;
  }
  fetch('api/login.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ email, password })
  })
    .then(res => res.json())
    .then(data => {
      if (data.success) {
        checkSession();
      } else {
        showLoginForm(data.error || 'Login failed.');
      }
    })
    .catch(() => showLoginForm('Login failed.'));
}

function submitRegister() {
  const email = document.getElementById('register-email').value.trim();
  const phone = document.getElementById('register-phone').value.trim();
  const password = document.getElementById('register-password').value;
  if (!email || !password) {
    showRegisterForm('Please enter email and password.');
    return;
  }
  fetch('api/register.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ email, phone, password })
  })
    .then(res => res.json())
    .then(data => {
      if (data.success) {
        showLoginForm('Registration successful! Please log in.');
      } else {
        showRegisterForm(data.error || 'Registration failed.');
      }
    })
    .catch(() => showRegisterForm('Registration failed.'));
}

// Loader function to load HTML partials into the form panel
function loadPanel(url, callback) {
  fetch(url)
    .then(res => res.text())
    .then(html => {
      document.getElementById('form-panel').innerHTML = html;
      if (callback) callback();
    });
}

// Show welcome panel
function showWelcomePanel() {
  loadPanel('components/welcome.html');
}

// Show add member form
function showAddMemberForm() {
  loadPanel('components/add-member.html', () => {
    // Attach tab switching logic after loading
    window.showFormTab = function(tab) {
      document.querySelectorAll('.tab').forEach(el => {
        el.classList.remove('border-b-2', 'border-blue-600', 'text-blue-600');
        el.classList.add('text-gray-600');
      });
      document.querySelector('.tab[onclick*="' + tab + '"]').classList.add('border-b-2', 'border-blue-600', 'text-blue-600');
      document.querySelector('.tab[onclick*="' + tab + '"]').classList.remove('text-gray-600');
      document.querySelectorAll('.tab-content').forEach(el => el.classList.add('hidden'));
      document.getElementById(tab).classList.remove('hidden');
    };
    // Default to personal tab
    window.showFormTab('personal');
    // Dummy uploadPhoto function
    window.uploadPhoto = function() { alert('Photo upload feature coming soon!'); };
  });
}

function showMemberSummary(member) {
  document.getElementById('form-panel').innerHTML = `
    <div class="bg-white p-4 rounded-lg shadow-sm">
      <h2 class="text-center text-xl font-semibold mb-4">Me</h2>
      <button onclick="uploadPhoto()" class="w-full py-2 mb-4 bg-blue-600 text-white rounded hover:bg-blue-700 transition-colors">Add my photo</button>

      <div class="flex justify-around mb-4 border-b">
        <div class="tab cursor-pointer pb-2 border-b-2 border-blue-600 text-blue-600 font-medium" onclick="showSummaryTab('personal')">Personal</div>
        <div class="tab cursor-pointer pb-2 text-gray-600 font-medium" onclick="showSummaryTab('contact')">Contact</div>
        <div class="tab cursor-pointer pb-2 text-gray-600 font-medium" onclick="showSummaryTab('bio')">Biography</div>
      </div>

      <div id="personal" class="tab-content">
        <div class="mb-4 space-y-1">
          <p><strong>Full name:</strong> ${member.full_name}</p>
          <p><strong>Gender:</strong> ${member.gender}</p>
          <p><strong>Birth date:</strong> ${member.birth_date}</p>
          ${member.death_date ? `<p><strong>Death date:</strong> ${member.death_date}</p>` : ''}
          <p><strong>Tree stats:</strong> No ancestors, No descendants</p>
          ${member.notes ? `<p><strong>Notes:</strong> ${member.notes}</p>` : ''}
        </div>
        <button onclick='showAddMemberForm(${JSON.stringify(member)})' class="w-full py-2 bg-blue-600 text-white rounded hover:bg-blue-700 transition-colors">Edit my details</button>
      </div>

      <div id="contact" class="tab-content hidden">
        <div class="mb-4 space-y-1">
          ${member.email ? `<p><strong>Email:</strong> ${member.email}</p>` : '<p><strong>Email:</strong> Not provided</p>'}
          ${member.phone ? `<p><strong>Phone:</strong> ${member.phone}</p>` : '<p><strong>Phone:</strong> Not provided</p>'}
        </div>
        <button onclick='showAddMemberForm(${JSON.stringify(member)})' class="w-full py-2 bg-blue-600 text-white rounded hover:bg-blue-700 transition-colors">Edit contact</button>
      </div>

      <div id="bio" class="tab-content hidden">
        <div class="mb-4 space-y-1">
          ${member.bio_notes ? `<p><strong>Biography:</strong> ${member.bio_notes}</p>` : '<p><strong>Biography:</strong> No biography added yet.</p>'}
        </div>
        <button onclick='showAddMemberForm(${JSON.stringify(member)})' class="w-full py-2 bg-blue-600 text-white rounded hover:bg-blue-700 transition-colors">Edit biography</button>
      </div>

      <div class="mt-6 space-y-2">
        <p class="text-sm text-gray-600">Click to add your relatives:</p>
        <button onclick="addRelative('parents')" class="w-full py-2 border border-blue-600 text-blue-600 rounded hover:bg-blue-50 transition-colors">Add new parents</button>
        <button onclick="addRelative('partner')" class="w-full py-2 border border-blue-600 text-blue-600 rounded hover:bg-blue-50 transition-colors">Add partner/ex</button>
        <button onclick="addRelative('sibling')" class="w-full py-2 border border-blue-600 text-blue-600 rounded hover:bg-blue-50 transition-colors">Add brother/sister</button>
        <button onclick="addRelative('child')" class="w-full py-2 border border-blue-600 text-blue-600 rounded hover:bg-blue-50 transition-colors">Add child</button>
      </div>
    </div>
  `;
}

function showSummaryTab(tab) {
  // Update tab buttons
  document.querySelectorAll('.tab').forEach(el => {
    el.classList.remove('border-b-2', 'border-blue-600', 'text-blue-600');
    el.classList.add('text-gray-600');
  });
  document.querySelector('.tab[onclick*="' + tab + '"]').classList.add('border-b-2', 'border-blue-600', 'text-blue-600');
  document.querySelector('.tab[onclick*="' + tab + '"]').classList.remove('text-gray-600');

  // Update tab content
  document.querySelectorAll('.tab-content').forEach(el => el.classList.add('hidden'));
  document.getElementById(tab).classList.remove('hidden');
}

function addRelative(type) {
  if (!window.currentMember) {
    alert('No member selected.');
    return;
  }
  window.relationshipContext.memberId = window.currentMember.id;
  window.relationshipContext.relationshipType = type;

  if (type === 'parents') {
    addParents(window.currentMember.id, window.currentMember.full_name);
  } else if (type === 'partner') {
    showAddPartnerForm(window.currentMember.id, window.currentMember.full_name);
  } else if (type === 'sibling') {
    showAddSiblingForm(window.currentMember.id, window.currentMember.full_name);
  } else if (type === 'child') {
    showAddChildForm(window.currentMember.id, window.currentMember.full_name);
  } else {
    alert('Feature coming soon!');
  }
}

// Example: Add Partner Form (implement similar for sibling/child)
function showAddPartnerForm(memberId, memberName) {
  document.getElementById('form-panel').innerHTML = `
    <div class="bg-white p-4 rounded-lg shadow-sm">
      <h2 class="text-xl font-semibold mb-4">Add Partner for ${memberName}</h2>
      <div class="mb-4">
        <label class="block mb-1">Full Name</label>
        <input type="text" id="partner-full-name" class="w-full border px-2 py-1 rounded" />
      </div>
      <div class="mb-4">
        <label class="block mb-1">Gender</label>
        <select id="partner-gender" class="w-full border px-2 py-1 rounded">
          <option value="">Select gender</option>
          <option value="male">Male</option>
          <option value="female">Female</option>
        </select>
      </div>
      <div class="mb-4">
        <label class="block mb-1">Birth Date</label>
        <input type="date" id="partner-birth-date" class="w-full border px-2 py-1 rounded" />
      </div>
      <button class="bg-blue-600 text-white px-4 py-2 rounded" onclick="submitAddPartner()">Add Partner</button>
      <button class="ml-2 px-4 py-2 rounded border" onclick="fetchAndDisplayMembers()">Cancel</button>
    </div>
  `;
}

window.submitAddPartner = function() {
  const full_name = document.getElementById('partner-full-name').value.trim();
  const gender = document.getElementById('partner-gender').value;
  const birth_date = document.getElementById('partner-birth-date').value;
  const memberId = window.relationshipContext.memberId;

  if (!full_name || !gender || !birth_date) {
    alert('Full name, gender, and birth date are required');
    return;
  }

  const body = JSON.stringify({
    member_id: memberId,
    full_name,
    gender,
    birth_date
  });

  logDebug('Submitting partner:', body);

  fetch('api/member.php?action=add_partner', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body
  })
    .then(res => res.json())
    .then(data => {
      logDebug('Response:', data);
      if (data.success) {
        fetchAndDisplayMembers();
      } else {
        logError('API responded with failure:', data);
        alert(data.error || 'Failed to add partner.');
      }
    })
    .catch(err => {
      logError('addPartner failed:', err);
      alert('Failed to add partner.');
    });
};

// Repeat similar for sibling and child forms/actions

// Global context for relationship actions
window.relationshipContext = {
  memberId: null,
  relationshipType: null
};

// When user clicks a node, set the context
function showMemberDetail(memberId) {
  window.relationshipContext.memberId = memberId;
  // ...existing code...
}

// Add relative actions set the context and open the form
function addRelative(type) {
  if (!window.currentMember) {
    alert('No member selected.');
    return;
  }
  window.relationshipContext.memberId = window.currentMember.id;
  window.relationshipContext.relationshipType = type;

  if (type === 'parents') {
    addParents(window.currentMember.id, window.currentMember.full_name);
  } else if (type === 'partner') {
    showAddPartnerForm(window.currentMember.id, window.currentMember.full_name);
  } else if (type === 'sibling') {
    showAddSiblingForm(window.currentMember.id, window.currentMember.full_name);
  } else if (type === 'child') {
    showAddChildForm(window.currentMember.id, window.currentMember.full_name);
  } else {
    alert('Feature coming soon!');
  }
}

// Example: Add Partner Form (implement similar for sibling/child)
function showAddPartnerForm(memberId, memberName) {
  document.getElementById('form-panel').innerHTML = `
    <div class="bg-white p-4 rounded-lg shadow-sm">
      <h2 class="text-xl font-semibold mb-4">Add Partner for ${memberName}</h2>
      <div class="mb-4">
        <label class="block mb-1">Full Name</label>
        <input type="text" id="partner-full-name" class="w-full border px-2 py-1 rounded" />
      </div>
      <div class="mb-4">
        <label class="block mb-1">Gender</label>
        <select id="partner-gender" class="w-full border px-2 py-1 rounded">
          <option value="">Select gender</option>
          <option value="male">Male</option>
          <option value="female">Female</option>
        </select>
      </div>
      <div class="mb-4">
        <label class="block mb-1">Birth Date</label>
        <input type="date" id="partner-birth-date" class="w-full border px-2 py-1 rounded" />
      </div>
      <button class="bg-blue-600 text-white px-4 py-2 rounded" onclick="submitAddPartner()">Add Partner</button>
      <button class="ml-2 px-4 py-2 rounded border" onclick="fetchAndDisplayMembers()">Cancel</button>
    </div>
  `;
}

window.submitAddPartner = function() {
  const full_name = document.getElementById('partner-full-name').value.trim();
  const gender = document.getElementById('partner-gender').value;
  const birth_date = document.getElementById('partner-birth-date').value;
  const memberId = window.relationshipContext.memberId;

  if (!full_name || !gender || !birth_date) {
    alert('Full name, gender, and birth date are required');
    return;
  }

  const body = JSON.stringify({
    member_id: memberId,
    full_name,
    gender,
    birth_date
  });

  logDebug('Submitting partner:', body);

  fetch('api/member.php?action=add_partner', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body
  })
    .then(res => res.json())
    .then(data => {
      logDebug('Response:', data);
      if (data.success) {
        fetchAndDisplayMembers();
      } else {
        logError('API responded with failure:', data);
        alert(data.error || 'Failed to add partner.');
      }
    })
    .catch(err => {
      logError('addPartner failed:', err);
      alert('Failed to add partner.');
    });
};

// Repeat similar for sibling and child forms/actions