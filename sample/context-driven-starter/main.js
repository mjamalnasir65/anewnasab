
window.currentNodeContext = {};

function onNodeClick(member) {
  window.currentNodeContext = {
    target_member_id: member.id,
    target_full_name: member.full_name
  };
  console.log('[DEBUG] Node context set:', window.currentNodeContext);
}

function startAddParent() {
  window.currentNodeContext.relationship_type = 'parent';
  document.getElementById('form-title').innerText = "Add Parent";
  document.getElementById('form-panel').style.display = 'block';
}

function submitAddMember(event) {
  event.preventDefault();

  const data = {
    full_name: document.getElementById('full_name').value,
    gender: document.getElementById('gender').value,
    birth_date: document.getElementById('birth_date').value,
    context: window.currentNodeContext
  };

  console.log('[DEBUG] Sending:', data);

  fetch('add_member.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(data)
  })
  .then(res => res.json())
  .then(result => {
    if (result.success) {
      alert('Member added!');
    } else {
      alert('Failed: ' + result.error);
    }
  })
  .catch(err => alert('Error: ' + err));
}
