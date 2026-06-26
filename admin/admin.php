<?php
session_start();
include '../db_connect.php';
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header('Location: ../login.php');
    exit;
}

$db = getDB();

// Metrics
$totalBooksResult     = $db->query("SELECT SUM(quantity) FROM books");
$totalBooks           = $totalBooksResult ? ($totalBooksResult->fetch_row()[0] ?? 0) : 0;

$activeStudentsResult = $db->query("SELECT COUNT(*) FROM users WHERE role='student'");
$activeStudents       = $activeStudentsResult ? ($activeStudentsResult->fetch_row()[0] ?? 0) : 0;

$pendingReqsResult    = $db->query("SELECT COUNT(*) FROM requests WHERE status='Pending'");
$pendingReqs          = $pendingReqsResult ? ($pendingReqsResult->fetch_row()[0] ?? 0) : 0;

$booksBorrowedResult  = $db->query("SELECT COUNT(*) FROM requests WHERE status='Approved'");
$booksBorrowed        = $booksBorrowedResult ? ($booksBorrowedResult->fetch_row()[0] ?? 0) : 0;

// Recent Requests
$recentStmt = $db->query(
    "SELECT r.id, u.fullname, u.student_id, b.title, r.request_date, r.status
     FROM requests r
     JOIN users u ON r.user_id = u.id
     JOIN books b ON r.book_id = b.id
     ORDER BY r.request_date DESC
     LIMIT 5"
);
$recentRequests = $recentStmt ? $recentStmt->fetch_all(MYSQLI_ASSOC) : [];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title>Admin Dashboard - HTU Library</title>
<link rel="preconnect" href="https://fonts.googleapis.com"/>
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin/>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet"/>
<link rel="stylesheet" href="../css/admin/admin.css">
</head>
<body>

<?php include 'navbar.php'; ?>

  <main class="main-content">
    <div class="page-header">
      <h2>Dashboard Overview</h2>
      <p>Welcome back! Here's what's happening today.</p>
    </div>

    <div class="metrics-grid">

      <!-- Total Books -->
      <div class="metric-card">
        <div class="metric-icon" style="background:#fde8e8;">
          <svg viewBox="0 0 24 24" fill="none">
            <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20" stroke="#be3835" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            <path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z" stroke="#be3835" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
          </svg>
        </div>
        <div class="metric-info">
          <div class="metric-number"><?= number_format((int)$totalBooks) ?></div>
          <div class="metric-label">Total Books</div>
          <div class="metric-sub">+120 this month</div>
        </div>
      </div>

      <!-- Active Students -->
      <div class="metric-card">
        <div class="metric-icon" style="background:#e8f5e9;">
          <svg viewBox="0 0 24 24" fill="none">
            <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2" stroke="#2e7d32" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            <circle cx="9" cy="7" r="4" stroke="#2e7d32" stroke-width="2"/>
            <path d="M23 21v-2a4 4 0 0 0-3-3.87" stroke="#2e7d32" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            <path d="M16 3.13a4 4 0 0 1 0 7.75" stroke="#2e7d32" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
          </svg>
        </div>
        <div class="metric-info">
          <div class="metric-number"><?= number_format((int)$activeStudents) ?></div>
          <div class="metric-label">Active Students</div>
          <div class="metric-sub">+45 this week</div>
        </div>
      </div>

      <!-- Pending Requests -->
      <div class="metric-card">
        <div class="metric-icon" style="background:#fffde7;">
          <svg viewBox="0 0 24 24" fill="none">
            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z" stroke="#f9a825" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            <polyline points="14 2 14 8 20 8" stroke="#f9a825" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            <line x1="16" y1="13" x2="8" y2="13" stroke="#f9a825" stroke-width="2" stroke-linecap="round"/>
            <line x1="16" y1="17" x2="8" y2="17" stroke="#f9a825" stroke-width="2" stroke-linecap="round"/>
          </svg>
        </div>
        <div class="metric-info">
          <div class="metric-number"><?= number_format((int)$pendingReqs) ?></div>
          <div class="metric-label">Pending Requests</div>
          <div class="metric-sub" style="color:#f9a825;">5 today</div>
        </div>
      </div>

      <!-- Books Borrowed -->
      <div class="metric-card">
        <div class="metric-icon" style="background:#e3f2fd;">
          <svg viewBox="0 0 24 24" fill="none">
            <polyline points="22 12 18 12 15 21 9 3 6 12 2 12" stroke="#1565c0" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
          </svg>
        </div>
        <div class="metric-info">
          <div class="metric-number"><?= number_format((int)$booksBorrowed) ?></div>
          <div class="metric-label">Books Borrowed</div>
          <div class="metric-sub" style="color:#1565c0;">+12% from last month</div>
        </div>
      </div>

    </div>

    <!-- RECENT REQUESTS -->
    <div class="recent-card">
      <h3>Recent Book Requests</h3>
      <div class="table-responsive">
        <table class="requests-table">
          <thead>
            <tr>
              <th>Request ID</th>
              <th>Student</th>
              <th>Student ID</th>
              <th>Book Title</th>
              <th>Date</th>
              <th>Status</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($recentRequests)): ?>
            <tr>
              <td colspan="6" style="text-align:center;color:#aaa;padding:30px 0;font-size:14px;">No requests found.</td>
            </tr>
            <?php else: ?>
            <?php foreach ($recentRequests as $req):
              $badgeClass = 'badge-pending';
              if ($req['status'] === 'Approved') $badgeClass = 'badge-approved';
              elseif ($req['status'] === 'Rejected') $badgeClass = 'badge-rejected';
              $shortId = strtoupper(substr((string)$req['id'], -6));
              $dateFormatted = date('M d, Y', strtotime($req['request_date']));
            ?>
            <tr>
              <td><span class="req-id">#<?= htmlspecialchars($shortId) ?></span></td>
              <td><?= htmlspecialchars($req['fullname']) ?></td>
              <td><?= htmlspecialchars($req['student_id']) ?></td>
              <td><?= htmlspecialchars($req['title']) ?></td>
              <td><?= $dateFormatted ?></td>
              <td><span class="badge <?= $badgeClass ?>"><?= htmlspecialchars($req['status']) ?></span></td>
            </tr>
            <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>

  </main>

<?php include 'footer.php'; ?>
