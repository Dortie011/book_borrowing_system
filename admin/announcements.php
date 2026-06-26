<?php
session_start();
include '../db_connect.php';
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header('Location: ../login.php');
    exit;
}

$db         = getDB();
$action_msg = '';

// ── Handle POST actions ──────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        $title        = trim($_POST['title']   ?? '');
        $content      = trim($_POST['content'] ?? '');
        $date_created = trim($_POST['date']    ?? date('Y-m-d'));

        if ($title && $content) {
            $stmt = $db->prepare("INSERT INTO announcements (title, content, date_created) VALUES (?,?,?)");
            $stmt->bind_param("sss", $title, $content, $date_created);
            $stmt->execute();
            $stmt->close();
            header('Location: announcements.php?posted=1');
            exit;
        }
    }

    if ($action === 'edit') {
        $edit_id      = (int)($_POST['edit_id'] ?? 0);
        $title        = trim($_POST['title']    ?? '');
        $content      = trim($_POST['content']  ?? '');
        $date_created = trim($_POST['date']     ?? date('Y-m-d'));

        if ($edit_id && $title && $content) {
            $stmt = $db->prepare("UPDATE announcements SET title=?, content=?, date_created=? WHERE id=?");
            $stmt->bind_param("sssi", $title, $content, $date_created, $edit_id);
            $stmt->execute();
            $stmt->close();
            header('Location: announcements.php?updated=1');
            exit;
        }
    }

    if ($action === 'delete') {
        $del_id = (int)($_POST['id'] ?? 0);
        if ($del_id > 0) {
            $stmt = $db->prepare("DELETE FROM announcements WHERE id=?");
            $stmt->bind_param("i", $del_id);
            $stmt->execute();
            $stmt->close();
            header('Location: announcements.php?deleted=1');
            exit;
        }
    }
}

// ── Fetch all announcements ─────────────────────────
$announcements = $db->query(
    "SELECT * FROM announcements ORDER BY date_created DESC, id DESC"
)->fetch_all(MYSQLI_ASSOC);

$admin_name = $_SESSION['name'] ?? 'Admin';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HTU Library – Announcements</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../css/admin/announcements.css">
</head>
<body>

<!-- ═══ NAVBAR ═══ -->
<?php include 'navbar.php'; ?>

    <!-- MAIN -->
    <main class="main-content-area">

        <!-- Header -->
        <div class="page-header">
            <h2>Announcements</h2>
            <button class="btn-new-announcement" onclick="openModal()">
                <svg viewBox="0 0 24 24" style="width:18px;height:18px;fill:#fff;"><path d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z"/></svg>
                New Announcement
            </button>
        </div>

        <!-- List -->
        <div class="announcements-list">
            <?php if (empty($announcements)): ?>
                <div class="empty-state">
                    <svg viewBox="0 0 24 24"><path d="M20 2H4c-1.1 0-2 .9-2 2v18l4-4h14c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2zm0 14H5.17L4 17.17V4h16v12z"/></svg>
                    <p>No announcements yet. Click <strong>+ New Announcement</strong> to add one.</p>
                </div>
            <?php else: ?>
                <?php foreach ($announcements as $ann): ?>
                <div class="announcement-card">
                    <div class="accent-bar"></div>
                    <div class="announcement-body">
                        <span class="announcement-date">
                            <?= date('F j, Y', strtotime($ann['date_created'])) ?>
                        </span>
                        <h4 class="announcement-title"><?= htmlspecialchars($ann['title']) ?></h4>
                        <p class="announcement-content"><?= nl2br(htmlspecialchars($ann['content'])) ?></p>
                    </div>
                    <div class="announcement-actions">
                        <!-- Edit button: passes data to JS -->
                        <button type="button" class="btn-outline edit"
                            data-id="<?= $ann['id'] ?>"
                            data-title="<?= htmlspecialchars($ann['title'], ENT_QUOTES) ?>"
                            data-content="<?= htmlspecialchars($ann['content'], ENT_QUOTES) ?>"
                            data-date="<?= htmlspecialchars($ann['date_created']) ?>"
                            onclick="openEditModal(this)">
                            <svg viewBox="0 0 24 24"><path d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75L3 17.25zm17.71-10.21a1 1 0 000-1.41l-2.34-2.34a1 1 0 00-1.41 0l-1.83 1.83 3.75 3.75 1.83-1.83z"/></svg>
                            Edit
                        </button>
                        <!-- Delete form -->
                        <form method="POST" action="announcements.php"
                              data-confirm="Delete this announcement?">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id"     value="<?= $ann['id'] ?>">
                            <button type="submit" class="btn-outline del">
                                <svg viewBox="0 0 24 24"><path d="M6 19c0 1.1.9 2 2 2h8c1.1 0 2-.9 2-2V7H6v12zM19 4h-3.5l-1-1h-5l-1 1H5v2h14V4z"/></svg>
                                Delete
                            </button>
                        </form>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div><!-- /announcements-list -->

    </main>
</div>

<!-- ════════════════════════════════════
     MODAL (New / Edit Announcement)
════════════════════════════════════ -->
<div class="modal-overlay" id="modal-overlay" onclick="handleOverlayClick(event)">
    <div class="modal-box" id="modal-box">
        <div class="modal-header">
            <h3 id="modal-heading">New Announcement</h3>
            <button class="btn-close-modal" onclick="closeModal()" title="Close">✕</button>
        </div>

        <form method="POST" action="announcements.php" id="announcement-form">
            <input type="hidden" name="action"  id="form-action"  value="add">
            <input type="hidden" name="edit_id" id="form-edit-id" value="0">

            <div class="modal-fields">

                <div class="field-group">
                    <label>Title <span style="color:#e74c3c">*</span></label>
                    <input type="text" name="title" id="field-title"
                           placeholder="Announcement title" required>
                </div>

                <div class="field-group">
                    <label>Date <span style="color:#e74c3c">*</span></label>
                    <input type="date" name="date" id="field-date"
                           value="<?= date('Y-m-d') ?>" required>
                </div>

                <div class="field-group">
                    <label>Content <span style="color:#e74c3c">*</span></label>
                    <textarea name="content" id="field-content" rows="6"
                              placeholder="Write your announcement here…" required></textarea>
                </div>

                <button type="submit" class="btn-submit-modal" id="modal-submit-btn">
                    Post Announcement
                </button>

            </div>
        </form>
    </div>
</div>

<!-- Toast notifications -->
<?php if (isset($_GET['posted'])): ?>
    <div class="toast" id="toast">✔ Announcement posted successfully!</div>
<?php elseif (isset($_GET['updated'])): ?>
    <div class="toast" id="toast">✔ Announcement updated successfully!</div>
<?php elseif (isset($_GET['deleted'])): ?>
    <div class="toast red" id="toast">🗑 Announcement deleted.</div>
<?php endif; ?>

<script>
    // ─── Modal helpers ───────────────────────────────────
    const overlay     = document.getElementById('modal-overlay');
    const heading     = document.getElementById('modal-heading');
    const formAction  = document.getElementById('form-action');
    const formEditId  = document.getElementById('form-edit-id');
    const fieldTitle  = document.getElementById('field-title');
    const fieldDate   = document.getElementById('field-date');
    const fieldContent= document.getElementById('field-content');
    const submitBtn   = document.getElementById('modal-submit-btn');

    function openModal() {
        heading.textContent    = 'New Announcement';
        formAction.value       = 'add';
        formEditId.value       = '0';
        fieldTitle.value       = '';
        fieldDate.value        = new Date().toISOString().slice(0, 10);
        fieldContent.value     = '';
        submitBtn.textContent  = 'Post Announcement';
        overlay.classList.add('active');
        fieldTitle.focus();
    }

    function openEditModal(btn) {
        const id      = btn.dataset.id;
        const title   = btn.dataset.title;
        const content = btn.dataset.content;
        const date    = btn.dataset.date;

        heading.textContent    = 'Edit Announcement';
        formAction.value       = 'edit';
        formEditId.value       = id;
        fieldTitle.value       = title;
        fieldDate.value        = date;
        fieldContent.value     = content;
        submitBtn.textContent  = 'Save Changes';
        overlay.classList.add('active');
        fieldTitle.focus();
    }

    function closeModal() {
        overlay.classList.remove('active');
    }

    function handleOverlayClick(e) {
        if (e.target === overlay) closeModal();
    }

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') closeModal();
    });

    // ─── Auto-dismiss toast ──────────────────────────────
    const toast = document.getElementById('toast');
    if (toast) {
        setTimeout(() => {
            toast.style.transition = 'opacity 0.4s';
            toast.style.opacity = '0';
            setTimeout(() => toast.remove(), 420);
        }, 3500);
    }
</script>
</body>
</html>
