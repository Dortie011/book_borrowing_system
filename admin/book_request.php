<?php
session_start();
include '../db_connect.php';
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header('Location: ../login.php');
    exit;
}

$db = getDB();

// ── Handle POST actions ──────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $id     = (int)($_POST['id'] ?? 0);

    if ($id > 0) {
        if ($action === 'approve') {
            // Get the book_id first
            $stmt = $db->prepare("SELECT book_id FROM requests WHERE id = ?");
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $row = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            $upd = $db->prepare("UPDATE requests SET status='Approved' WHERE id = ?");
            $upd->bind_param('i', $id);
            $upd->execute();
            $upd->close();

            if ($row) {
                $decr = $db->prepare("UPDATE books SET available = available - 1 WHERE id = ? AND available > 0");
                $decr->bind_param('i', $row['book_id']);
                $decr->execute();
                $decr->close();
            }
        } elseif ($action === 'reject') {
            $upd = $db->prepare("UPDATE requests SET status='Rejected' WHERE id = ?");
            $upd->bind_param('i', $id);
            $upd->execute();
            $upd->close();
        }
    }
    // Redirect to avoid form re-submission
    header('Location: book_request.php' . (isset($_GET['filter']) ? '?filter='.$_GET['filter'] : ''));
    exit;
}

// ── Metrics ──────────────────────────────────────────────────────────────────
$totalReqsResult    = $db->query("SELECT COUNT(*) FROM requests");
$totalReqs          = $totalReqsResult ? ($totalReqsResult->fetch_row()[0] ?? 0) : 0;

$pendingCountResult = $db->query("SELECT COUNT(*) FROM requests WHERE status='Pending'");
$pendingCount       = $pendingCountResult ? ($pendingCountResult->fetch_row()[0] ?? 0) : 0;

$approvedCountResult= $db->query("SELECT COUNT(*) FROM requests WHERE status='Approved'");
$approvedCount      = $approvedCountResult ? ($approvedCountResult->fetch_row()[0] ?? 0) : 0;

$rejectedCountResult= $db->query("SELECT COUNT(*) FROM requests WHERE status='Rejected'");
$rejectedCount      = $rejectedCountResult ? ($rejectedCountResult->fetch_row()[0] ?? 0) : 0;

// ── Filter & Search ───────────────────────────────────────────────────────────
$filter = $_GET['filter'] ?? 'All';
$search = trim($_GET['search'] ?? '');
$allowedFilters = ['All', 'Pending', 'Approved', 'Rejected'];
if (!in_array($filter, $allowedFilters)) $filter = 'All';

$sql    = "SELECT r.*, u.fullname, u.student_id AS sid, b.title AS book_title
           FROM requests r
           JOIN users u ON r.user_id = u.id
           JOIN books b ON r.book_id = b.id
           WHERE 1=1";
$params = [];

if ($filter !== 'All') {
    $sql .= " AND r.status = ?";
    $params[] = $filter;
}
if ($search !== '') {
    $sql .= " AND (u.fullname LIKE ? OR b.title LIKE ?)";
    $params[] = '%' . $search . '%';
    $params[] = '%' . $search . '%';
}
$sql .= " ORDER BY r.request_date DESC";

$stmt = $db->prepare($sql);
if (count($params) > 0) {
    $types = str_repeat('s', count($params));
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$requestsResult = $stmt->get_result();
$requests = $requestsResult ? $requestsResult->fetch_all(MYSQLI_ASSOC) : [];
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title>Book Requests – HTU Library Admin</title>
<link rel="preconnect" href="https://fonts.googleapis.com"/>
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin/>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet"/>
<link rel="stylesheet" href="../css/admin/book_request.css">
</head>
<body>

<?php include 'navbar.php'; ?>

  <main class="main-content">
    <div class="page-header">
      <h2>Book Requests</h2>
      <p>Review, approve, or reject student borrow requests.</p>
    </div>

    <div class="mini-metrics">
      <div class="mini-card total">
        <div class="mini-card-number"><?= number_format((int)$totalReqs) ?></div>
        <div class="mini-card-label">Total Requests</div>
      </div>
      <div class="mini-card pending">
        <div class="mini-card-number"><?= number_format((int)$pendingCount) ?></div>
        <div class="mini-card-label">Pending</div>
      </div>
      <div class="mini-card approved">
        <div class="mini-card-number"><?= number_format((int)$approvedCount) ?></div>
        <div class="mini-card-label">Approved</div>
      </div>
      <div class="mini-card rejected">
        <div class="mini-card-number"><?= number_format((int)$rejectedCount) ?></div>
        <div class="mini-card-label">Rejected</div>
      </div>
    </div>

    <div class="filter-bar">
      <form method="GET" action="book_request.php">
        <input type="hidden" name="filter" value="<?= htmlspecialchars($filter) ?>"/>
        <input
          type="text"
          name="search"
          class="search-input"
          placeholder="Search by student name or book title..."
          value="<?= htmlspecialchars($search) ?>"
        />
        <button type="submit" class="search-btn">Search</button>
      </form>
      <div class="filter-pills">
        <?php foreach (['All', 'Pending', 'Approved', 'Rejected'] as $f): ?>
        <a href="book_request.php?filter=<?= $f ?><?= $search ? '&search='.urlencode($search) : '' ?>"
           class="<?= $filter === $f ? 'active' : '' ?>"><?= $f ?></a>
        <?php endforeach; ?>
      </div>
    </div>

    <div class="table-card">
      <div class="table-card-header">
        <h3>All Requests</h3>
        <span class="result-count"><?= count($requests) ?> result<?= count($requests) !== 1 ? 's' : '' ?></span>
      </div>

      <?php if (empty($requests)): ?>
      <div class="empty-state">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
          <circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
        </svg>
        <p>No requests found<?= $search ? ' for "<strong>'.htmlspecialchars($search).'</strong>"' : '' ?>.</p>
      </div>
      <?php else: ?>
      <div class="table-responsive">
        <table class="req-table">
          <thead>
            <tr>
              <th>Request ID</th>
              <th>Student</th>
              <th>Student ID</th>
              <th>Book Title</th>
              <th>Borrow Date</th>
              <th>Return Date</th>
              <th>Status</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($requests as $req):
              $isPending  = $req['status'] === 'Pending';
              $badgeClass = 'badge-pending';
              if ($req['status'] === 'Approved') $badgeClass = 'badge-approved';
              elseif ($req['status'] === 'Rejected') $badgeClass = 'badge-rejected';
              $shortId      = strtoupper(substr((string)$req['id'], -6));
              $borrowDate   = $req['borrow_date'] ? date('M d, Y', strtotime($req['borrow_date'])) : '—';
              $returnDate   = !empty($req['return_date']) ? date('M d, Y', strtotime($req['return_date'])) : '—';
              $modalId      = 'modal-' . $req['id'];
            ?>
            <tr>
              <td><span class="req-id">#<?= htmlspecialchars($shortId) ?></span></td>
              <td><?= htmlspecialchars($req['fullname']) ?></td>
              <td style="font-size:12.5px;color:#666;"><?= htmlspecialchars($req['sid']) ?></td>
              <td style="font-weight:600;"><?= htmlspecialchars($req['book_title']) ?></td>
              <td><?= $borrowDate ?></td>
              <td><?= $returnDate ?></td>
              <td><span class="badge <?= $badgeClass ?>"><?= htmlspecialchars($req['status']) ?></span></td>
              <td>
                <div class="actions">
                  <button type="button" class="btn-action btn-view"
                          onclick="openModal('<?= $modalId ?>')"
                          title="View details">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                      <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                      <circle cx="12" cy="12" r="3"/>
                    </svg>
                  </button>

                  <form method="POST" style="display:inline;">
                    <input type="hidden" name="action" value="approve"/>
                    <input type="hidden" name="id" value="<?= $req['id'] ?>"/>
                    <button type="submit" class="btn-action btn-approve"
                            <?= !$isPending ? 'disabled' : '' ?>
                            title="Approve"
                            <?= $isPending ? 'onclick="return confirm(\'Approve this request?\')"' : '' ?>>
                      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="20 6 9 17 4 12"/>
                      </svg>
                    </button>
                  </form>

                  <form method="POST" style="display:inline;">
                    <input type="hidden" name="action" value="reject"/>
                    <input type="hidden" name="id" value="<?= $req['id'] ?>"/>
                    <button type="submit" class="btn-action btn-reject"
                            <?= !$isPending ? 'disabled' : '' ?>
                            title="Reject"
                            <?= $isPending ? 'onclick="return confirm(\'Reject this request?\')"' : '' ?>>
                      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
                      </svg>
                    </button>
                  </form>
                </div>

                <div class="modal-overlay" id="<?= $modalId ?>">
                  <div class="modal">
                    <button class="modal-close" onclick="closeModal('<?= $modalId ?>')">&times;</button>
                    <h3>Request Details</h3>
                    <div class="modal-row">
                      <span class="label">Request ID</span>
                      <span class="value" style="font-family:monospace;">#<?= htmlspecialchars($shortId) ?></span>
                    </div>
                    <div class="modal-row">
                      <span class="label">Student Name</span>
                      <span class="value"><?= htmlspecialchars($req['fullname']) ?></span>
                    </div>
                    <div class="modal-row">
                      <span class="label">Student ID</span>
                      <span class="value"><?= htmlspecialchars($req['sid']) ?></span>
                    </div>
                    <div class="modal-row">
                      <span class="label">Book Title</span>
                      <span class="value"><?= htmlspecialchars($req['book_title']) ?></span>
                    </div>
                    <div class="modal-row">
                      <span class="label">Borrow Date</span>
                      <span class="value"><?= $borrowDate ?></span>
                    </div>
                    <div class="modal-row">
                      <span class="label">Return Date</span>
                      <span class="value"><?= $returnDate ?></span>
                    </div>
                    <div class="modal-row">
                      <span class="label">Status</span>
                      <span class="value"><span class="badge <?= $badgeClass ?>"><?= htmlspecialchars($req['status']) ?></span></span>
                    </div>
                  </div>
                </div>

              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <?php endif; ?>
    </div>

  </main>
<script>
  function openModal(id) {
    document.getElementById(id).classList.add('open');
  }
  function closeModal(id) {
    document.getElementById(id).classList.remove('open');
  }
  // Close on backdrop click
  document.querySelectorAll('.modal-overlay').forEach(function(overlay) {
    overlay.addEventListener('click', function(e) {
      if (e.target === overlay) overlay.classList.remove('open');
    });
  });
</script>
<?php include 'footer.php'; ?>