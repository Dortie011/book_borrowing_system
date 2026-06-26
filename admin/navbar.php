<?php
// Calculate current page for sidebar active states
$current_page = basename($_SERVER['PHP_SELF']);
$admin_name = $_SESSION['fullname'] ?? $_SESSION['name'] ?? 'Admin';
?>
<link rel="stylesheet" href="../css/admin/navbar.css">

<nav class="navbar">
  <div class="nav-logo">
    <button class="mobile-menu-toggle" id="mobileMenuBtn" aria-label="Toggle Menu">
      <svg viewBox="0 0 24 24"><path d="M3 18h18v-2H3v2zm0-5h18v-2H3v2zm0-7v2h18V6H3z"/></svg>
    </button>
    <img src="../images/htulogo.png" alt="HTU Logo" class="logo-img"/>
    <div class="logo-text">
      <h1>Holy Trinity University Library</h1>
      <p>Knowledge &amp; Discovery</p>
    </div>
  </div>
  <div class="nav-right">
    <span class="welcome-text">Welcome, <?= htmlspecialchars($admin_name) ?></span>
    <div class="admin-avatar">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
        <circle cx="12" cy="8" r="4"/>
        <path d="M4 20c0-4 3.6-7 8-7s8 3 8 7"/>
      </svg>
    </div>
    <a href="../logout.php" data-confirm="Are you sure you want to log out?" class="btn-logout">
      <svg viewBox="0 0 24 24"><path d="M17 7l-1.41 1.41L18.17 11H8v2h10.17l-2.58 2.58L17 17l5-5-5-5zM4 5h8V3H4c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h8v-2H4V5z"/></svg>
      <span>Logout</span>
    </a>
  </div>
</nav>

<!-- Custom Confirmation Modal -->
<div class="custom-confirm-overlay" id="customConfirmOverlay">
    <div class="custom-confirm-card" id="customConfirmCard">
        <div class="custom-confirm-icon">
            <svg viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-6h-2v-2h4v8zm0-10h-2V5h-2V7h4v2z"/></svg>
        </div>
        <h3 class="custom-confirm-title">Confirm Action</h3>
        <p class="custom-confirm-message" id="customConfirmMessage">Are you sure you want to proceed?</p>
        <div class="custom-confirm-actions">
            <button type="button" class="custom-confirm-btn btn-cancel" id="customConfirmCancel">Cancel</button>
            <button type="button" class="custom-confirm-btn btn-confirm" id="customConfirmOk">Yes, Proceed</button>
        </div>
    </div>
</div>

<div class="dashboard-layout">
  <aside class="sidebar-panel">
    <nav class="sidebar-nav">
      <a href="admin.php"         class="nav-item <?= $current_page === 'admin.php' ? 'active' : '' ?>">Dashboard Overview</a>
      <a href="book_request.php"  class="nav-item <?= $current_page === 'book_request.php' ? 'active' : '' ?>">Book Request</a>
      <a href="books.php"         class="nav-item <?= ($current_page === 'books.php' || $current_page === 'add_book.php') ? 'active' : '' ?>">Books</a>
      <a href="announcements.php" class="nav-item <?= $current_page === 'announcements.php' ? 'active' : '' ?>">Announcements</a>
    </nav>
  </aside>

<script>
let confirmCallback = null;

function showCustomConfirm(message, callback) {
    const overlay = document.getElementById('customConfirmOverlay');
    const msgEl = document.getElementById('customConfirmMessage');
    if (overlay && msgEl) {
        msgEl.textContent = message;
        overlay.classList.add('active');
        confirmCallback = callback;
    }
}

function closeCustomConfirm() {
    const overlay = document.getElementById('customConfirmOverlay');
    if (overlay) {
        overlay.classList.remove('active');
    }
    confirmCallback = null;
}

document.addEventListener('DOMContentLoaded', function() {
  const mobileMenuBtn = document.getElementById('mobileMenuBtn');
  const sidebarPanel = document.querySelector('.sidebar-panel');
  
  if(mobileMenuBtn && sidebarPanel) {
    mobileMenuBtn.addEventListener('click', function(e) {
      sidebarPanel.classList.toggle('show');
      e.stopPropagation();
    });
    
    // Close sidebar when clicking outside
    document.addEventListener('click', function(e) {
      if (window.innerWidth <= 768 && sidebarPanel.classList.contains('show')) {
        if (!sidebarPanel.contains(e.target) && e.target !== mobileMenuBtn) {
          sidebarPanel.classList.remove('show');
        }
      }
    });
  }

  // Intercept clicks on links/buttons with data-confirm
  document.addEventListener('click', function(e) {
      const target = e.target.closest('[data-confirm]');
      if (target) {
          // Ignore if the matched element is a FORM (since form submits are handled separately)
          if (target.tagName === 'FORM') {
              return;
          }
          const form = target.closest('form');
          if (form && form.hasAttribute('data-confirm') && target.type === 'submit') {
              return;
          }
          
          if (!target.dataset.confirmed) {
              e.preventDefault();
              showCustomConfirm(target.dataset.confirm, function() {
                  target.dataset.confirmed = "true";
                  if (target.tagName === 'A') {
                      window.location.href = target.href;
                  } else {
                      target.click();
                  }
              });
          }
      }
  });

  // Intercept form submissions
  document.addEventListener('submit', function(e) {
      const form = e.target;
      if (form.hasAttribute('data-confirm') && !form.dataset.confirmed) {
          e.preventDefault();
          showCustomConfirm(form.getAttribute('data-confirm'), function() {
              form.dataset.confirmed = "true";
              form.submit();
          });
      }
  });

  // Wire modal buttons
  const btnCancel = document.getElementById('customConfirmCancel');
  const btnOk = document.getElementById('customConfirmOk');
  if (btnCancel) btnCancel.addEventListener('click', closeCustomConfirm);
  if (btnOk) {
      btnOk.addEventListener('click', function() {
          if (confirmCallback) {
              confirmCallback();
          }
          closeCustomConfirm();
      });
  }

  // Escape/Enter keyboard bindings
  document.addEventListener('keydown', function(e) {
      const overlay = document.getElementById('customConfirmOverlay');
      if (overlay && overlay.classList.contains('active')) {
          if (e.key === 'Escape') {
              closeCustomConfirm();
          } else if (e.key === 'Enter') {
              e.preventDefault();
              if (confirmCallback) {
                  confirmCallback();
              }
              closeCustomConfirm();
          }
      }
  });
});
</script>
