<?php
session_start();
include '../db_connect.php';
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}

$db = getDB();

// Fetch announcements
$announcements = $db->query("SELECT * FROM announcements ORDER BY date_created DESC LIMIT 3")->fetch_all(MYSQLI_ASSOC);

// Fetch category counts
$categories = ['Fiction', 'Non-Fiction', 'Science', 'History', 'Technology'];
$catCounts = [];
foreach ($categories as $cat) {
    $stmt = $db->prepare("SELECT COUNT(*) as cnt FROM books WHERE category=?");
    $stmt->bind_param('s', $cat);
    $stmt->execute();
    $catCounts[$cat] = $stmt->get_result()->fetch_assoc()['cnt'];
}

// Fetch featured books (with images)
$featuredBooks = $db->query("SELECT * FROM books WHERE is_featured = 1 ORDER BY id DESC LIMIT 15")->fetch_all(MYSQLI_ASSOC);

// Get user initials
$fullname = $_SESSION['fullname'] ?? 'User';
$nameParts = explode(' ', trim($fullname));
$initials = strtoupper(substr($nameParts[0], 0, 1) . (isset($nameParts[1]) ? substr($nameParts[1], 0, 1) : ''));

$db->close();

// Requested success toast
$showToast = isset($_GET['requested']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Holy Trinity University Library</title>
    <meta name="description" content="Holy Trinity University Library - Your gateway to knowledge and discovery. Browse thousands of books, request borrowing, and explore our collections.">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="stylesheet" href="../css/student/home.css">
</head>
<body>

<?php if ($showToast): ?>
<div class="toast" id="successToast">
    <svg width="18" height="18" viewBox="0 0 24 24" fill="white"><path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/></svg>
    Book request submitted successfully!
</div>
<script>setTimeout(() => { const t = document.getElementById('successToast'); if(t) t.style.display='none'; }, 3500);</script>
<?php endif; ?>

<!-- NAVBAR -->
<?php include 'navbar.php'; ?>

<!-- HERO -->
<section class="hero">
    <div class="hero-overlay"></div>
    <div class="hero-content">
        <span class="hero-badge">Welcome to your learning sanctuary</span>
        <h2>Discover Knowledge,<br>Inspire Curiosity</h2>
        <p>Your gateway to over 3,000 carefully curated books, journals,<br>
           and resources. A space designed for exploration,<br>
           learning, and academic excellence.</p>
        <a href="category.php" class="btn-hero">Browse Books</a>
    </div>
</section>

<!-- INFO CARDS -->
<section class="info-section">
    <div class="info-card">
        <h3>Our Mission</h3>
        <p><strong>To foster a love for reading and provide resources that support academic achievement and personal growth for all students.</strong></p>
    </div>
    <div class="info-card">
        <h3>What We Offer</h3>
        <p><strong>Extensive collection of books, quiet study spaces, research assistance, and digital resources to support your educational journey.</strong></p>
    </div>
    <div class="info-card">
        <h3>Join Our Community</h3>
        <p><strong>Connect with fellow readers, participate in book clubs, and attend workshops designed to enhance your learning experience.</strong></p>
    </div>
</section>

<!-- CATEGORIES -->
<section class="section-wrap alt">
    <div class="section-header">
        <h2>Explore by Category</h2>
        <p>Browse our diverse collection across multiple genres</p>
    </div>
    <div class="category-grid">
        <?php
        $catIcons = [
            'Fiction' => '../images/fiction.png',
            'Non-Fiction' => '../images/non-fiction.png',
            'Science' => '../images/science.png',
            'History' => '../images/history.png',
            'Technology' => '../images/technology.png',
        ];
        foreach ($categories as $cat):
            $icon = $catIcons[$cat] ?? '';
            $count = $catCounts[$cat] ?? 0;
            $slug = urlencode($cat);
        ?>
        <a href="category.php?genre=<?= $slug ?>" class="category-card">
            <?php if ($icon): ?><img src="<?= htmlspecialchars($icon) ?>" alt="<?= htmlspecialchars($cat) ?>"><?php endif; ?>
            <h4><?= htmlspecialchars($cat) ?></h4>
            <p><?= $count ?> books</p>
        </a>
        <?php endforeach; ?>
    </div>
</section>

<!-- FEATURED BOOKS -->
<section class="section-wrap alt" style="padding-top: 0;">
    <div class="section-header">
        <h2>Featured Books</h2>
        <p>Handpicked recommendations from our librarians</p>
    </div>
    <div class="featured-slider-container" style="position: relative; display: flex; align-items: center; padding: 0 10px;">
        <!-- Left Button -->
        <button id="slide-left" class="slide-btn" style="left: -22px;" aria-label="Slide Left">&#10094;</button>
        
        <div class="books-grid" id="featured-books-grid" style="width: 100%;">
            <?php foreach ($featuredBooks as $book): ?>
            <a href="book_detail.php?id=<?= $book['id'] ?>" class="book-card">
                <img src="../<?= htmlspecialchars($book['image_path']) ?>" alt="<?= htmlspecialchars($book['title']) ?>">
                <div class="book-meta">
                    <h5><?= htmlspecialchars($book['title']) ?></h5>
                    <p><?= htmlspecialchars($book['author']) ?></p>
                </div>
            </a>
            <?php endforeach; ?>
        </div>

        <!-- Right Button -->
        <button id="slide-right" class="slide-btn" style="right: -22px;" aria-label="Slide Right">&#10095;</button>
    </div>
</section>

<!-- SCHEDULE & GUIDELINES -->
<section class="split-section">
    <div class="panel-card">
        <div class="panel-title">
            <div class="panel-dot dot-red"></div>
            <h3>Library Schedule</h3>
        </div>
        <div class="schedule-row"><span class="day">Monday – Friday</span><span class="time">8:00 AM – 5:00 PM</span></div>
        <div class="schedule-row"><span class="day">Saturday</span><span class="time">8:00 AM – 5:00 PM</span></div>
        <div class="schedule-row"><span class="day">Sunday</span><span class="closed">Closed</span></div>
    </div>
    <div class="panel-card">
        <div class="panel-title">
            <div class="panel-dot dot-green"></div>
            <h3>Library Guidelines</h3>
        </div>
        <ul class="guidelines-list">
            <li>Handle all books with care and respect</li>
            <li>Return books on or before the due date</li>
            <li>Maximum 3 books per student at a time</li>
            <li>Maintain silence in reading areas</li>
            <li>No food or drinks near books</li>
            <li>Report any damaged books immediately</li>
        </ul>
    </div>
</section>

<!-- ANNOUNCEMENTS -->
<section class="section-wrap alt">
    <div class="section-header">
        <h2>Announcements</h2>
    </div>
    <div class="announce-grid">
        <?php if (empty($announcements)): ?>
        <p style="text-align:center;color:#aaa;width:100%;">No announcements yet.</p>
        <?php else: ?>
        <?php foreach ($announcements as $ann): ?>
        <div class="announce-card">
            <span class="date"><?= date('M d, Y', strtotime($ann['date_created'])) ?></span>
            <h4><?= htmlspecialchars($ann['title']) ?></h4>
            <p><?= htmlspecialchars($ann['content']) ?></p>
        </div>
        <?php endforeach; ?>
        <?php endif; ?>
    </div>
</section>

<?php include 'footer.php'; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const grid = document.getElementById('featured-books-grid');
    const leftBtn = document.getElementById('slide-left');
    const rightBtn = document.getElementById('slide-right');

    if (grid && leftBtn && rightBtn) {
        // Scroll exactly by 208px (190px card width + 18px gap)
        leftBtn.addEventListener('click', function() {
            grid.scrollBy({ left: -208, behavior: 'smooth' });
        });
        rightBtn.addEventListener('click', function() {
            grid.scrollBy({ left: 208, behavior: 'smooth' });
        });
    }
});
</script>

</body>
</html>
