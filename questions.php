<?php
session_start();
include 'include/db.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: index.php");
    exit();
}

// Fetch categories
$cat_stmt = $conn->query("SELECT * FROM categories ORDER BY name ASC");
$categories = $cat_stmt->fetchAll();

// Add Question
if (isset($_POST['add_question'])) {
    $stmt = $conn->prepare("INSERT INTO questions 
        (question_text, category_id, option_a, option_b, option_c, option_d, correct_option) 
        VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([
        $_POST['question_text'], $_POST['category_id'],
        $_POST['option_a'], $_POST['option_b'], $_POST['option_c'], $_POST['option_d'],
        $_POST['correct_option']
    ]);
    header("Location: questions.php");
    exit();
}

// Update Question
if (isset($_POST['update_question'])) {
    $stmt = $conn->prepare("UPDATE questions SET 
        question_text = ?, category_id = ?, option_a = ?, option_b = ?, option_c = ?, option_d = ?, correct_option = ? 
        WHERE id = ?");
    $stmt->execute([
        $_POST['question_text'], $_POST['category_id'],
        $_POST['option_a'], $_POST['option_b'], $_POST['option_c'], $_POST['option_d'],
        $_POST['correct_option'], $_POST['id']
    ]);
    header("Location: questions.php");
    exit();
}

// Delete Question
if (isset($_GET['delete'])) {
    $stmt = $conn->prepare("DELETE FROM questions WHERE id = ?");
    $stmt->execute([$_GET['delete']]);
    header("Location: questions.php");
    exit();
}

// Search and Sort
$search = $_GET['search'] ?? '';
$category_filter = $_GET['category_filter'] ?? '';
$sort = $_GET['sort'] ?? 'id_desc'; // Default sort: newest first

$where = [];
$params = [];

if (!empty($search)) {
    $where[] = "questions.question_text LIKE ?";
    $params[] = "%$search%";
}

if (!empty($category_filter)) {
    $where[] = "questions.category_id = ?";
    $params[] = $category_filter;
}

$where_sql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

// Define sorting options
$sort_options = [
    'text_asc' => 'questions.question_text ASC',
    'text_desc' => 'questions.question_text DESC',
    'category_asc' => 'categories.name ASC',
    'category_desc' => 'categories.name DESC',
    'id_asc' => 'questions.id ASC',
    'id_desc' => 'questions.id DESC'
];

$order_by = $sort_options[$sort] ?? $sort_options['id_desc'];

$stmt = $conn->prepare("SELECT questions.*, categories.name AS category_name 
                        FROM questions 
                        LEFT JOIN categories ON questions.category_id = categories.id 
                        $where_sql 
                        ORDER BY $order_by");
$stmt->execute($params);
$all_questions = $stmt->fetchAll();
?>

<?php include 'inc/header.php'; ?>
<?php include 'inc/sidebar.php'; ?>

<style>
    .content-area {
        padding: 20px;
        background-color: #E5F1F7;
        min-height: 100vh;
    }
    

    .form-control, .form-select {
        border-radius: 10px;
    }
    table {
        background: white;
        border-radius: 10px;
        overflow: hidden;
    }
    table th {
        background-color: #f8f9fa;
    }
    table td {
        vertical-align: middle;
    }
    .btn {
        border-radius: 10px;
    }
    .table tbody tr:hover {
        background-color: #e6f0ff;
    }
    .sticky-dashboard-bar {
    position: sticky;
    top: 0;
    z-index: 100;
    background-color: #2e5f80;
    border-radius: 8px;
    box-shadow: 2px 2px 6px rgba(0, 0, 0, 0.1);
}
.table tbody tr:hover {
    background-color: #e6f0ff;
    transition: background-color 0.2s ease-in-out;
}


</style>

<div class="content-area">
    <!-- ✅ Blue Header -->
    <div class="sticky-dashboard-bar px-4 py-3 mb-4">
    <h2 class="m-0 text-white fw-bold">Questions Management</h2>
</div>


    <!-- ✅ Toolbar: Everything aligned in one row -->
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <!-- Left side: search form -->
        <form method="GET" class="d-flex flex-nowrap align-items-center gap-2 mb-0 flex-grow-1">
            <input type="text" name="search" placeholder="Search questions..." value="<?= htmlspecialchars($search); ?>" 
                   class="form-control" style="max-width: 250px;">
            <select name="category_filter" class="form-select" style="max-width: 200px;">
                <option value="">All Categories</option>
                <?php foreach ($categories as $cat): ?>
                    <option value="<?= $cat['id'] ?>" <?= ($category_filter == $cat['id']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($cat['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <select name="sort" class="form-select" style="max-width: 200px;">
                <option value="id_desc" <?= ($sort == 'id_desc') ? 'selected' : '' ?>>Newest First</option>
                <option value="id_asc" <?= ($sort == 'id_asc') ? 'selected' : '' ?>>Oldest First</option>
                <option value="text_asc" <?= ($sort == 'text_asc') ? 'selected' : '' ?>>Question Text (A-Z)</option>
                <option value="text_desc" <?= ($sort == 'text_desc') ? 'selected' : '' ?>>Question Text (Z-A)</option>
                <option value="category_asc" <?= ($sort == 'category_asc') ? 'selected' : '' ?>>Category (A-Z)</option>
                <option value="category_desc" <?= ($sort == 'category_desc') ? 'selected' : '' ?>>Category (Z-A)</option>
            </select>
            <button type="submit" class="btn btn-primary">Apply Filters</button>
            <?php if (!empty($_GET['search']) || !empty($_GET['category_filter']) || !empty($_GET['sort'])): ?>
                <a href="questions.php" class="btn btn-secondary">Clear</a>
            <?php endif; ?>
        </form>

        <!-- Right side: Add Question button -->
        <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addModal">
            <i class="bi bi-plus-lg"></i> Add Question
        </button>
    </div>






    <!-- ✅ Questions Table -->
    <table class="table table-bordered">
        <thead>
            <tr>
                <th>ID</th>
                <th>Question</th>
                <th>Category</th>
                <th>Options (A-D)</th>
                <th>Correct</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($all_questions as $q): ?>
                <tr>
                    <td><?= $q['id'] ?></td>
                    <td><?= htmlspecialchars($q['question_text']) ?></td>
                    <td><?= htmlspecialchars($q['category_name']) ?></td>
                    <td>
    <ul class="list-unstyled mb-0">
        <?php foreach (['A', 'B', 'C', 'D'] as $letter): ?>
            <li>
                <strong><?= $letter ?>:</strong>
                <span class="<?= $q['correct_option'] === $letter ? 'text-success fw-bold' : '' ?>">
                    <?= htmlspecialchars($q['option_' . strtolower($letter)]) ?>
                </span>
            </li>
        <?php endforeach; ?>
    </ul>
</td>

                    <td><?= $q['correct_option'] ?></td>
                    <td>
                        <button class="btn btn-sm btn-warning" data-bs-toggle="modal" data-bs-target="#editModal<?= $q['id'] ?>">Edit</button>
                        <a href="questions.php?delete=<?= $q['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure?')">Delete</a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<!-- ✅ Add Modal -->
<div class="modal fade" id="addModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" class="modal-content">
            <div class="modal-header"><h5 class="modal-title">Add Question</h5></div>
            <div class="modal-body">
                <div class="mb-2">
                    <label>Question Text</label>
                    <textarea name="question_text" class="form-control" required></textarea>
                </div>
                <div class="mb-2">
                    <label>Category</label>
                    <select name="category_id" class="form-select" required>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php foreach (['a', 'b', 'c', 'd'] as $opt): ?>
                    <div class="mb-2">
                        <label>Option <?= strtoupper($opt) ?></label>
                        <input type="text" name="option_<?= $opt ?>" class="form-control" required>
                    </div>
                <?php endforeach; ?>
                <div class="mb-2">
                    <label>Correct Option</label>
                    <select name="correct_option" class="form-select" required>
                        <option value="A">A</option>
                        <option value="B">B</option>
                        <option value="C">C</option>
                        <option value="D">D</option>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button name="add_question" class="btn btn-success">Add</button>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </form>
    </div>
</div>

<!-- ✅ Edit Modals -->
<?php foreach ($all_questions as $q): ?>
<div class="modal fade" id="editModal<?= $q['id'] ?>" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" class="modal-content">
            <input type="hidden" name="id" value="<?= $q['id'] ?>">
            <div class="modal-header"><h5 class="modal-title">Edit Question</h5></div>
            <div class="modal-body">
                <div class="mb-2">
                    <label>Question Text</label>
                    <textarea name="question_text" class="form-control" required><?= htmlspecialchars($q['question_text']) ?></textarea>
                </div>
                <div class="mb-2">
                    <label>Category</label>
                    <select name="category_id" class="form-select" required>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= $cat['id'] ?>" <?= $cat['id'] == $q['category_id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($cat['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php foreach (['a', 'b', 'c', 'd'] as $opt): ?>
                    <div class="mb-2">
                        <label>Option <?= strtoupper($opt) ?></label>
                        <input type="text" name="option_<?= $opt ?>" class="form-control" value="<?= htmlspecialchars($q['option_' . $opt]) ?>" required>
                    </div>
                <?php endforeach; ?>
                <div class="mb-2">
                    <label>Correct Option</label>
                    <select name="correct_option" class="form-select" required>
                        <?php foreach (['A', 'B', 'C', 'D'] as $opt): ?>
                            <option value="<?= $opt ?>" <?= $q['correct_option'] == $opt ? 'selected' : '' ?>><?= $opt ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button name="update_question" class="btn btn-primary">Update</button>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </form>
    </div>
</div>
<?php endforeach; ?>

<?php include 'inc/footer.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>