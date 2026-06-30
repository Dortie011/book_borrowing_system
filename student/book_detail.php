<?php
session_start();
include '../db_connect.php';
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}

$db = getDB();
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0) {
    header('Location: home.php');
    exit;
}

$stmt = $db->prepare("SELECT * FROM books WHERE id=?");
$stmt->bind_param("i", $id);
$stmt->execute();
$book = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$book) {
    header('Location: home.php');
    exit;
}

$available_copies = (int)($book['available'] ?? 0);
$total_copies     = (int)($book['quantity'] ?? 0);
$is_available     = ($available_copies > 0);
$cover_url      = !empty($book['image_path']) ? '../' . htmlspecialchars($book['image_path']) : '';
$title_letter   = strtoupper(mb_substr($book['title'], 0, 1));
$category       = $book['category'] ?? 'General';

// Format year published
$year_published = $book['year_published'] ?? $book['published_year'] ?? ($book['publish_year'] ?? 'N/A');

// Description fallback
$description = $book['description'] ?? 'No description available for this book.';

// ISBN / Publisher
$isbn      = $book['isbn']      ?? 'N/A';
$publisher = $book['publisher'] ?? 'N/A';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title><?= htmlspecialchars($book['title']) ?> – HTU Library</title>
<link rel="stylesheet" href="../css/student/book_detail.css">
</head>
<body>

<?php include 'navbar.php'; ?>

<!-- BREADCRUMB -->
<div class="breadcrumb">
  <a href="home.php">Home</a>
  <span class="sep">›</span>
  <a href="category.php?genre=<?= urlencode($category) ?>"><?= htmlspecialchars($category) ?></a>
  <span class="sep">›</span>
  <span class="current"><?= htmlspecialchars($book['title']) ?></span>
</div>

<!-- MAIN -->
<div class="page-content">

  <!-- ====== LEFT COLUMN ====== -->
  <div class="book-left">
    <?php if ($cover_url): ?>
      <img src="<?= $cover_url ?>" alt="<?= htmlspecialchars($book['title']) ?>" class="book-cover-img"/>
    <?php else: ?>
      <div class="book-cover-placeholder-lg"><?= htmlspecialchars($title_letter) ?></div>
    <?php endif; ?>

    <span class="avail-badge-large <?= $is_available ? 'available' : 'unavailable' ?>">
      <?= $is_available ? '✓ Available' : '✗ Not Available' ?>
    </span>

    <p class="copies-info">
      Available copies: <strong><?= $available_copies ?></strong> of <strong><?= $total_copies ?></strong>
    </p>

    <a href="request.php?book_id=<?= (int)$book['id'] ?>" class="btn-request">
      &#43; Request this Book
    </a>

    <a href="category.php?genre=<?= urlencode($category) ?>" class="link-back">
      &#8592; Back to <?= htmlspecialchars($category) ?>
    </a>
  </div>

  <!-- ====== RIGHT COLUMN ====== -->
  <div class="book-right">

    <div>
      <span class="category-pill"><?= htmlspecialchars($category) ?></span>
      <h2 class="book-title"><?= htmlspecialchars($book['title']) ?></h2>
      <p class="book-author-label">by <?= htmlspecialchars($book['author']) ?></p>
    </div>

    <hr class="divider"/>

    <!-- About -->
    <div>
      <h3 class="section-heading">About this Book</h3>
      <p class="book-description"><?= nl2br(htmlspecialchars($description)) ?></p>
    </div>

    <!-- Details Table -->
    <div>
      <h3 class="section-heading">Book Details</h3>
      <table class="details-table">
        <tr>
          <td>ISBN</td>
          <td><?= htmlspecialchars($isbn) ?></td>
        </tr>
        <tr>
          <td>Publisher</td>
          <td><?= htmlspecialchars($publisher) ?></td>
        </tr>
        <tr>
          <td>Year Published</td>
          <td><?= htmlspecialchars((string)$year_published) ?></td>
        </tr>
        <tr>
          <td>Category</td>
          <td><?= htmlspecialchars($category) ?></td>
        </tr>
        <tr>
          <td>Status</td>
          <td>
            <span style="color: <?= $is_available ? '#155724' : '#721c24' ?>; font-weight: 600;">
              <?= $is_available ? 'Available' : 'Not Available' ?>
            </span>
          </td>
        </tr>
      </table>
    </div>

    <!-- Availability info box -->
    <?php if ($is_available): ?>
      <div class="info-box green">
        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="#155724" viewBox="0 0 16 16">
          <path d="M8 16A8 8 0 1 0 8 0a8 8 0 0 0 0 16zm3.97-9.03a.75.75 0 0 0-1.08-1.04L7.25 10.6 5.53 8.88a.75.75 0 0 0-1.06 1.06l2.25 2.25a.75.75 0 0 0 1.07-.02l4.18-4.5z"/>
        </svg>
        <span>This book is <strong>available</strong> for borrowing. Click the button on the left to request it.</span>
      </div>
    <?php else: ?>
      <div class="info-box red">
        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="#721c24" viewBox="0 0 16 16">
          <path d="M8 16A8 8 0 1 0 8 0a8 8 0 0 0 0 16zm-.75-4.75a.75.75 0 0 1 1.5 0v.5a.75.75 0 0 1-1.5 0v-.5zm0-6.5a.75.75 0 0 1 1.5 0v4a.75.75 0 0 1-1.5 0v-4z"/>
        </svg>
        <span>This book is <strong>currently not available</strong>. You can still request it and you will be notified when it becomes available.</span>
      </div>
    <?php endif; ?>

  </div><!-- end book-right -->

</div><!-- end page-content -->

<?php include 'footer.php'; ?>
</body>
</html>
