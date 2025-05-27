<?php
session_start();
include 'include/db.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: index.php");
    exit();
}

// Fetch categories for the add quiz form
$categories = $conn->query("SELECT * FROM categories ORDER BY name ASC")->fetchAll();

// Handle quiz creation
if (isset($_POST['add_quiz'])) {
    $title = $_POST['title'];
    $status = $_POST['status'];
    $category_id = $_POST['category_id'];
    $question_ids = $_POST['question_ids'] ?? [];
    
    $iconName = null;
    if (!empty($_FILES['icon']['name'])) {
        $iconName = time() . '_' . basename($_FILES['icon']['name']);
        $targetDir = 'uploads/quiz_icons/';
        
        // Create directory if it doesn't exist
        if (!file_exists($targetDir)) {
            mkdir($targetDir, 0777, true);
        }
        
        move_uploaded_file($_FILES['icon']['tmp_name'], $targetDir . $iconName);
    }

    try {
        // Start transaction
        $conn->beginTransaction();
        
        // Insert the quiz
        $stmt = $conn->prepare("INSERT INTO quizzes (title, icon, category_id, status) VALUES (?, ?, ?, ?)");
        $stmt->execute([$title, $iconName, $category_id, $status]);
        $quiz_id = $conn->lastInsertId();

        // Insert quiz questions if any were selected
        if (!empty($question_ids)) {
            $stmt = $conn->prepare("INSERT INTO quiz_questions (quiz_id, question_id) VALUES (?, ?)");
            foreach ($question_ids as $qid) {
                $stmt->execute([$quiz_id, $qid]);
            }
        }

        // Commit the transaction
        $conn->commit();
        $_SESSION['success'] = "Quiz created successfully!";
    } catch (PDOException $e) {
        // Rollback on error
        $conn->rollBack();
        $_SESSION['error'] = "Error creating quiz: " . $e->getMessage();
    }
}

$search = $_GET['search'] ?? '';
$category_filter = $_GET['category_filter'] ?? '';
$sort = $_GET['sort'] ?? 'id_desc'; // Default sort: newest first

$where = [];
$params = [];

if (!empty($search)) {
    $where[] = "quizzes.title LIKE ?";
    $params[] = "%$search%";
}

if (!empty($category_filter)) {
    $where[] = "quizzes.category_id = ?";
    $params[] = $category_filter;
}

$where_sql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

// Define sorting options
$sort_options = [
    'title_asc' => 'quizzes.title ASC',
    'title_desc' => 'quizzes.title DESC',
    'category_asc' => 'categories.name ASC',
    'category_desc' => 'categories.name DESC',
    'questions_asc' => 'question_count ASC',
    'questions_desc' => 'question_count DESC',
    'id_asc' => 'quizzes.id ASC',
    'id_desc' => 'quizzes.id DESC'
];

$order_by = $sort_options[$sort] ?? $sort_options['id_desc'];

$stmt = $conn->prepare("
    SELECT quizzes.*, categories.name AS category_name,
    (SELECT COUNT(*) FROM quiz_questions WHERE quiz_id = quizzes.id) AS question_count
    FROM quizzes
    LEFT JOIN categories ON quizzes.category_id = categories.id
    $where_sql
    ORDER BY $order_by");
$stmt->execute($params);
$quizzes = $stmt->fetchAll();
?>

<?php include 'inc/header.php'; ?>
<style>
body {
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
.content-area {
    background-color: #E5F1F7;
    padding: 30px;
    border-radius: 16px;
}
.quiz-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 24px;
    padding: 10px 0;
}
.quiz-card {
    background: #fff;
    border-radius: 20px;
    box-shadow: 0 8px 16px rgba(0, 0, 0, 0.05);
    padding: 20px 24px;
    display: flex;
    flex-direction: column;
    justify-content: center;
    transition: 0.2s ease;
    min-height: 140px;
    position: relative;
    text-decoration: none;
}
.quiz-card:hover {
    background-color: #fff;
    color: #44698C;
    border: 3px solid #FFFFFF;
    box-shadow: 0 0 20px #44698C, 0 0 30px #44698C, 0 0 40px #C0D2D7;
    transform: translateY(-4px);
    transition: 0.3s ease all;
}
.quiz-title {
    font-size: 18px;
    font-weight: 600;
    color: #000;
    margin-bottom: 4px;
}
.quiz-meta {
    font-size: 14px;
    color: #555;
}

.modal-content {
    border-radius: 15px;
    border: none;
}

.modal-header {
    background: linear-gradient(135deg, #2e5f80, #3a7ca5);
    color: white;
    border-radius: 15px 15px 0 0;
    border-bottom: none;
}

.modal-body {
    padding: 24px;
}

.form-label {
    font-weight: 600;
    color: #2c3e50;
    margin-bottom: 8px;
}

.form-control, .form-select {
    border-radius: 10px;
    border: 1px solid #e2e8f0;
    padding: 12px;
}

.form-control:focus, .form-select:focus {
    border-color: #2e5f80;
    box-shadow: 0 0 0 0.2rem rgba(46, 95, 128, 0.25);
}
</style>

<div class="content-area">
    <!-- Success/Error Messages -->
    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <?= htmlspecialchars($_SESSION['success']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            <?php unset($_SESSION['success']); ?>
        </div>
    <?php endif; ?>

    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <?= htmlspecialchars($_SESSION['error']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            <?php unset($_SESSION['error']); ?>
        </div>
    <?php endif; ?>

    <div class="sticky-dashboard-bar px-4 py-3 mb-4">
        <div class="d-flex justify-content-between align-items-center">
            <h2 class="m-0 text-white fw-bold">Quizzes</h2>
            <button type="button" class="btn btn-light" data-bs-toggle="modal" data-bs-target="#addQuizModal">
                <i class="bi bi-plus-lg me-2"></i>Add Quiz
            </button>
        </div>
    </div>

    <form method="GET" class="mb-4">
        <div class="d-flex flex-wrap gap-3 align-items-start">
            <div class="search-wrapper position-relative" style="min-width: 200px; max-width: 300px;">
                <i class="bi bi-search position-absolute" style="left: 12px; top: 12px; color: #6c757d;"></i>
                <input type="text" name="search" placeholder="Search quizzes..." value="<?= htmlspecialchars($search) ?>" 
                    class="form-control ps-4" style="height: 42px; padding-left: 35px;">
            </div>
            <div class="category-wrapper" style="min-width: 180px; max-width: 220px;">
                <select name="category_filter" class="form-select" style="height: 42px;">
                    <option value="">All Categories</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?= $cat['id'] ?>" <?= ($category_filter == $cat['id']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($cat['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="sort-wrapper" style="min-width: 180px; max-width: 220px;">
                <select name="sort" class="form-select" style="height: 42px;">
                    <option value="id_desc" <?= ($sort == 'id_desc') ? 'selected' : '' ?>>Newest First</option>
                    <option value="id_asc" <?= ($sort == 'id_asc') ? 'selected' : '' ?>>Oldest First</option>
                    <option value="title_asc" <?= ($sort == 'title_asc') ? 'selected' : '' ?>>Title (A-Z)</option>
                    <option value="title_desc" <?= ($sort == 'title_desc') ? 'selected' : '' ?>>Title (Z-A)</option>
                    <option value="category_asc" <?= ($sort == 'category_asc') ? 'selected' : '' ?>>Category (A-Z)</option>
                    <option value="category_desc" <?= ($sort == 'category_desc') ? 'selected' : '' ?>>Category (Z-A)</option>
                    <option value="questions_asc" <?= ($sort == 'questions_asc') ? 'selected' : '' ?>>Questions (Low to High)</option>
                    <option value="questions_desc" <?= ($sort == 'questions_desc') ? 'selected' : '' ?>>Questions (High to Low)</option>
                </select>
            </div>
            <div class="button-group d-flex gap-2">
                <button type="submit" class="btn btn-primary" style="height: 42px;">Apply Filters</button>
                <?php if (!empty($_GET['search']) || !empty($_GET['category_filter']) || !empty($_GET['sort'])): ?>
                    <a href="quizzes.php" class="btn btn-secondary" style="height: 42px;">Clear</a>
                <?php endif; ?>
            </div>
        </div>
    </form>

    <!-- Quiz Grid -->
    <div class="quiz-grid">
        <?php foreach ($quizzes as $quiz): ?>
        <a href="quiz_edit.php?id=<?= $quiz['id'] ?>" class="quiz-card">
            <div>
                <div class="quiz-title"><?= htmlspecialchars($quiz['title']) ?></div>
                <div class="quiz-meta">Category: <?= htmlspecialchars($quiz['category_name']) ?></div>
                <div class="quiz-meta">Questions: <?= $quiz['question_count'] ?></div>
                <div class="quiz-meta">
                    Status:
                    <span class="badge <?= $quiz['status'] === 'Published' ? 'bg-success' : 'bg-secondary' ?>">
                        <?= $quiz['status'] ?>
                    </span>
                </div>
            </div>
        </a>
        <?php endforeach; ?>
    </div> <!-- end of quiz-grid -->

    <!-- Add Quiz Modal -->
    <div class="modal fade" id="addQuizModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add New Quiz</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form method="POST" id="addQuizForm" enctype="multipart/form-data">
                        <div class="mb-3">
                            <label class="form-label">Quiz Title</label>
                            <input type="text" name="title" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Quiz Icon (optional)</label>
                            <input type="file" name="icon" accept="image/*" class="form-control">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Category</label>
                            <select name="category_id" class="form-select" required>
                                <option disabled selected>Select Category</option>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label d-block">Selected Questions</label>
                            <button type="button" id="selectQuestionsBtn" class="btn btn-outline-primary mb-2">Select Questions</button>
                            <p class="text-muted" id="selectedQuestionsCount">No questions selected</p>
                            <div id="selectedQuestionsContainer"></div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-select" required>
                                <option value="Published">Published</option>
                                <option value="Draft">Draft</option>
                            </select>
                        </div>
                        <div class="modal-footer px-0 pb-0">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" name="add_quiz" class="btn btn-success">Create Quiz</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div> <!-- end of content-area -->

<?php include 'inc/footer.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
let selectedQuestions = [];

document.getElementById('selectQuestionsBtn').addEventListener('click', function() {
    // Save current form data to session
    const formData = {
        title: document.querySelector('#addQuizForm input[name="title"]').value,
        category_id: document.querySelector('#addQuizForm select[name="category_id"]').value,
        status: document.querySelector('#addQuizForm select[name="status"]').value
    };
    
    // Store form data in session storage
    sessionStorage.setItem('quizFormData', JSON.stringify(formData));
    
    // Redirect to question picker
    window.location.href = 'question_picker.php?mode=new';
});

// When the modal is shown, restore any saved form data
document.getElementById('addQuizModal').addEventListener('show.bs.modal', function() {
    const savedFormData = sessionStorage.getItem('quizFormData');
    if (savedFormData) {
        const formData = JSON.parse(savedFormData);
        document.querySelector('#addQuizForm input[name="title"]').value = formData.title || '';
        document.querySelector('#addQuizForm select[name="category_id"]').value = formData.category_id || '';
        document.querySelector('#addQuizForm select[name="status"]').value = formData.status || 'Published';
    }
    
    // Update selected questions count if any
    if (selectedQuestions.length > 0) {
        document.getElementById('selectedQuestionsCount').textContent = 
            `You selected ${selectedQuestions.length} question(s)`;
        
        // Add hidden inputs for selected questions
        const container = document.getElementById('selectedQuestionsContainer');
        container.innerHTML = '';
        selectedQuestions.forEach(qid => {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'question_ids[]';
            input.value = qid;
            container.appendChild(input);
        });
    }
});

// When the modal is hidden, clear the form and session storage
document.getElementById('addQuizModal').addEventListener('hidden.bs.modal', function() {
    document.getElementById('addQuizForm').reset();
    sessionStorage.removeItem('quizFormData');
    document.getElementById('selectedQuestionsCount').textContent = 'No questions selected';
    document.getElementById('selectedQuestionsContainer').innerHTML = '';
});

// If returning from question picker, update the selected questions
window.addEventListener('load', function() {
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.has('selected_questions')) {
        selectedQuestions = urlParams.get('selected_questions').split(',').filter(Boolean);
        
        // If we have selected questions and there's saved form data, show the modal
        if (selectedQuestions.length > 0 && sessionStorage.getItem('quizFormData')) {
            const modal = new bootstrap.Modal(document.getElementById('addQuizModal'));
            modal.show();
        }
    }
});
</script>
<?php include 'inc/footer.php'; ?>