<?php
// Calculate initials dynamically for student profile badge
$navbar_fullname = $_SESSION['fullname'] ?? 'User';
$navbar_nameParts = explode(' ', trim($navbar_fullname));
$navbar_initials = strtoupper(substr($navbar_nameParts[0], 0, 1) . (isset($navbar_nameParts[1]) ? substr($navbar_nameParts[1], 0, 1) : ''));
$current_page = basename($_SERVER['PHP_SELF']);
?>
<link rel="stylesheet" href="../css/student/navbar.css">

<header class="navbar">
    <div class="nav-logo">
        <button class="mobile-menu-toggle" id="mobileMenuBtn" aria-label="Toggle Menu">
            <svg viewBox="0 0 24 24"><path d="M3 18h18v-2H3v2zm0-5h18v-2H3v2zm0-7v2h18V6H3z"/></svg>
        </button>
        <a href="home.php" style="display: flex; align-items: center; gap: 14px; text-decoration: none;">
            <img src="../images/htulogo.png" alt="HTU Logo">
            <div class="logo-text">
                <h1>Holy Trinity University Library</h1>
                <p>Knowledge &amp; Discovery</p>
            </div>
        </a>
    </div>
    <div class="nav-actions">
        <a href="home.php" class="nav-btn btn-home <?= ($current_page === 'home.php') ? 'active' : '' ?>">Home</a>
        <a href="category.php" class="nav-btn btn-books <?= ($current_page === 'category.php' || $current_page === 'book_detail.php') ? 'active' : '' ?>">Books</a>
        <a href="request.php" class="nav-btn btn-request <?= ($current_page === 'request.php') ? 'active' : '' ?>">Book Request</a>
        <a href="profile.php" class="btn-profile <?= ($current_page === 'profile.php') ? 'active' : '' ?>" title="My Profile"><?= htmlspecialchars($navbar_initials) ?></a>
        <a href="../logout.php" data-confirm="Are you sure you want to log out?" class="btn-logout">Logout</a>
    </div>
</header>

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
    // Mobile Hamburger
    const mobileMenuBtn = document.getElementById('mobileMenuBtn');
    const navActions = document.querySelector('.nav-actions');
    
    if (mobileMenuBtn && navActions) {
        mobileMenuBtn.addEventListener('click', function(e) {
            navActions.classList.toggle('show');
            e.stopPropagation();
        });
        
        // Close menu when clicking outside
        document.addEventListener('click', function(e) {
            if (window.innerWidth <= 768 && navActions.classList.contains('show')) {
                if (!navActions.contains(e.target) && e.target !== mobileMenuBtn) {
                    navActions.classList.remove('show');
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
            // If it is a submit button in a form that has data-confirm, let the form handler manage it
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
