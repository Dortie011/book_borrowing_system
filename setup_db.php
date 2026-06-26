<?php
// Setup script - run once to create tables and seed data
mysqli_report(MYSQLI_REPORT_OFF);
$conn = new mysqli('127.0.0.1', 'root', '');
if ($conn->connect_error) {
    $conn = new mysqli('127.0.0.1', 'root', 'root');
}
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

// Create database
$conn->query("CREATE DATABASE IF NOT EXISTS htu_library CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
$conn->select_db('htu_library');

// Users table
$conn->query("CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id VARCHAR(50) UNIQUE NOT NULL,
    fullname VARCHAR(255) NOT NULL,
    gender VARCHAR(10) NOT NULL DEFAULT 'Male',
    email VARCHAR(255) NOT NULL,
    contact VARCHAR(50) NOT NULL DEFAULT '',
    birth_date DATE NOT NULL DEFAULT '2000-01-01',
    address VARCHAR(500) NOT NULL DEFAULT '',
    password VARCHAR(255) NOT NULL,
    role VARCHAR(20) NOT NULL DEFAULT 'student',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

// Books table
$conn->query("CREATE TABLE IF NOT EXISTS books (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    author VARCHAR(255) NOT NULL,
    category VARCHAR(100) NOT NULL,
    isbn VARCHAR(50) NOT NULL DEFAULT '',
    quantity INT NOT NULL DEFAULT 1,
    available INT NOT NULL DEFAULT 1,
    status VARCHAR(50) NOT NULL DEFAULT 'Available',
    image_path VARCHAR(255) DEFAULT NULL,
    description TEXT DEFAULT NULL,
    publisher VARCHAR(255) DEFAULT NULL,
    year_published VARCHAR(10) DEFAULT NULL,
    is_featured TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

// Announcements table
$conn->query("CREATE TABLE IF NOT EXISTS announcements (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    content TEXT NOT NULL,
    date_created DATE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

// Requests table
$conn->query("CREATE TABLE IF NOT EXISTS requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    book_id INT NOT NULL,
    borrow_date DATE NOT NULL,
    return_date DATE NOT NULL,
    purpose TEXT DEFAULT NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'Pending',
    request_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (book_id) REFERENCES books(id) ON DELETE CASCADE
)");

// Seed admin account
$adminPwd = password_hash('1234', PASSWORD_DEFAULT);
$conn->query("INSERT IGNORE INTO users (student_id, fullname, gender, email, contact, birth_date, address, password, role) 
    VALUES ('admin', 'Administrator', 'Male', 'admin@htu.edu.ph', '09001234567', '1990-01-01', 'Holy Trinity University', '$adminPwd', 'admin')");

// Seed student account
$stuPwd = password_hash('1111', PASSWORD_DEFAULT);
$conn->query("INSERT IGNORE INTO users (student_id, fullname, gender, email, contact, birth_date, address, password, role) 
    VALUES ('2024100949', 'Dela Cruz, Juan Carlos', 'Male', 'juancarlos@student.htu.edu.ph', '09171234567', '2003-05-15', '123 Mabini St., Angeles City, Pampanga', '$stuPwd', 'student')");

// Seed Books
$books = [
    ['To Kill a Mockingbird', 'Harper Lee', 'Fiction', '978-0-06-112008-4', 5, 3, 'Available', 'images/To_Kill_a_Mockingbird.jpg', 'A gripping, heart-wrenching, and wholly remarkable tale of coming-of-age in a South with a shocking force of moral purpose.', 'J.B. Lippincott & Co.', '1960'],
    ['I Know Why the Caged Bird Sings', 'Maya Angelou', 'Fiction', '978-0-345-51438-5', 4, 4, 'Available', 'images/I Know Why the Caged Bird Sings.jpg', 'Maya Angelou\'s autobiography about growing up in the American South in the 1930s and 40s, dealing with racism and trauma.', 'Random House', '1969'],
    ['Pride and Prejudice', 'Jane Austen', 'Fiction', '978-0-14-143951-8', 6, 5, 'Available', 'images/Pride and Prejudice.jpg', 'The story follows the main character Elizabeth Bennet as she deals with issues of manners, upbringing, morality, education, and marriage in the society of the landed gentry of the British Regency.', 'T. Egerton, Whitehall', '1813'],
    ['Fahrenheit 451', 'Ray Bradbury', 'Fiction', '978-1-4516-7331-9', 5, 2, 'Available', 'images/Fahrenheit 451.jpg', 'A dystopian novel about a future American society where books are outlawed and firemen burn any that are found.', 'Ballantine Books', '1953'],
    ['Psycho', 'Robert Bloch', 'Fiction', '978-0-312-95122-1', 3, 1, 'Available', 'images/Pyscho.jpg', 'A psychological horror novel about a young woman who ends up at an isolated motel run by a disturbed young man.', 'Simon & Schuster', '1959'],
    ['A Brief History of Time', 'Stephen Hawking', 'Science', '978-0-553-38016-3', 4, 4, 'Available', null, 'From the Big Bang to black holes, Hawking talks about the nature of space and time.', 'Bantam Books', '1988'],
    ['Sapiens: A Brief History of Humankind', 'Yuval Noah Harari', 'Non-Fiction', '978-0-06-231609-7', 5, 5, 'Available', null, 'A brief history of humankind, covering the Cognitive Revolution, Agricultural Revolution, and Scientific Revolution.', 'Harper', '2011'],
    ['The Art of War', 'Sun Tzu', 'History', '978-1-59030-963-7', 3, 3, 'Available', null, 'An ancient Chinese military treatise dating from the 5th century BC, attributed to the ancient Chinese military strategist Sun Tzu.', 'Shambhala', '500 BC'],
    ['Clean Code', 'Robert C. Martin', 'Technology', '978-0-13-235088-4', 4, 3, 'Available', null, 'A handbook of agile software craftsmanship that presents concepts, patterns, and practices for writing clean code.', 'Prentice Hall', '2008'],
    ['The Great Gatsby', 'F. Scott Fitzgerald', 'Fiction', '978-0-7432-7356-5', 4, 2, 'Available', null, 'A portrait of the Jazz Age in all of its decadence and excess, a critique of the American Dream.', 'Charles Scribner\'s Sons', '1925'],
    ['1984', 'George Orwell', 'Non-Fiction', '978-0-45-228285-3', 5, 4, 'Available', null, 'A dystopian novel depicting a totalitarian society ruled by Big Brother, where individualism and independent thinking are suppressed.', 'Secker & Warburg', '1949'],
    ['The Selfish Gene', 'Richard Dawkins', 'Science', '978-0-19-857519-1', 3, 3, 'Available', null, 'A popular science book by Richard Dawkins, which helped to popularize the gene-centric view of evolution.', 'Oxford University Press', '1976'],
    ['Introduction to Algorithms', 'Thomas H. Cormen', 'Technology', '978-0-26-203384-8', 3, 2, 'Available', null, 'A comprehensive text on algorithms, widely used in computer science courses.', 'MIT Press', '1990'],
    ['Philippine History', 'Teodoro Agoncillo', 'History', '978-971-08-0409-3', 6, 6, 'Available', null, 'A comprehensive account of Philippine history from the pre-Spanish era to the modern period.', 'Garotech', '1990'],
    ['The Power of Now', 'Eckhart Tolle', 'Non-Fiction', '978-1-57731-480-6', 4, 4, 'Available', null, 'A guide to spiritual enlightenment that encourages readers to live in the present moment.', 'New World Library', '1997'],
];

$i = 0;
foreach ($books as $b) {
    $title = $conn->real_escape_string($b[0]);
    $author = $conn->real_escape_string($b[1]);
    $category = $conn->real_escape_string($b[2]);
    $isbn = $conn->real_escape_string($b[3]);
    $qty = (int)$b[4];
    $avail = (int)$b[5];
    $status = $conn->real_escape_string($b[6]);
    $img = $b[7] ? "'" . $conn->real_escape_string($b[7]) . "'" : 'NULL';
    $desc = $conn->real_escape_string($b[8]);
    $pub = $conn->real_escape_string($b[9]);
    $yr = $conn->real_escape_string($b[10]);
    $is_feat = ($i < 5) ? 1 : 0;
    $conn->query("INSERT IGNORE INTO books (title, author, category, isbn, quantity, available, status, image_path, description, publisher, year_published, is_featured) 
        VALUES ('$title','$author','$category','$isbn',$qty,$avail,'$status',$img,'$desc','$pub','$yr',$is_feat)");
    $i++;
}

// Seed Announcements
$announcements = [
    ['New Science Collection Arrived', '50+ new books on quantum physics and biotechnology now available for borrowing.', '2026-04-10'],
    ['Book Club Meeting', "Join us this Friday at 4 PM to discuss 'The Midnight Library' in the Reading Hall.", '2026-04-08'],
    ['Extended Hours Next Week', 'Library will be open until 8 PM during exam week (Apr 15-19). Take advantage of the extra study time!', '2026-04-03'],
];
foreach ($announcements as $a) {
    $t = $conn->real_escape_string($a[0]);
    $c = $conn->real_escape_string($a[1]);
    $d = $conn->real_escape_string($a[2]);
    $conn->query("INSERT IGNORE INTO announcements (title, content, date_created) VALUES ('$t','$c','$d')");
}

// Seed some sample requests
$stuRow = $conn->query("SELECT id FROM users WHERE student_id='2024100949'")->fetch_assoc();
$stuId = $stuRow ? $stuRow['id'] : null;
if ($stuId) {
    $bookRows = $conn->query("SELECT id FROM books LIMIT 3")->fetch_all(MYSQLI_ASSOC);
    $statuses = ['Approved', 'Pending', 'Rejected'];
    $dates = [['2026-05-01','2026-05-08'], ['2026-05-10','2026-05-17'], ['2026-04-20','2026-04-27']];
    foreach ($bookRows as $i => $bk) {
        $bid = $bk['id'];
        $bd = $dates[$i][0]; $rd = $dates[$i][1];
        $st = $statuses[$i];
        $conn->query("INSERT INTO requests (user_id, book_id, borrow_date, return_date, purpose, status) 
            VALUES ($stuId, $bid, '$bd', '$rd', 'Academic research and study', '$st')");
    }
}

echo "✅ Database 'htu_library' initialized successfully!\n";
echo "   Tables created: users, books, announcements, requests\n";
echo "   Default admin: username=admin, password=1234\n";
echo "   Default student: username=2024100949, password=1111\n";
$conn->close();
?>
