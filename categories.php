<?php
session_start();
include 'include/db.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: index.php");
    exit();
}

// Search and Sort
$search = $_GET['search'] ?? '';
$sort = $_GET['sort'] ?? 'name_asc'; // Default sort: alphabetically

$where = [];
$params = [];

if (!empty($search)) {
    $where[] = "categories.name LIKE ?";
    $params[] = "%$search%";
}

$where_sql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

// Define sorting options
$sort_options = [
    'name_asc' => 'categories.name ASC',
    'name_desc' => 'categories.name DESC',
    'items_asc' => 'item_count ASC',
    'items_desc' => 'item_count DESC',
    'id_asc' => 'categories.id ASC',
    'id_desc' => 'categories.id DESC'
];

$order_by = $sort_options[$sort] ?? $sort_options['name_asc'];

$stmt = $conn->prepare("
    SELECT categories.*, 
           (SELECT COUNT(*) FROM questions WHERE category_id = categories.id) +
           (SELECT COUNT(*) FROM quizzes WHERE category_id = categories.id) +
           (SELECT COUNT(*) FROM flashcards WHERE category_id = categories.id) as item_count
    FROM categories 
    $where_sql 
    ORDER BY $order_by");
$stmt->execute($params);
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<?php include 'inc/header.php'; ?>
<style>
body {
    background-color: #dbeef5;
}

.form-control, .form-select {
    border-radius: 8px;
    border: 1px solid #e0e0e0;
    padding: 0.6rem 1rem;
    height: 42px;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.02);
    transition: all 0.3s ease;
    font-size: 0.95rem;
}

.form-control:focus, .form-select:focus {
    border-color: #2e5f80;
    box-shadow: 0 0 0 0.2rem rgba(46, 95, 128, 0.15);
}

.search-wrapper, .sort-wrapper {
    position: relative;
}

.search-wrapper .form-control {
    padding-left: 2.5rem;
}

.search-wrapper::before {
    content: '\F52A';
    font-family: "bootstrap-icons";
    position: absolute;
    left: 1rem;
    top: 50%;
    transform: translateY(-50%);
    color: #6c757d;
    font-size: 1rem;
    pointer-events: none;
}

.sort-wrapper .form-select {
    padding-right: 2rem;
    background-position: right 0.75rem center;
}

.btn {
    padding: 0.6rem 1.2rem;
    border-radius: 8px;
    font-weight: 500;
    transition: all 0.3s ease;
}

.btn-primary {
    background-color: #2e5f80;
    border-color: #2e5f80;
}

.btn-primary:hover {
    background-color: #254d69;
    border-color: #254d69;
    transform: translateY(-1px);
}

.sticky-dashboard-bar {
    position: sticky;
    top: 0;
    z-index: 100;
    background-color: #2e5f80;
    border-radius: 8px;
    box-shadow: 2px 2px 6px rgba(0, 0, 0, 0.1);
}
.content-area {
    background-color: #E5F1F7;
    padding: 30px;
    border-radius: 16px;
}
.category-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 24px;
    padding: 10px 0;
}
.category-card {
    background: #fff;
    border-radius: 20px;
    box-shadow: 0 8px 16px rgba(0, 0, 0, 0.05);
    padding: 24px;
    transition: 0.3s ease all;
    height: 160px;
    position: relative;
    cursor: pointer;
    overflow: hidden;
    display: flex;
    align-items: center;
    justify-content: center;
    text-align: center;
}
.category-card:hover {
    border: 3px solid #FFFFFF;
    box-shadow: 0 0 20px #44698C, 0 0 30px #44698C, 0 0 40px #C0D2D7;
    transform: translateY(-4px);
}
.card-front,
.card-back {
    transition: opacity 0.3s ease;
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    padding: 24px;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
}
.card-front {
    opacity: 1;
}
.card-back {
    opacity: 0;
    font-size: 17px;
    color: #333;
    line-height: 1.5;
    width: 100%;
    height: 100%;
    padding: 20px;
    text-align: center;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-direction: column;
}

.category-card:hover .card-front {
    opacity: 0;
}
.category-card:hover .card-back {
    opacity: 1;
}
.category-title {
    font-size: 18px;
    font-weight: 600;
    color: #000;
    margin-bottom: 6px;
}
.category-meta {
    font-size: 14px;
    color: #555;
}
</style>


<div class="content-area">
    <div class="sticky-dashboard-bar px-4 py-3 mb-4">
        <h2 class="m-0 text-white fw-bold">Categories</h2>
    </div>

    <form method="GET" class="mb-3 d-flex gap-3 flex-wrap align-items-center">
        <div class="d-flex gap-3 flex-wrap flex-grow-1">
            <div class="search-wrapper" style="min-width: 200px; flex: 2; max-width: 300px;">
                <input type="text" name="search" placeholder="Search category..." value="<?= htmlspecialchars($search) ?>" 
                       class="form-control">
            </div>
            <div class="sort-wrapper" style="min-width: 180px; flex: 1; max-width: 220px;">
                <select name="sort" class="form-select">
                    <option value="name_asc" <?= ($sort == 'name_asc') ? 'selected' : '' ?>>Name (A-Z)</option>
                    <option value="name_desc" <?= ($sort == 'name_desc') ? 'selected' : '' ?>>Name (Z-A)</option>
                    <option value="items_desc" <?= ($sort == 'items_desc') ? 'selected' : '' ?>>Most Items</option>
                    <option value="items_asc" <?= ($sort == 'items_asc') ? 'selected' : '' ?>>Least Items</option>
                    <option value="id_desc" <?= ($sort == 'id_desc') ? 'selected' : '' ?>>Newest First</option>
                    <option value="id_asc" <?= ($sort == 'id_asc') ? 'selected' : '' ?>>Oldest First</option>
                </select>
            </div>
            <div class="button-group d-flex gap-2">
                <button type="submit" class="btn btn-primary">Apply Filters</button>
                <?php if (!empty($_GET['search']) || !empty($_GET['sort'])): ?>
                    <a href="categories.php" class="btn btn-secondary">Clear</a>
                <?php endif; ?>
            </div>
        </div>
        <button type="button" class="btn btn-success ms-auto" data-bs-toggle="modal" data-bs-target="#addModal">
            <i class="bi bi-plus-lg me-1"></i>Add Category
        </button>
    </form>

    <div class="category-grid">
        <?php foreach ($categories as $cat): ?>
        <div class="category-card" data-bs-toggle="modal" data-bs-target="#editModal<?= $cat['id'] ?>">
            <div class="card-front">
                <div class="category-title"><?= htmlspecialchars($cat['name']) ?></div>
                <div class="category-meta">Total Items: <?= $cat['item_count'] ?></div>
            </div>
            <div class="card-back">
                <?= nl2br(htmlspecialchars($cat['description'] ?: 'No description provided.')) ?>
            </div>
        </div>
        <?php endforeach; ?>

        <!-- ✅ Add Category Card -->
        <div class="category-card d-flex justify-content-center align-items-center text-center" data-bs-toggle="modal" data-bs-target="#addModal">
            <div style="font-size: 48px; color: #4a5a75;">+</div>
        </div>
    </div>
</div>

<!-- ✅ Add Modal -->
<div class="modal fade" id="addModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" class="modal-content">
            <div class="modal-header"><h5 class="modal-title">Add Category</h5></div>
            <div class="modal-body">
                <div class="mb-2">
                    <label class="form-label">Category Name</label>
                    <input type="text" name="name" class="form-control" required>
                </div>
                <div class="mb-2">
                    <label class="form-label">Description</label>
                    <textarea name="description" class="form-control" rows="3"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button name="add_category" class="btn btn-success">Add</button>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </form>
    </div>
</div>

<!-- ✅ Edit Modals -->
<?php foreach ($categories as $cat): ?>
<div class="modal fade" id="editModal<?= $cat['id'] ?>" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" class="modal-content">
            <input type="hidden" name="id" value="<?= $cat['id'] ?>">
            <div class="modal-header"><h5 class="modal-title">Edit Category</h5></div>
            <div class="modal-body">
                <div class="mb-2">
                    <label class="form-label">Category Name</label>
                    <input type="text" name="name" value="<?= htmlspecialchars($cat['name']) ?>" class="form-control" required>
                </div>
                <div class="mb-2">
                    <label class="form-label">Description</label>
                    <textarea name="description" class="form-control" rows="3"><?= htmlspecialchars($cat['description']) ?></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button name="update_category" class="btn btn-primary">Update</button>
                <a href="?delete=<?= $cat['id'] ?>" class="btn btn-danger" onclick="return confirm('Are you sure?')">Delete</a>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </form>
    </div>
</div>
<?php endforeach; ?>

<?php include 'inc/footer.php'; ?>