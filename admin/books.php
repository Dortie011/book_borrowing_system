<?php
session_start();
include '../db_connect.php';
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header('Location: ../login.php');
    exit;
}

$db = getDB();
$message = '';

// Handle DELETE
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete') {
    $del_id = (int)($_POST['id'] ?? 0);
    if ($del_id > 0) {
        $stmt = $db->prepare("DELETE FROM books WHERE id = ?");
        $stmt->bind_param("i", $del_id);
        $stmt->execute();
        $stmt->close();
        header('Location: books.php?deleted=1');
        exit;
    }
}

// Filters
$search = trim($_GET['search'] ?? '');
$cat    = $_GET['category'] ?? 'All';

// Build WHERE
$where  = [];
$params = [];
$types  = '';

if ($search !== '') {
    $where[]  = "(title LIKE ? OR author LIKE ? OR isbn LIKE ?)";
    $like     = '%' . $search . '%';
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
    $types   .= 'sss';
}

if ($cat !== 'All' && $cat !== '') {
    $where[]  = "category = ?";
    $params[] = $cat;
    $types   .= 's';
}

$where_sql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

// Fetch books
$sql   = "SELECT * FROM books $where_sql ORDER BY id DESC";
$stmt  = $db->prepare($sql);
if ($params) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$books_result = $stmt->get_result();
$books = $books_result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Metrics
$total_books     = $db->query("SELECT COALESCE(SUM(quantity), 0) AS v FROM books")->fetch_assoc()['v'];
$available_books = $db->query("SELECT COALESCE(SUM(available), 0) AS v FROM books")->fetch_assoc()['v'];
$borrowed_books  = $db->query("SELECT COALESCE(SUM(quantity - available), 0) AS v FROM books")->fetch_assoc()['v'];
$total_titles    = $db->query("SELECT COUNT(*) AS v FROM books")->fetch_assoc()['v'];

$admin_name = $_SESSION['name'] ?? 'Admin';

$categories_list = ['All', 'Fiction', 'Non-Fiction', 'Science', 'History', 'Technology'];

function categoryBadge($cat) {
    $map = [
        'Fiction'     => ['bg' => '#fde8e8', 'color' => '#c0392b'],
        'Non-Fiction' => ['bg' => '#e8f5e9', 'color' => '#27ae60'],
        'Science'     => ['bg' => '#e3f2fd', 'color' => '#1565c0'],
        'History'     => ['bg' => '#fff3e0', 'color' => '#e65100'],
        'Technology'  => ['bg' => '#f3e5f5', 'color' => '#7b1fa2'],
    ];
    $style = $map[$cat] ?? ['bg' => '#f0f0f0', 'color' => '#555'];
    return '<span style="background:' . $style['bg'] . ';color:' . $style['color'] . ';padding:3px 12px;border-radius:20px;font-size:11.5px;font-weight:600;">' . htmlspecialchars($cat) . '</span>';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HTU Library – Books Inventory</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../css/admin/books.css">
</head>
<body>

<!-- ═══ NAVBAR ═══ -->
<?php include 'navbar.php'; ?>

    <!-- MAIN -->
    <main class="main-content-area">

        <!-- Page Header -->
        <div class="page-header">
            <h2>Books Inventory</h2>
            <a href="add_book.php" class="btn-add-book">
                <svg viewBox="0 0 24 24" style="width:18px;height:18px;fill:#fff;"><path d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z"/></svg>
                Add New Book
            </a>
        </div>

        <!-- Metrics -->
        <div class="metrics-row">
            <div class="metric-card red">
                <div class="metric-icon red-bg">
                    <svg viewBox="0 0 24 24"><path d="M12 11.55C9.64 9.35 6.48 8 3 8v11c3.48 0 6.64 1.35 9 3.55 2.36-2.2 5.52-3.55 9-3.55V8c-3.48 0-6.64 1.35-9 3.55z"/></svg>
                </div>
                <div class="metric-info">
                    <div class="val"><?= number_format($total_books) ?></div>
                    <div class="label">Total Books</div>
                </div>
            </div>
            <div class="metric-card green">
                <div class="metric-icon green-bg">
                    <svg viewBox="0 0 24 24"><path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41L9 16.17z"/></svg>
                </div>
                <div class="metric-info">
                    <div class="val"><?= number_format($available_books) ?></div>
                    <div class="label">Available Books</div>
                </div>
            </div>
            <div class="metric-card blue">
                <div class="metric-icon blue-bg">
                    <svg viewBox="0 0 24 24"><path d="M18 2H6c-1.1 0-2 .9-2 2v16c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2zm-1 14H7v-2h10v2zm0-4H7v-2h10v2zm0-4H7V6h10v2z"/></svg>
                </div>
                <div class="metric-info">
                    <div class="val"><?= number_format($borrowed_books) ?></div>
                    <div class="label">Borrowed Books</div>
                </div>
            </div>
            <div class="metric-card purple">
                <div class="metric-icon purple-bg">
                    <svg viewBox="0 0 24 24"><path d="M4 6H2v14c0 1.1.9 2 2 2h14v-2H4V6zm16-4H8c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2zm0 14H8V4h12v12z"/></svg>
                </div>
                <div class="metric-info">
                    <div class="val"><?= number_format($total_titles) ?></div>
                    <div class="label">Total Titles</div>
                </div>
            </div>
        </div>

        <!-- Filter Bar -->
        <form method="GET" action="books.php">
            <div class="filter-bar">
                <input type="text" name="search" placeholder="Search by title, author or ISBN…" value="<?= htmlspecialchars($search) ?>">
                <div class="cat-pills">
                    <?php foreach ($categories_list as $c): ?>
                        <a href="?search=<?= urlencode($search) ?>&category=<?= urlencode($c) ?>"
                           class="cat-pill <?= ($cat === $c) ? 'active' : '' ?>">
                            <?= htmlspecialchars($c) ?>
                        </a>
                    <?php endforeach; ?>
                </div>
                <input type="hidden" name="category" value="<?= htmlspecialchars($cat) ?>">
                <button type="submit" class="btn-search">Search</button>
            </div>
        </form>

        <!-- Books Table -->
        <div class="table-card">
          <div class="table-responsive">
            <?php if (empty($books)): ?>
                <div class="empty-state">
                    <svg viewBox="0 0 24 24"><path d="M12 11.55C9.64 9.35 6.48 8 3 8v11c3.48 0 6.64 1.35 9 3.55 2.36-2.2 5.52-3.55 9-3.55V8c-3.48 0-6.64 1.35-9 3.55z"/></svg>
                    <p>No books found<?= ($search || $cat !== 'All') ? ' for your filters' : '' ?>.</p>
                </div>
            <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>Book ID</th>
                        <th>Title &amp; Author</th>
                        <th>Category</th>
                        <th>ISBN</th>
                        <th>Qty</th>
                        <th>Available</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($books as $b): ?>
                    <tr>
                        <td><span class="book-id">#<?= $b['id'] ?></span></td>
                        <td>
                            <span class="book-title"><?= htmlspecialchars($b['title']) ?></span>
                            <span class="book-author"><?= htmlspecialchars($b['author']) ?></span>
                        </td>
                        <td><?= categoryBadge($b['category'] ?? '') ?></td>
                        <td style="font-size:12.5px;color:#666;"><?= htmlspecialchars($b['isbn'] ?? '—') ?></td>
                        <td style="font-weight:700;text-align:center;"><?= (int)$b['quantity'] ?></td>
                        <td style="font-weight:700;text-align:center;"><?= (int)$b['available'] ?></td>
                        <td>
                            <?php if ((int)$b['available'] > 0): ?>
                                <span class="badge-status available">Available</span>
                            <?php else: ?>
                                <span class="badge-status out">Out of Stock</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="action-btns">
                                <a href="add_book.php?id=<?= $b['id'] ?>" class="btn-icon btn-edit" title="Edit">
                                    <svg viewBox="0 0 24 24"><path d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75L3 17.25zm17.71-10.21a1 1 0 000-1.41l-2.34-2.34a1 1 0 00-1.41 0l-1.83 1.83 3.75 3.75 1.83-1.83z"/></svg>
                                </a>
                                <form method="POST" action="books.php" style="display:inline;" data-confirm="Delete this book? This action cannot be undone.">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id"     value="<?= $b['id'] ?>">
                                    <button type="submit" class="btn-icon btn-del" title="Delete">
                                        <svg viewBox="0 0 24 24"><path d="M6 19c0 1.1.9 2 2 2h8c1.1 0 2-.9 2-2V7H6v12zM19 4h-3.5l-1-1h-5l-1 1H5v2h14V4z"/></svg>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
          </div>
        </div><!-- /table-card -->

    </main>
</div>

<?php if (isset($_GET['deleted'])): ?>
    <div class="toast" id="toast">Book deleted successfully.</div>
    <script>setTimeout(()=>{ document.getElementById('toast').style.opacity=0; }, 3000);</script>
<?php endif; ?>

</body>
</html>
