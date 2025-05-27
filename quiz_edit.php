<?php
session_start();
include 'include/db.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: index.php");
    exit();
}

// Get quiz ID from URL
$id = $_GET['id'] ?? 0;

// Fetch quiz data
try {
    $stmt = $conn->prepare("
        SELECT q.*, c.name as category_name 
        FROM quizzes q 
        LEFT JOIN categories c ON q.category_id = c.id 
        WHERE q.id = :id
    ");
    $stmt->execute([':id' => $id]);
    $quiz = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$quiz) {
        $_SESSION['error'] = "Quiz not found";
        header("Location: quizzes.php");
        exit();
    }

    // Fetch quiz questions
    $stmt = $conn->prepare("
        SELECT q.* 
        FROM questions q
        JOIN quiz_questions qq ON q.id = qq.question_id
        WHERE qq.quiz_id = :quiz_id
        ORDER BY qq.id ASC
    ");
    $stmt->execute([':quiz_id' => $id]);
    $quiz_questions = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Store questions in session for question picker
    $_SESSION['edit_quiz_selected_questions'] = array_column($quiz_questions, 'id');

} catch (PDOException $e) {
    $_SESSION['error'] = "Error loading quiz: " . $e->getMessage();
    header("Location: quizzes.php");
    exit();
}
$stmt = $conn->prepare("SELECT * FROM quizzes WHERE id = ?");
$stmt->execute([$id]);
$quiz = $stmt->fetch();

if (!$quiz) {
    die("Quiz not found.");
}

$categories = $conn->query("SELECT * FROM categories ORDER BY name ASC")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = $_POST['title'];
    $category_id = $_POST['category_id'];
    $status = $_POST['status'];
    $iconName = $_POST['existing_icon'];

    if (!empty($_FILES['icon']['name'])) {
        $iconName = time() . '_' . basename($_FILES['icon']['name']);
        move_uploaded_file($_FILES['icon']['tmp_name'], 'uploads/quiz_icons/' . $iconName);
    }

    // Start transaction
    $conn->beginTransaction();

    try {
        // Update quiz details
        $stmt = $conn->prepare("UPDATE quizzes SET title = ?, category_id = ?, status = ?, icon = ? WHERE id = ?");
        $stmt->execute([$title, $category_id, $status, $iconName, $id]);

        // Update quiz questions if they were changed
        if (isset($_SESSION['edit_quiz_selected_questions'])) {
            // Remove old questions
            $stmt = $conn->prepare("DELETE FROM quiz_questions WHERE quiz_id = ?");
            $stmt->execute([$id]);

            // Add new questions
            $stmt = $conn->prepare("INSERT INTO quiz_questions (quiz_id, question_id) VALUES (?, ?)");
            foreach ($_SESSION['edit_quiz_selected_questions'] as $qid) {
                $stmt->execute([$id, $qid]);
            }
            
            // Clear the session
            unset($_SESSION['edit_quiz_selected_questions']);
        }

        $conn->commit();
        $_SESSION['success'] = "Quiz updated successfully";
        header("Location: quizzes.php");
        exit();
    } catch (Exception $e) {
        $conn->rollBack();
        $_SESSION['error'] = "Error updating quiz: " . $e->getMessage();
    }

    header("Location: quizzes.php");
    exit();
}
?>

<?php include 'inc/header.php'; ?>
<!-- ✅ Select2 CSS -->
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />

<style>
.content-area {
    background-color: #E5F1F7;
    padding: 30px;
    border-radius: 16px;
}
.sticky-dashboard-bar {
    background-color: #2e5f80;
    border-radius: 8px;
    box-shadow: 2px 2px 6px rgba(0, 0, 0, 0.1);
}
.form-label {
    font-weight: 600;
}

/* ✅ Compact Select2 box */
.select2-container--default .select2-selection--single {
    height: 34px;
    padding: 4px 10px;
    font-size: 14px;
    border-radius: 5px;
    line-height: 1.2;
}
.select2-container--default .select2-selection--single .select2-selection__rendered {
    line-height: 30px;
    padding-left: 0;
}
.select2-container--default .select2-selection--single .select2-selection__arrow {
    height: 32px;
}
</style>

<div class="content-area">
    <div class="sticky-dashboard-bar px-4 py-3 mb-4 d-flex justify-content-between align-items-center">
        <h2 class="m-0 text-white fw-bold">Edit Quiz</h2>
        <a href="quizzes.php" class="btn btn-light text-dark"><i class="bi bi-arrow-left"></i> Back</a>
    </div>

    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger">
            <?= htmlspecialchars($_SESSION['error']) ?>
            <?php unset($_SESSION['error']); ?>
        </div>
    <?php endif; ?>

    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success">
            <?= htmlspecialchars($_SESSION['success']) ?>
            <?php unset($_SESSION['success']); ?>
        </div>
    <?php endif; ?>

    <div class="p-4 bg-white rounded shadow-sm">
        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="id" value="<?= $quiz['id'] ?>">
            <input type="hidden" name="existing_icon" value="<?= $quiz['icon'] ?>">

            <div class="mb-3">
                <label class="form-label">Quiz Title</label>
                <input type="text" name="title" class="form-control" value="<?= htmlspecialchars($quiz['title']) ?>" required>
            </div>

            <div class="mb-3">
                <label class="form-label">Current Icon</label><br>
                <?php if ($quiz['icon']): ?>
                    <img src="uploads/quiz_icons/<?= $quiz['icon'] ?>" alt="Quiz Icon" style="height: 64px;">
                <?php else: ?>
                    <em>No icon uploaded.</em>
                <?php endif; ?>
            </div>

            <div class="mb-3">
                <label class="form-label">Change Icon</label>
                <input type="file" name="icon" class="form-control" accept="image/*">
            </div>

            <div class="mb-3">
                <label class="form-label">Category</label>
                <select name="category_id" class="form-select" required>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?= $cat['id'] ?>" <?= $cat['id'] == $quiz['category_id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($cat['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="mb-3">
                <label class="form-label">Selected Questions</label><br>
                <a href="question_picker.php?mode=edit&id=<?= $quiz['id'] ?>" class="btn btn-outline-primary btn-sm">
                    Select Questions
                </a>
                <?php
                $stmt = $conn->prepare("SELECT COUNT(*) FROM quiz_questions WHERE quiz_id = ?");
                $stmt->execute([$quiz['id']]);
                $count = $stmt->fetchColumn();
                ?>
                <small class="text-muted ms-2">You selected <?= $count ?> question(s).</small>
            </div>

            <div class="mb-3">
                <label class="form-label">Status</label>
                <select name="status" class="form-select" required>
                    <option value="Published" <?= $quiz['status'] == 'Published' ? 'selected' : '' ?>>Published</option>
                    <option value="Draft" <?= $quiz['status'] == 'Draft' ? 'selected' : '' ?>>Draft</option>
                </select>
            </div>

            <button class="btn btn-primary">Update Quiz</button>
        </form>
    </div>
</div>

<!-- ✅ jQuery + Select2 -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<!-- ✅ Enhanced Select2 Init -->
<script>
$(document).ready(function () {
    $('select[name="category_id"]').select2({
        width: '100%',
        dropdownAutoWidth: true,
        dropdownParent: $('.content-area'),
        placeholder: "Select a category",
        allowClear: true,
        language: {
            searching: function () {
                return "Searching...";
            },
            inputTooShort: function () {
                return "Type more to search";
            }
        }
    }).on('select2:open', function () {
        let searchBox = document.querySelector('.select2-container--open .select2-search__field');
        if (searchBox) {
            searchBox.focus();
            searchBox.placeholder = "Search categories...";
        }
    });
});
</script>

<?php include 'inc/footer.php'; ?>
