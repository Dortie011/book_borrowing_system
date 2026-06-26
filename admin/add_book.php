<?php
session_start();
include '../db_connect.php';
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header('Location: ../login.php');
    exit;
}

$db      = getDB();
$edit_id = (int)($_GET['id'] ?? 0);
$book    = [];

// Fetch existing book if editing
if ($edit_id > 0) {
    $stmt = $db->prepare("SELECT * FROM books WHERE id = ?");
    $stmt->bind_param("i", $edit_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $book   = $result->fetch_assoc() ?: [];
    $stmt->close();
    if (empty($book)) {
        header('Location: books.php');
        exit;
    }
}

$is_edit    = $edit_id > 0;
$page_title = $is_edit ? 'Edit Book' : 'Add New Book';
$error_msg  = '';

// Handle form POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title          = trim($_POST['title']          ?? '');
    $author         = trim($_POST['author']         ?? '');
    $category       = trim($_POST['category']       ?? '');
    $isbn           = trim($_POST['isbn']           ?? '');
    $publisher      = trim($_POST['publisher']      ?? '');
    $year_published = trim($_POST['year_published'] ?? '');
    $quantity       = (int)($_POST['quantity']      ?? 1);
    $available      = (int)($_POST['available']     ?? 0);
    $description    = trim($_POST['description']    ?? '');
    $is_featured    = isset($_POST['is_featured'])  ? 1 : 0;
    $pid            = (int)($_POST['edit_id']       ?? 0);

    if (!$title || !$author) {
        $error_msg = 'Title and Author are required.';
    } else {
        // Handle image upload
        $image_path = $book['image_path'] ?? '';
        if (!empty($_FILES['cover']['name'])) {
            $ext     = strtolower(pathinfo($_FILES['cover']['name'], PATHINFO_EXTENSION));
            $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            if (!in_array($ext, $allowed)) {
                $error_msg = 'Only image files (jpg, png, gif, webp) are allowed.';
            } else {
                $new_name   = 'book_' . time() . '_' . mt_rand(1000, 9999) . '.' . $ext;
                $upload_dir = __DIR__ . '/../images/';
                if (move_uploaded_file($_FILES['cover']['tmp_name'], $upload_dir . $new_name)) {
                    $image_path = 'images/' . $new_name;
                } else {
                    $error_msg = 'Failed to upload image. Check folder permissions.';
                }
            }
        }

        if (!$error_msg) {
            if ($pid > 0) {
                // UPDATE existing book
                $stmt = $db->prepare(
                    "UPDATE books
                     SET title=?, author=?, category=?, isbn=?,
                         quantity=?, available=?,
                         description=?, publisher=?, year_published=?,
                         image_path=?, is_featured=?
                     WHERE id=?"
                );
                $stmt->bind_param(
                    "ssssiissssii",
                    $title, $author, $category, $isbn,
                    $quantity, $available,
                    $description, $publisher, $year_published,
                    $image_path, $is_featured,
                    $pid
                );
                $stmt->execute();
                $stmt->close();
            } else {
                // INSERT new book
                $stmt = $db->prepare(
                    "INSERT INTO books
                         (title, author, category, isbn,
                          quantity, available,
                          description, publisher, year_published,
                          image_path, is_featured)
                     VALUES (?,?,?,?, ?,?, ?,?,?, ?,?)"
                );
                // s s s s  i i  s s s  s  i
                $stmt->bind_param(
                    "ssssiissssi",
                    $title, $author, $category, $isbn,
                    $quantity, $available,
                    $description, $publisher, $year_published,
                    $image_path, $is_featured
                );
                $stmt->execute();
                $stmt->close();
            }
            header('Location: books.php?saved=1');
            exit;
        }
    }
}

$admin_name = $_SESSION['fullname'] ?? $_SESSION['name'] ?? 'Admin';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HTU Library – <?= htmlspecialchars($page_title) ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../css/admin/add_book.css">
</head>
<body>

<?php include 'navbar.php'; ?>

    <main class="main-content-area">

        <a href="books.php" class="back-link">
            <svg viewBox="0 0 24 24" style="width:16px;height:16px;fill:#be3835;"><path d="M20 11H7.83l5.59-5.59L12 4l-8 8 8 8 1.41-1.41L7.83 13H20v-2z"/></svg>
            Back to Books
        </a>

        <h2 class="page-title"><?= htmlspecialchars($page_title) ?></h2>

        <?php if ($error_msg): ?>
            <div class="error-box"><?= htmlspecialchars($error_msg) ?></div>
        <?php endif; ?>

        <div class="form-card">
            <form method="POST" action="add_book.php<?= $is_edit ? '?id=' . $edit_id : '' ?>" enctype="multipart/form-data" data-confirm="Are you sure you want to save this book?">
                <input type="hidden" name="edit_id" value="<?= $is_edit ? $edit_id : 0 ?>">

                <div class="form-grid">

                    <!-- LEFT: Cover Upload -->
                    <div>
                        <div class="upload-box" id="upload-box" onclick="document.getElementById('cover-input').click()">
                            <?php if ($is_edit && !empty($book['image_path'])): ?>
                                <img src="../<?= htmlspecialchars($book['image_path']) ?>" alt="Cover" class="cover-preview" id="cover-preview">
                            <?php else: ?>
                                <svg viewBox="0 0 24 24" id="upload-icon"><path d="M9 3L7.17 5H4c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V7c0-1.1-.9-2-2-2h-3.17L15 3H9zm3 15c-2.76 0-5-2.24-5-5s2.24-5 5-5 5 2.24 5 5-2.24 5-5 5zm0-8c-1.65 0-3 1.35-3 3s1.35 3 3 3 3-1.35 3-3-1.35-3-3-3z"/></svg>
                                <p>Upload Book Cover</p>
                                <span class="upload-hint">Click to browse image</span>
                                <img src="" alt="" class="cover-preview" id="cover-preview" style="display:none;">
                            <?php endif; ?>
                        </div>
                        <input type="file" name="cover" id="cover-input" accept="image/*">
                    </div>

                    <!-- RIGHT: Fields -->
                    <div class="fields-col">

                        <div class="field-group">
                            <label>Book Title <span style="color:#e74c3c">*</span></label>
                            <input type="text" name="title" placeholder="Enter book title"
                                   value="<?= htmlspecialchars($book['title'] ?? '') ?>" required>
                        </div>

                        <div class="field-group">
                            <label>Author <span style="color:#e74c3c">*</span></label>
                            <input type="text" name="author" placeholder="Enter author name"
                                   value="<?= htmlspecialchars($book['author'] ?? '') ?>" required>
                        </div>

                        <div class="field-group">
                            <label>Category</label>
                            <select name="category">
                                <?php
                                $cats = ['Fiction', 'Non-Fiction', 'Science', 'History', 'Technology'];
                                foreach ($cats as $c):
                                    $sel = (($book['category'] ?? '') === $c) ? 'selected' : '';
                                ?>
                                    <option value="<?= $c ?>" <?= $sel ?>><?= $c ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="fields-row">
                            <div class="field-group">
                                <label>ISBN</label>
                                <input type="text" name="isbn" placeholder="e.g. 978-3-16-148410-0"
                                       value="<?= htmlspecialchars($book['isbn'] ?? '') ?>">
                            </div>
                            <div class="field-group">
                                <label>Publisher</label>
                                <input type="text" name="publisher" placeholder="Publisher name"
                                       value="<?= htmlspecialchars($book['publisher'] ?? '') ?>">
                            </div>
                        </div>

                        <div class="fields-row">
                            <div class="field-group">
                                <label>Year Published</label>
                                <input type="text" name="year_published" placeholder="YYYY"
                                       value="<?= htmlspecialchars($book['year_published'] ?? '') ?>">
                            </div>
                            <div class="field-group">
                                <label>Quantity</label>
                                <input type="number" name="quantity" min="1"
                                       value="<?= (int)($book['quantity'] ?? 1) ?>">
                            </div>
                        </div>

                        <div class="field-group">
                            <label>Available Copies</label>
                            <input type="number" name="available" min="0"
                                   value="<?= (int)($book['available'] ?? 0) ?>">
                        </div>

                        <div class="field-group">
                            <label>Description</label>
                            <textarea name="description" rows="4" placeholder="Brief description of the book…"><?= htmlspecialchars($book['description'] ?? '') ?></textarea>
                        </div>

                        <!-- ⭐ Featured Toggle -->
                        <div class="featured-toggle-wrap">
                            <label class="featured-toggle-label" for="is_featured">
                                <svg viewBox="0 0 24 24" style="width:20px;height:20px;fill:#f9a825;flex-shrink:0;">
                                    <path d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z"/>
                                </svg>
                                Feature this book
                                <span class="hint">(appears in Featured Books on homepage)</span>
                            </label>
                            <label class="toggle-switch">
                                <input type="checkbox" id="is_featured" name="is_featured" value="1"
                                       <?= !empty($book['is_featured']) ? 'checked' : '' ?>>
                                <span class="toggle-track"></span>
                            </label>
                        </div>

                        <button type="submit" class="btn-submit">
                            <?= $is_edit ? '💾 Save Changes' : '➕ Add Book' ?>
                        </button>

                    </div><!-- /fields-col -->

                </div><!-- /form-grid -->
            </form>
        </div><!-- /form-card -->

    </main>

<script>
    document.getElementById('cover-input').addEventListener('change', function () {
        const file = this.files[0];
        if (!file) return;
        const reader = new FileReader();
        reader.onload = (e) => {
            const preview = document.getElementById('cover-preview');
            const icon    = document.getElementById('upload-icon');
            preview.src = e.target.result;
            preview.style.display = 'block';
            if (icon) icon.style.display = 'none';
            document.querySelectorAll('#upload-box p, #upload-box .upload-hint')
                    .forEach(p => p.style.display = 'none');
        };
        reader.readAsDataURL(file);
    });
</script>

<?php include 'footer.php'; ?>
