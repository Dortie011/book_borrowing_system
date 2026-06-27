<?php
session_start();
include '../db_connect.php';
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'student') {
    header('Location: ../login.php');
    exit;
}

$db = getDB();
$user_id = $_SESSION['user_id'];

// Handle profile update
$success_msg = '';
$error_msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $fullname = trim($_POST['fullname'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $contact  = trim($_POST['contact'] ?? '');
    $address  = trim($_POST['address'] ?? '');

    if ($fullname === '' || $email === '') {
        $error_msg = 'Full Name and Email are required.';
    } else {
        $stmt = $db->prepare("UPDATE users SET fullname=?, email=?, contact=?, address=? WHERE id=?");
        $stmt->bind_param("ssssi", $fullname, $email, $contact, $address, $user_id);
        if ($stmt->execute()) {
            $success_msg = 'Profile updated successfully!';
        } else {
            $error_msg = 'Failed to update profile. Please try again.';
        }
        $stmt->close();
    }
}

// Fetch user info
$stmt = $db->prepare("SELECT * FROM users WHERE id=?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$user) {
    session_destroy();
    header('Location: ../login.php');
    exit;
}

// Initials (up to 2)
$initials = '';
$name_parts = explode(' ', trim($user['fullname']));
foreach ($name_parts as $part) {
    if ($part !== '') $initials .= strtoupper($part[0]);
    if (strlen($initials) >= 2) break;
}
if ($initials === '') $initials = '?';

// Format dates
function format_date_long($date_str) {
    if (!$date_str) return 'N/A';
    $ts = strtotime($date_str);
    return $ts ? date('F d, Y', $ts) : 'N/A';
}
function format_date_month_year($date_str) {
    if (!$date_str) return 'N/A';
    $ts = strtotime($date_str);
    return $ts ? date('F Y', $ts) : 'N/A';
}

// Borrowed books
$stmt2 = $db->prepare("SELECT r.*, b.title, b.author, b.category FROM requests r JOIN books b ON r.book_id = b.id WHERE r.user_id=? ORDER BY r.request_date DESC");
$stmt2->bind_param("i", $user_id);
$stmt2->execute();
$borrowed_result = $stmt2->get_result();
$borrowed_books = [];
while ($row = $borrowed_result->fetch_assoc()) {
    $borrowed_books[] = $row;
}
$stmt2->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title>My Profile – HTU Library</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet"/>
<link rel="stylesheet" href="../css/student/profile.css">
</head>
<body>

<?php include 'navbar.php'; ?>

<!-- MAIN CONTENT -->
<div class="page-wrapper">

  <?php if ($success_msg): ?>
    <div class="alert alert-success"><?= htmlspecialchars($success_msg) ?></div>
  <?php endif; ?>
  <?php if ($error_msg): ?>
    <div class="alert alert-error"><?= htmlspecialchars($error_msg) ?></div>
  <?php endif; ?>

  <!-- PROFILE HEADER CARD -->
  <div class="profile-header">
    <div class="avatar-circle"><?= htmlspecialchars($initials) ?></div>
    <div class="profile-header-info">
      <h2><?= htmlspecialchars($user['fullname']) ?></h2>
      <p class="student-id"><?= htmlspecialchars($user['student_id'] ?? $_SESSION['student_id'] ?? '') ?></p>
      <span class="role-badge">Student</span>
    </div>
    <button class="btn-edit" onclick="openModal()">&#9998; Edit Profile</button>
  </div>

  <!-- PERSONAL INFORMATION CARD -->
  <div class="info-card">
    <h3>Personal Information</h3>
    <div class="info-grid">
      <div class="info-item">
        <div class="label">Student ID</div>
        <div class="value"><?= htmlspecialchars($user['student_id'] ?? 'N/A') ?></div>
      </div>
      <div class="info-item">
        <div class="label">Full Name</div>
        <div class="value"><?= htmlspecialchars($user['fullname'] ?? 'N/A') ?></div>
      </div>
      <div class="info-item">
        <div class="label">Gender</div>
        <div class="value"><?= htmlspecialchars($user['gender'] ?? 'N/A') ?></div>
      </div>
      <div class="info-item">
        <div class="label">Email Address</div>
        <div class="value"><?= htmlspecialchars($user['email'] ?? 'N/A') ?></div>
      </div>
      <div class="info-item">
        <div class="label">Contact Number</div>
        <div class="value"><?= htmlspecialchars($user['contact'] ?? 'N/A') ?></div>
      </div>
      <div class="info-item">
        <div class="label">Date of Birth</div>
        <div class="value"><?= format_date_long($user['birth_date'] ?? null) ?></div>
      </div>
      <div class="info-item">
        <div class="label">Address</div>
        <div class="value"><?= htmlspecialchars($user['address'] ?? 'N/A') ?></div>
      </div>
      <div class="info-item">
        <div class="label">Member Since</div>
        <div class="value"><?= format_date_month_year($user['created_at'] ?? null) ?></div>
      </div>
    </div>
  </div>

  <!-- BORROWED BOOKS CARD -->
  <div class="info-card">
    <h3>My Borrowed Books</h3>
    <?php if (empty($borrowed_books)): ?>
      <div class="empty-state">No borrowing history yet.</div>
    <?php else: ?>
      <div class="books-table-wrapper">
        <table>
          <thead>
            <tr>
              <th>Book Title</th>
              <th>Author</th>
              <th>Category</th>
              <th>Borrow Date</th>
              <th>Return Date</th>
              <th>Status</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($borrowed_books as $book): ?>
            <tr>
              <td><?= htmlspecialchars($book['title']) ?></td>
              <td><?= htmlspecialchars($book['author']) ?></td>
              <td><?= htmlspecialchars($book['category']) ?></td>
              <td><?= $book['borrow_date'] ? date('M d, Y', strtotime($book['borrow_date'])) : 'N/A' ?></td>
              <td><?= $book['return_date'] ? date('M d, Y', strtotime($book['return_date'])) : '—' ?></td>
              <td>
                <?php
                  $st = strtolower($book['status'] ?? 'pending');
                  $badge_class = 'badge-pending';
                  if ($st === 'approved')  $badge_class = 'badge-approved';
                  elseif ($st === 'rejected') $badge_class = 'badge-rejected';
                  elseif ($st === 'returned')  $badge_class = 'badge-returned';
                ?>
                <span class="badge <?= $badge_class ?>"><?= ucfirst(htmlspecialchars($book['status'])) ?></span>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </div>

  <!-- LOGOUT LINK -->
  <div class="logout-row">
    <a href="../logout.php" data-confirm="Are you sure you want to log out?">&#8594; Sign Out</a>
  </div>

</div>

<!-- EDIT PROFILE MODAL -->
<div class="modal-overlay" id="editModal">
  <div class="modal-card">
    <button class="modal-close" onclick="closeModal()" title="Close">&times;</button>
    <h3>Edit Profile</h3>
    <form method="POST" action="profile.php">
      <div class="form-group">
        <label for="fullname">Full Name</label>
        <input type="text" id="fullname" name="fullname" value="<?= htmlspecialchars($user['fullname'] ?? '') ?>" required/>
      </div>
      <div class="form-group">
        <label for="email">Email Address</label>
        <input type="email" id="email" name="email" value="<?= htmlspecialchars($user['email'] ?? '') ?>" required/>
      </div>
      <div class="form-group">
        <label for="contact">Contact Number</label>
        <input type="text" id="contact" name="contact" value="<?= htmlspecialchars($user['contact'] ?? '') ?>"/>
      </div>
      <div class="form-group">
        <label for="address">Address</label>
        <textarea id="address" name="address"><?= htmlspecialchars($user['address'] ?? '') ?></textarea>
      </div>
      <div class="modal-actions">
        <button type="submit" name="update_profile" class="btn-save">Save Changes</button>
        <button type="button" class="btn-cancel" onclick="closeModal()">Cancel</button>
      </div>
    </form>
  </div>
</div>

<script>
  function openModal()  { document.getElementById('editModal').classList.add('active'); }
  function closeModal() { document.getElementById('editModal').classList.remove('active'); }
  // Close modal when clicking overlay background
  document.getElementById('editModal').addEventListener('click', function(e) {
    if (e.target === this) closeModal();
  });
  <?php if ($success_msg): ?>
  // Auto-hide success alert
  setTimeout(function() {
    var el = document.querySelector('.alert-success');
    if (el) el.style.display = 'none';
  }, 3500);
  <?php endif; ?>
</script>
<?php include 'footer.php'; ?>
</body>
</html>
