// --- Elements ---
const assignmentForm = document.getElementById('assignment-form');
const formTitle = document.getElementById('form-title');
const saveBtn = document.getElementById('save-assignment-btn');
const cancelBtn = document.getElementById('cancel-edit-btn');
const assignmentsTableBody = document.querySelector('#assignments-table tbody');

const titleInput = document.getElementById('assignment-title');
const descriptionInput = document.getElementById('assignment-description');
const dateInput = document.getElementById('assignment-due-date');
const filesInput = document.getElementById('assignment-files');
const idInput = document.getElementById('assignment-id');

let isEditing = false;

// --- Functions ---

/**
 * Loads all assignments and renders them in the table.
 */
async function loadAssignments() {
  try {
    const response = await fetch('api/index.php?resource=assignments');
    if (!response.ok) throw new Error('Failed to fetch assignments');
    const assignments = await response.json();

    assignmentsTableBody.innerHTML = '';
    assignments.forEach(assignment => {
      const row = document.createElement('tr');
      row.innerHTML = `
                <td>${assignment.title}</td>
                <td>${assignment.due_date}</td>
                <td>
                    <button class="outline contrast smaller" onclick="startEdit(${assignment.id})">Edit</button>
                    <button class="outline danger smaller" onclick="deleteAssignment(${assignment.id})">Delete</button>
                </td>
            `;
      assignmentsTableBody.appendChild(row);
    });

  } catch (error) {
    console.error('Error loading assignments:', error);
    assignmentsTableBody.innerHTML = '<tr><td colspan="3">Error loading assignments.</td></tr>';
  }
}

/**
 * Handles form submission (Create or Update).
 */
assignmentForm.addEventListener('submit', async (e) => {
  e.preventDefault();

  const data = {
    title: titleInput.value,
    description: descriptionInput.value,
    due_date: dateInput.value,
    files: filesInput.value.split('\n').filter(line => line.trim() !== '')
  };

  try {
    let url = 'api/index.php?resource=assignments';
    let method = 'POST';

    if (isEditing) {
      data.id = idInput.value;
      method = 'PUT';
    }

    const response = await fetch(url, {
      method: method,
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(data)
    });

    if (!response.ok) {
      const errorData = await response.json();
      throw new Error(errorData.message || 'Failed to save assignment');
    }

    // Reset form
    resetForm();
    loadAssignments();

  } catch (error) {
    alert('Error: ' + error.message);
    console.error(error);
  }
});

/**
 * Prepares the form for editing an assignment.
 */
window.startEdit = async function (id) {
  try {
    const response = await fetch(`api/index.php?resource=assignments&id=${id}`);
    if (!response.ok) throw new Error('Failed to fetch assignment details');
    const assignment = await response.json();

    titleInput.value = assignment.title;
    descriptionInput.value = assignment.description;
    dateInput.value = assignment.due_date;

    let files = assignment.files;
    if (typeof files === 'string') {
      try { files = JSON.parse(files); } catch (e) { files = []; }
    }
    if (Array.isArray(files)) {
      filesInput.value = files.join('\n');
    } else {
      filesInput.value = '';
    }

    idInput.value = assignment.id;

    isEditing = true;
    formTitle.textContent = 'Edit Assignment';
    saveBtn.textContent = 'Update Assignment';
    cancelBtn.style.display = 'inline-block';

    // Scroll to form
    assignmentForm.scrollIntoView({ behavior: 'smooth' });

  } catch (error) {
    console.error('Error fetching assignment for edit:', error);
    alert('Could not load assignment for editing.');
  }
};

/**
 * Deletes an assignment.
 */
window.deleteAssignment = async function (id) {
  if (!confirm('Are you sure you want to delete this assignment?')) return;

  try {
    const response = await fetch(`api/index.php?resource=assignments&id=${id}`, {
      method: 'DELETE'
    });

    if (!response.ok) throw new Error('Failed to delete assignment');

    loadAssignments();

  } catch (error) {
    alert('Failed to delete assignment');
    console.error(error);
  }
};

/**
 * Resets the form to "Add Mode".
 */
function resetForm() {
  assignmentForm.reset();
  isEditing = false;
  idInput.value = '';
  formTitle.textContent = 'Add a New Assignment';
  saveBtn.textContent = 'Add Assignment';
  cancelBtn.style.display = 'none';
}

cancelBtn.addEventListener('click', resetForm);

// --- Initial Load ---
loadAssignments();
