const assignmentId = new URLSearchParams(window.location.search).get('id');
const detailsArticle = document.getElementById('assignment-details');
const commentsContainer = document.getElementById('comments-container');
const commentForm = document.getElementById('comment-form');

if (!assignmentId) {
  document.querySelector('main').innerHTML = '<article><h2>Error</h2><p>No assignment ID specified.</p><a href="list.html">Return to List</a></article>';
} else {
  loadAssignmentDetails();
  loadComments();
}

async function loadAssignmentDetails() {
  try {
    const response = await fetch(`api/index.php?resource=assignments&id=${assignmentId}`);
    if (!response.ok) throw new Error('Failed to fetch details');
    const assignment = await response.json();

    document.getElementById('assignment-title').textContent = assignment.title;
    document.getElementById('assignment-due-date').textContent = assignment.due_date;
    document.getElementById('assignment-description').innerHTML = `<p>${assignment.description}</p>`;

    const filesList = document.getElementById('assignment-files');
    filesList.innerHTML = '';
    if (assignment.files && assignment.files.length > 0) {
      let files = assignment.files;
      if (typeof files === 'string') {
        try { files = JSON.parse(files); } catch (e) { files = []; }
      }

      if (Array.isArray(files)) {
        files.forEach(file => {
          const li = document.createElement('li');
          const a = document.createElement('a');
          a.href = file;
          a.textContent = file;
          a.target = "_blank";
          li.appendChild(a);
          filesList.appendChild(li);
        });
      }
    } else {
      filesList.innerHTML = '<li>No files attached.</li>';
    }

  } catch (error) {
    console.error('Error:', error);
    detailsArticle.innerHTML = '<p>Error loading assignment details.</p>';
  }
}

async function loadComments() {
  try {
    const response = await fetch(`api/index.php?resource=comments&assignment_id=${assignmentId}`);
    if (!response.ok) throw new Error('Failed to fetch comments');
    const comments = await response.json();

    commentsContainer.innerHTML = '';
    if (comments.length === 0) {
      commentsContainer.innerHTML = '<p>No comments yet. Be the first to ask!</p>';
      return;
    }

    comments.forEach(comment => {
      const card = document.createElement('div');
      card.className = 'comment-card';
      card.innerHTML = `
                <p>${comment.text}</p>
                <div class="comment-meta">Posted by: ${comment.author} on ${comment.created_at}</div> 
            `;
      commentsContainer.appendChild(card);
    });

  } catch (error) {
    console.error('Error:', error);
    commentsContainer.innerHTML = '<p>Error loading comments.</p>';
  }
}

commentForm.addEventListener('submit', async (e) => {
  e.preventDefault();
  const text = document.getElementById('new-comment').value;

  try {
    const response = await fetch('api/index.php?resource=comments', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        assignment_id: assignmentId,
        author: 'Current User',
        text: text
      })
    });

    if (!response.ok) throw new Error('Failed to post comment');

    // Clear form and reload comments
    commentForm.reset();
    loadComments();

  } catch (error) {
    alert('Failed to post comment');
    console.error(error);
  }
});
