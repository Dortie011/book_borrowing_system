<?php
session_start();
include '../db_connect.php';
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}

$db = getDB();

$genre  = $_GET['genre']  ?? 'All';
$search = $_GET['search'] ?? '';
$sort   = $_GET['sort']   ?? 'title_asc';

$order_by = ($sort === 'title_desc') ? 'b.title DESC' : 'b.title ASC';

// Build query dynamically
$params      = [];
$types       = '';
$conditions  = [];

if ($genre !== 'All' && $genre !== '') {
    $conditions[] = 'b.category = ?';
    $params[]     = $genre;
    $types       .= 's';
}
if ($search !== '') {
    $like = '%' . $search . '%';
    $conditions[] = '(b.title LIKE ? OR b.author LIKE ?)';
    $params[]  = $like;
    $params[]  = $like;
    $types    .= 'ss';
}

$where_clause = count($conditions) > 0 ? 'WHERE ' . implode(' AND ', $conditions) : '';
$sql = "SELECT * FROM books b $where_clause ORDER BY $order_by";

$stmt = $db->prepare($sql);
if (count($params) > 0) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();
$books  = [];
while ($row = $result->fetch_assoc()) {
    $books[] = $row;
}
$stmt->close();

$total_books = count($books);

$genre_labels = [
    'All'         => 'All Books',
    'Fiction'     => 'Fiction Books',
    'Non-Fiction' => 'Non-Fiction Books',
    'Science'     => 'Science Books',
    'History'     => 'History Books',
    'Technology'  => 'Technology Books',
];
$page_title = $genre_labels[$genre] ?? htmlspecialchars($genre) . ' Books';

$categories = ['All', 'Fiction', 'Non-Fiction', 'Science', 'History', 'Technology'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title><?= $page_title ?> – HTU Library</title>
<link rel="stylesheet" href="../css/student/category.css">
</head>
<body>

<?php include 'navbar.php'; ?>

<!-- PAGE HEADER -->
<div class="page-header">
  <h2><?= htmlspecialchars($page_title) ?></h2>
  <p><strong><?= number_format($total_books) ?></strong> book<?= $total_books !== 1 ? 's' : '' ?> found</p>
</div>

<!-- FILTER BAR -->
<form method="GET" action="category.php" id="filterForm">
  <div class="filter-bar">
    <!-- Search -->
    <div class="search-wrap">
      <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
        <path d="M11.742 10.344a6.5 6.5 0 1 0-1.397 1.398h-.001c.03.04.062.078.098.115l3.85 3.85a1 1 0 0 0 1.415-1.414l-3.85-3.85a1.007 1.007 0 0 0-.115-.099zm-5.242 1.1a5.5 5.5 0 1 1 0-11 5.5 5.5 0 0 1 0 11z"/>
      </svg>
      <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Search by title or author..." onchange="this.form.submit()"/>
    </div>

    <!-- Category Pills -->
    <div class="category-pills">
      <?php foreach ($categories as $cat): ?>
        <?php
          $pill_url = 'category.php?genre=' . urlencode($cat);
          if ($search !== '') $pill_url .= '&search=' . urlencode($search);
          if ($sort !== 'title_asc') $pill_url .= '&sort=' . urlencode($sort);
          $is_active = ($genre === $cat) || ($cat === 'All' && ($genre === '' || $genre === 'All'));
        ?>
        <a href="<?= $pill_url ?>" class="<?= $is_active ? 'pill-active' : '' ?>"><?= htmlspecialchars($cat) ?></a>
      <?php endforeach; ?>
    </div>

    <!-- Sort -->
    <select name="sort" class="sort-select" onchange="this.form.submit()">
      <option value="title_asc"  <?= $sort === 'title_asc'  ? 'selected' : '' ?>>Title A–Z</option>
      <option value="title_desc" <?= $sort === 'title_desc' ? 'selected' : '' ?>>Title Z–A</option>
    </select>

    <!-- Keep genre in form -->
    <input type="hidden" name="genre" value="<?= htmlspecialchars($genre) ?>"/>
  </div>
</form>

<!-- BOOKS GRID -->
<div class="books-section">
  <div class="books-grid">
    <?php if (empty($books)): ?>
      <div class="empty-state">
        <svg xmlns="http://www.w3.org/2000/svg" width="64" height="64" fill="#ccc" viewBox="0 0 16 16">
          <path d="M11.742 10.344a6.5 6.5 0 1 0-1.397 1.398h-.001c.03.04.062.078.098.115l3.85 3.85a1 1 0 0 0 1.415-1.414l-3.85-3.85a1.007 1.007 0 0 0-.115-.099zm-5.242 1.1a5.5 5.5 0 1 1 0-11 5.5 5.5 0 0 1 0 11z"/>
        </svg>
        <h4>No books found</h4>
        <p>Try adjusting your search or browse a different category.</p>
      </div>
    <?php else: ?>
      <?php foreach ($books as $book):
        $is_available = (strtolower($book['status'] ?? 'available') === 'available');
        $cover_url    = !empty($book['image_path']) ? '../' . htmlspecialchars($book['image_path']) : '';
        $title_letter = strtoupper(mb_substr($book['title'], 0, 1));
      ?>
      <a href="book_detail.php?id=<?= (int)$book['id'] ?>" class="book-card">
        <?php if ($cover_url): ?>
          <img src="<?= $cover_url ?>" alt="<?= htmlspecialchars($book['title']) ?>" class="book-cover" loading="lazy"/>
        <?php else: ?>
          <div class="book-cover-placeholder"><?= htmlspecialchars($title_letter) ?></div>
        <?php endif; ?>
        <div class="book-body">
          <h5><?= htmlspecialchars($book['title']) ?></h5>
          <p class="book-author"><?= htmlspecialchars($book['author']) ?></p>
          <span class="status-badge <?= $is_available ? 'status-available' : 'status-unavailable' ?>">
            <?= $is_available ? 'Available' : 'Not Available' ?>
          </span>
        </div>
      </a>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>
</div>

<?php include 'footer.php'; ?>
</body>
</html>
