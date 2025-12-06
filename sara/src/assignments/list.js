/*
  Requirement: Populate the "Course Assignments" list page.
  
  Instructions:
  1. Link this file to `list.html` using:
     <script src="list.js" defer></script>
*/

// --- Element Selections ---
const listSection = document.getElementById('assignment-list-section');

// --- Functions ---

/**
 * Creates an <article> element for an assignment.
 */
function createAssignmentArticle(assignment) {
  const article = document.createElement('article');
  article.className = 'assignment-card';

  const header = document.createElement('header');
  const h2 = document.createElement('h2');
  h2.textContent = assignment.title;
  header.appendChild(h2);

  const pDue = document.createElement('p');
  pDue.innerHTML = `<strong>Due:</strong> ${assignment.due_date}`;

  const pDesc = document.createElement('p');
  // Truncate description for the list view
  const shortDesc = assignment.description.length > 100
    ? assignment.description.substring(0, 100) + '...'
    : assignment.description;
  pDesc.textContent = shortDesc;

  const footer = document.createElement('footer');
  const a = document.createElement('a');
  a.href = `details.html?id=${assignment.id}`;
  a.setAttribute('role', 'button');
  a.className = 'contrast';
  a.textContent = 'View Details & Discussion';
  footer.appendChild(a);

  article.appendChild(header);
  article.appendChild(pDue);
  article.appendChild(pDesc);
  article.appendChild(footer);

  return article;
}

/**
 * Fetches and loads assignments from the API.
 */
async function loadAssignments() {
  try {
    const response = await fetch('api/index.php?resource=assignments');
    if (!response.ok) {
      throw new Error('Failed to fetch assignments');
    }
    const assignments = await response.json();

    // Clear loading or existing content
    listSection.innerHTML = '';

    if (assignments.length === 0) {
      listSection.innerHTML = '<p>No assignments found.</p>';
      return;
    }

    assignments.forEach(assignment => {
      const article = createAssignmentArticle(assignment);
      listSection.appendChild(article);
    });

  } catch (error) {
    console.error('Error loading assignments:', error);
    listSection.innerHTML = '<article><p>Error loading assignments. Please try again later.</p></article>';
  }
}

// --- Initial Page Load ---
loadAssignments();
