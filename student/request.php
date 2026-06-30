<?php
session_start();
include '../db_connect.php';
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}

$db = getDB();
$user_id = $_SESSION['user_id'];
$error = '';
$success = false;

// Pre-fill from book_id GET param
$book_id = (int)($_GET['book_id'] ?? 0);
$prefill = null;
if ($book_id > 0) {
    $stmt = $db->prepare("SELECT * FROM books WHERE id=?");
    $stmt->bind_param('i', $book_id);
    $stmt->execute();
    $prefill = $stmt->get_result()->fetch_assoc();
}

// Handle POST submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $bid   = (int)($_POST['book_id'] ?? 0);
    $title = trim($_POST['book_title'] ?? '');
    $author = trim($_POST['author'] ?? '');
    $genre = trim($_POST['genre'] ?? '');
    $borrow_date = trim($_POST['borrow_date'] ?? '');
    $return_date = trim($_POST['return_date'] ?? '');
    $purpose = trim($_POST['purpose'] ?? '');

    // Resolve book
    if ($bid > 0) {
        $stmt = $db->prepare("SELECT * FROM books WHERE id=?");
        $stmt->bind_param('i', $bid);
        $stmt->execute();
        $bk = $stmt->get_result()->fetch_assoc();
    } else {
        $stmt = $db->prepare("SELECT * FROM books WHERE title=? OR (title LIKE ? AND author LIKE ?) LIMIT 1");
        $tl = "%$title%"; $al = "%$author%";
        $stmt->bind_param('sss', $title, $tl, $al);
        $stmt->execute();
        $bk = $stmt->get_result()->fetch_assoc();
    }

    $borrow_ts = strtotime($borrow_date);
    $return_ts = strtotime($return_date);

    if (!$bk) {
        $error = "Book not found. Please check the title and author.";
    } elseif (!$borrow_date || !$borrow_ts) {
        $error = "Please enter a valid borrow date.";
    } elseif (!$return_date || !$return_ts) {
        $error = "Please enter a valid return date.";
    } elseif ($return_ts < $borrow_ts) {
        $error = "Return date cannot be before borrow date.";
    } else {
        $ins = $db->prepare("INSERT INTO requests (user_id, book_id, borrow_date, return_date, purpose, status) VALUES (?,?,?,?,?,'Pending')");
        $ins->bind_param('iisss', $user_id, $bk['id'], $borrow_date, $return_date, $purpose);
        $ins->execute();
        header('Location: home.php?requested=1');
        exit;
    }
}

// Get user initials for navbar
$fullname = $_SESSION['fullname'] ?? 'User';
$nameParts = explode(' ', trim($fullname));
$initials = strtoupper(substr($nameParts[0], 0, 1) . (isset($nameParts[1]) ? substr($nameParts[1], 0, 1) : ''));
$db->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book Request – HTU Library</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../css/student/request.css">
</head>
<body>

<?php include 'navbar.php'; ?>

<main class="form-page">
    <div class="form-header">
        <h2>Book Request Form</h2>
        <p>Fill in the details below to request your desired book</p>
    </div>

    <div class="form-card">
        <?php if ($error): ?>
        <div class="alert-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST" action="request.php<?= $book_id ? '?book_id='.$book_id : '' ?>" data-confirm="Are you sure you want to submit this book request?">
            <input type="hidden" name="book_id" value="<?= $book_id ?: '' ?>">

            <div class="form-group">
                <label for="book-title">Book Title</label>
                <input type="text" id="book-title" name="book_title" placeholder="Enter book title" required
                    value="<?= htmlspecialchars($prefill['title'] ?? ($_POST['book_title'] ?? '')) ?>"
                    <?= $prefill ? 'readonly' : '' ?>>
            </div>

            <div class="form-group">
                <label for="author">Author</label>
                <input type="text" id="author" name="author" placeholder="Enter the author's name" required
                    value="<?= htmlspecialchars($prefill['author'] ?? ($_POST['author'] ?? '')) ?>"
                    <?= $prefill ? 'readonly' : '' ?>>
            </div>

            <div class="form-group">
                <label for="genre">Genre / Category</label>
                <?php if ($prefill): ?>
                    <input type="hidden" name="genre" value="<?= htmlspecialchars($prefill['category']) ?>">
                <?php endif; ?>
                <select id="genre" name="<?= $prefill ? 'genre_disabled' : 'genre' ?>" required <?= $prefill ? 'disabled' : '' ?>>
                    <option value="" disabled <?= !($prefill || ($_POST['genre'] ?? '')) ? 'selected' : '' ?>>Select Genre</option>
                    <?php foreach (['Fiction','Non-Fiction','Science','History','Technology'] as $g): ?>
                    <option value="<?= $g ?>" <?= (($prefill['category'] ?? ($_POST['genre'] ?? '')) === $g) ? 'selected' : '' ?>><?= $g ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="borrow_date">Preferred Borrow Date</label>
                <input type="date" id="borrow_date" name="borrow_date" required
                    value="<?= htmlspecialchars($_POST['borrow_date'] ?? '') ?>">
            </div>

            <div class="form-group">
                <label for="return_date">Preferred Return Date</label>
                <input type="date" id="return_date" name="return_date" required
                    value="<?= htmlspecialchars($_POST['return_date'] ?? '') ?>">
            </div>

            <div class="form-group">
                <label for="purpose">Purpose / Reason</label>
                <textarea id="purpose" name="purpose" rows="4" placeholder="Why do you need this book?"><?= htmlspecialchars($_POST['purpose'] ?? '') ?></textarea>
            </div>

            <button type="submit" class="btn-submit">Submit Book Request</button>
        </form>
        <a href="home.php" class="link-back">← Back to Home</a>
    </div>
</main>

<?php include 'footer.php'; ?>

</body>
</html>
