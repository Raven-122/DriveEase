<?php
session_start();
include 'include/db.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: index.php");
    exit();
}

// Add Flashcard
if (isset($_POST['add_flashcard'])) {
    $title = $_POST['title'];
    $description = $_POST['description'];
    $category_id = $_POST['category_id'];
    $image_path = '';

    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $upload_dir = 'uploads/flashcards/';
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
        if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif'])) {
            $filename = uniqid() . '.' . $ext;
            $upload_path = $upload_dir . $filename;
            if (move_uploaded_file($_FILES['image']['tmp_name'], $upload_path)) {
                $image_path = $upload_path;
            }
        }
    }

    $stmt = $conn->prepare("INSERT INTO flashcards (title, description, category_id, image_path) VALUES (?, ?, ?, ?)");
    $stmt->execute([$title, $description, $category_id, $image_path]);
    header("Location: flashcards.php");
    exit();
}

// Update Flashcard
if (isset($_POST['update_flashcard'])) {
    $id = $_POST['id'];
    $title = $_POST['title'];
    $description = $_POST['description'];
    $category_id = $_POST['category_id'];

    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $upload_dir = 'uploads/flashcards/';
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
        if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif'])) {
            $stmt = $conn->prepare("SELECT image_path FROM flashcards WHERE id = ?");
            $stmt->execute([$id]);
            $old = $stmt->fetch();
            if ($old && file_exists($old['image_path'])) {
                unlink($old['image_path']);
            }

            $filename = uniqid() . '.' . $ext;
            $upload_path = $upload_dir . $filename;
            if (move_uploaded_file($_FILES['image']['tmp_name'], $upload_path)) {
                $stmt = $conn->prepare("UPDATE flashcards SET title = ?, description = ?, category_id = ?, image_path = ? WHERE id = ?");
                $stmt->execute([$title, $description, $category_id, $upload_path, $id]);
                header("Location: flashcards.php");
                exit();
            }
        }
    }

    $stmt = $conn->prepare("UPDATE flashcards SET title = ?, description = ?, category_id = ? WHERE id = ?");
    $stmt->execute([$title, $description, $category_id, $id]);
    header("Location: flashcards.php");
    exit();
}

// Delete Flashcard
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $stmt = $conn->prepare("SELECT image_path FROM flashcards WHERE id = ?");
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    if ($row && file_exists($row['image_path'])) {
        unlink($row['image_path']);
    }
    $stmt = $conn->prepare("DELETE FROM flashcards WHERE id = ?");
    $stmt->execute([$id]);
    header("Location: flashcards.php");
    exit();
}

// Search
$search = $_GET['search'] ?? '';
$category_filter = $_GET['category_filter'] ?? '';
$sort = $_GET['sort'] ?? 'id_desc'; // Default sort: newest first

$where = [];
$params = [];

if (!empty($search)) {
    $where[] = "flashcards.title LIKE ?";
    $params[] = "%$search%";
}

if (!empty($category_filter)) {
    $where[] = "flashcards.category_id = ?";
    $params[] = $category_filter;
}

$where_sql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

// Define sorting options
$sort_options = [
    'title_asc' => 'flashcards.title ASC',
    'title_desc' => 'flashcards.title DESC',
    'category_asc' => 'categories.name ASC',
    'category_desc' => 'categories.name DESC',
    'id_asc' => 'flashcards.id ASC',
    'id_desc' => 'flashcards.id DESC'
];

$order_by = $sort_options[$sort] ?? $sort_options['id_desc'];

$stmt = $conn->prepare("SELECT flashcards.*, categories.name AS category_name 
                        FROM flashcards 
                        LEFT JOIN categories ON flashcards.category_id = categories.id 
                        $where_sql 
                        ORDER BY $order_by");
$stmt->execute($params);
$all_flashcards = $stmt->fetchAll();

$category_stmt = $conn->query("SELECT * FROM categories ORDER BY name");
$categories = $category_stmt->fetchAll();
?>

<?php include 'inc/header.php'; ?>
<?php include 'inc/sidebar.php'; ?>

<style>
body {
    background-color: #dbeef5;
}
.content-area {
    padding: 20px;
    background-color: #dbeef5;
}
.sticky-dashboard-bar {
    position: sticky;
    top: 0;
    z-index: 100;
    background-color: #2e5f80;
    border-radius: 8px;
    box-shadow: 2px 2px 6px rgba(0, 0, 0, 0.1);
}
.form-control, .form-select {
    border-radius: 10px;
}
.btn {
    border-radius: 10px;
}
.table-container {
    background: transparent;
    margin-top: 10px;
}
.table {
    width: 100%;
    border-collapse: separate;
    border-spacing: 0 12px;
}
.table thead {
    display: none;
}
.table tbody tr {
    background: #ffffff;
    border-radius: 25px;
    box-shadow: 0 2px 6px rgba(0,0,0,0.08);
    transition: 0.2s ease;
}

.table tbody tr:hover {
    background-color: #f0f8ff;
    transform: translateY(-2px);
}
.table td {
    vertical-align: middle;
    padding: 18px;
    border-top: none;
}
.flashcard-image {
    width: 80px;
    height: 80px;
    object-fit: cover;
    border-radius: 8px;
    border: 2px solid #e0e0e0;
    margin-right: 10px; /* New: space between image and text */
}

.badge {
    font-size: 0.9rem;
    background-color: #007bff;
    color: #fff;
    padding: 5px 10px;
    border-radius: 12px;
}
.flashcard-box {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    flex-wrap: wrap;
    gap: 15px;
    background: white;
    border-radius: 30px;
    box-shadow: 0 2px 6px rgba(0, 0, 0, 0.06);
    padding: 20px 25px;
}


</style>

<div class="content-area">
    <div class="sticky-dashboard-bar px-4 py-3 mb-4">
        <h2 class="m-0 text-white fw-bold">Flashcards Management</h2>
    </div>

    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <form method="GET" class="d-flex flex-nowrap align-items-center gap-2 mb-0 flex-grow-1">
            <input type="text" name="search" placeholder="Search flashcards..." value="<?= htmlspecialchars($search); ?>" class="form-control" style="max-width: 250px; min-width: 200px;">
            <select name="category_filter" class="form-select" style="max-width: 200px; min-width: 150px;">
                <option value="">All Categories</option>
                <?php foreach ($categories as $cat): ?>
                    <option value="<?= $cat['id'] ?>" <?= ($category_filter == $cat['id']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($cat['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <select name="sort" class="form-select" style="max-width: 200px; min-width: 150px;">
                <option value="id_desc" <?= ($sort == 'id_desc') ? 'selected' : '' ?>>Newest First</option>
                <option value="id_asc" <?= ($sort == 'id_asc') ? 'selected' : '' ?>>Oldest First</option>
                <option value="title_asc" <?= ($sort == 'title_asc') ? 'selected' : '' ?>>Title (A-Z)</option>
                <option value="title_desc" <?= ($sort == 'title_desc') ? 'selected' : '' ?>>Title (Z-A)</option>
                <option value="category_asc" <?= ($sort == 'category_asc') ? 'selected' : '' ?>>Category (A-Z)</option>
                <option value="category_desc" <?= ($sort == 'category_desc') ? 'selected' : '' ?>>Category (Z-A)</option>
            </select>
            <button type="submit" class="btn btn-primary">Apply Filters</button>
            <?php if (!empty($_GET['search']) || !empty($_GET['category_filter']) || !empty($_GET['sort'])): ?>
                <a href="flashcards.php" class="btn btn-secondary">Clear</a>
            <?php endif; ?>
        </form>

        <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addModal">Add Flashcard</button>
    </div>

    <div class="table-container">
  <?php foreach ($all_flashcards as $row): ?>
    <div class="flashcard-box d-flex justify-content-between align-items-start flex-nowrap gap-3 p-3 mb-3">
  <div class="d-flex align-items-start gap-3 flex-grow-1">
    <div class="fw-bold"><?= $row['id'] ?></div>
    <?php if (!empty($row['image_path'])): ?>
      <img src="<?= htmlspecialchars($row['image_path']) ?>" alt="Image" class="flashcard-image">
    <?php endif; ?>
    <div>
      <div class="fw-bold mb-1"><?= htmlspecialchars($row['title']) ?></div>
      <div><?= htmlspecialchars($row['description']) ?></div>
    </div>
  </div>

  <div class="d-flex flex-column align-items-end text-end flex-shrink-0" style="min-width: 160px;">
    <span class="badge mb-2"><?= htmlspecialchars($row['category_name']) ?></span>
    <div>
      <button class="btn btn-sm btn-warning" data-bs-toggle="modal" data-bs-target="#editModal<?= $row['id'] ?>">Edit</button>
      <a href="flashcards.php?delete=<?= $row['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure?')">Delete</a>
    </div>
  </div>
</div>

  <?php endforeach; ?>
</div>


<!-- ✅ Add Modal -->
<div class="modal fade" id="addModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" class="modal-content" enctype="multipart/form-data">
            <div class="modal-header"><h5 class="modal-title">Add Flashcard</h5></div>
            <div class="modal-body">
                <input type="text" name="title" class="form-control mb-2" placeholder="Title" required>
                <textarea name="description" class="form-control mb-2" placeholder="Description" required></textarea>
                <input type="file" name="image" class="form-control mb-2" accept="image/*">
                <select name="category_id" class="form-select" required>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="modal-footer">
                <button name="add_flashcard" class="btn btn-success">Add</button>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </form>
    </div>
</div>

<!-- ✅ Edit Modals -->
<?php foreach ($all_flashcards as $row): ?>
<div class="modal fade" id="editModal<?= $row['id'] ?>" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" class="modal-content" enctype="multipart/form-data">
            <input type="hidden" name="id" value="<?= $row['id'] ?>">
            <div class="modal-header"><h5 class="modal-title">Edit Flashcard</h5></div>
            <div class="modal-body">
                <?php if (!empty($row['image_path'])): ?>
                    <img src="<?= htmlspecialchars($row['image_path']) ?>" alt="Image" class="mb-2" style="max-width:100%;">
                <?php endif; ?>
                <input type="text" name="title" value="<?= htmlspecialchars($row['title']) ?>" class="form-control mb-2" required>
                <textarea name="description" class="form-control mb-2" required><?= htmlspecialchars($row['description']) ?></textarea>
                <input type="file" name="image" class="form-control mb-2" accept="image/*">
                <select name="category_id" class="form-select" required>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?= $cat['id'] ?>" <?= $cat['id'] == $row['category_id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($cat['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="modal-footer">
                <button name="update_flashcard" class="btn btn-primary">Update</button>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </form>
    </div>
</div>
<?php endforeach; ?>

<?php include 'inc/footer.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>