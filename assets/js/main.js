// Show login form by default on page load
window.onload = function() {
  checkSession();
};

function showLoginForm(message = '') {
  document.getElementById('landing-page').innerHTML = `
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
  document.getElementById('landing-page').classList.add('hidden');
  document.getElementById('app').classList.remove('hidden');
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
  document.getElementById('app').classList.add('hidden');
  document.getElementById('landing-page').classList.remove('hidden');
  let logoutBtn = document.getElementById('logout-btn');
  if (logoutBtn) logoutBtn.remove();
}

// Check if members exist and show tree or welcome panel
function checkAndShowTreeOrWelcome() {
  fetch('api/member.php')
    .then(res => res.json())
    .then(data => {
      if (data.success && data.members && data.members.length > 0) {
        showAppUI();
        renderMemberList(data.members);
      } else {
        hideAppUI();
        showWelcomePanel();
      }
    })
    .catch(() => {
      hideAppUI();
      showWelcomePanel();
    });
}

// Update checkSession to use checkAndShowTreeOrWelcome
function checkSession() {
  fetch('api/session.php')
    .then(res => res.json())
    .then(data => {
      if (data.success) {
        checkAndShowTreeOrWelcome();
      } else {
        hideAppUI();
        showLoginForm();
      }
    })
    .catch(() => {
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
  if (type === 'parents' && window.currentMember) {
    addParents(window.currentMember.id, window.currentMember.full_name);
  } else {
    alert('Feature coming soon!');
  }
}

function fetchAndDisplayMembers() {
  fetch('api/member.php')
    .then(res => res.json())
    .then(data => {
      if (data.success) {
        renderMemberList(data.members);
      } else {
        document.getElementById('tree-container').innerHTML = '<div class="p-4 text-center"><p>No members found.</p></div>';
      }
    })
    .catch(() => {
      document.getElementById('tree-container').innerHTML = '<div class="p-4 text-center"><p>Failed to load members.</p></div>';
    });
}

// Render member list as clickable nodes
function renderMemberList(members) {
  if (!members || members.length === 0) {
    document.getElementById('tree-container').innerHTML = '<div class="p-4 text-center"><p>No members found.</p></div>';
    return;
  }
  let html = '<div class="p-4"><h3 class="text-lg font-bold mb-4">Family Members</h3><ul class="space-y-3">';
  members.forEach(member => {
    html += `<li class="p-3 bg-gray-50 rounded-lg cursor-pointer hover:bg-blue-50" onclick="showMemberDetail(${member.id})"><strong>${member.full_name}</strong> (${member.gender}, ${member.birth_date}${member.death_date ? ' - ' + member.death_date : ''})</li>`;
  });
  html += '</ul></div>';
  document.getElementById('tree-container').innerHTML = html;
}

// Show member detail in form panel
function showMemberDetail(memberId) {
  // Fetch member data from API
  fetch(`api/member.php?id=${memberId}`)
    .then(res => res.json())
    .then(data => {
      if (data.success && data.member) {
        window.currentMember = data.member;
        loadPanel('components/member-detail.html', () => {
          // Fill in the member data
          const m = data.member;
          document.getElementById('detail-full-name').textContent = m.full_name || '';
          document.getElementById('detail-gender').textContent = m.gender || '';
          document.getElementById('detail-birth-date').textContent = m.birth_date || '';
          document.getElementById('detail-death-date').textContent = m.death_date || '-';
          document.getElementById('detail-notes').textContent = m.notes || '-';
          document.getElementById('detail-email').textContent = m.email || '-';
          document.getElementById('detail-phone').textContent = m.phone || '-';
          document.getElementById('detail-bio-notes').textContent = m.bio_notes || '-';
          // Tab logic
          window.showDetailTab = function(tab) {
            document.querySelectorAll('.tab').forEach(el => {
              el.classList.remove('border-b-2', 'border-blue-600', 'text-blue-600');
              el.classList.add('text-gray-600');
            });
            document.querySelector('.tab[onclick*="' + tab + '"]').classList.add('border-b-2', 'border-blue-600', 'text-blue-600');
            document.querySelector('.tab[onclick*="' + tab + '"]').classList.remove('text-gray-600');
            document.querySelectorAll('.tab-content').forEach(el => el.classList.add('hidden'));
            document.getElementById(tab).classList.remove('hidden');
          };
          window.showDetailTab('personal');
          window.uploadPhoto = function() { alert('Photo upload feature coming soon!'); };
          window.editMemberDetail = function() { showAddMemberForm(m); };
        });
      } else {
        alert('Member not found.');
      }
    })
    .catch(() => alert('Failed to load member details.'));
}

// Add parents for a member
function addParents(childId, childName) {
  console.log('addParents called with childId:', childId, 'childName:', childName);
  const body = JSON.stringify({ child_id: childId });
  console.log('Request body:', body);
  fetch('api/member.php?action=add_parents', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body
  })
    .then(res => res.json())
    .then(data => {
      if (data.success) {
        fetchAndDisplayMembers();
        loadPanel('components/parent-form.html', () => {
          document.getElementById('parent-form-title').textContent = data.mother.full_name;
          document.querySelectorAll('#parent-form-title-short, #parent-form-title-short-2, #parent-form-title-short-3, #parent-form-title-short-4, #parent-form-title-short-5').forEach(el => el.textContent = data.mother.full_name);
          document.getElementById('parent-full-name').textContent = data.mother.full_name;
          document.getElementById('parent-gender').textContent = 'Female';
        });
      } else {
        alert(data.error || 'Failed to add parents.');
      }
    })
    .catch(() => alert('Failed to add parents.'));
} 