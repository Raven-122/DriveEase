<?php
session_start();
include 'include/db.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: index.php");
    exit();
}

$categories = $conn->query("SELECT * FROM categories ORDER BY name ASC")->fetchAll();

// Grab selected question IDs from session (if any)
// Get temporary form data if it exists
$form_data = $_SESSION['quiz_form_data'] ?? [];
$selected_questions = $_SESSION['new_quiz_selected_questions'] ?? [];

if (isset($_POST['add_quiz'])) {
    $title = $_POST['title'];
    $status = $_POST['status'];
    $category_id = $_POST['category_id'];
    $question_ids = $_POST['question_ids'] ?? [];
    
    // Clear temporary form data
    unset($_SESSION['quiz_form_data']);

    $iconName = null;
    if (!empty($_FILES['icon']['name'])) {
        $iconName = time() . '_' . basename($_FILES['icon']['name']);
        move_uploaded_file($_FILES['icon']['tmp_name'], 'uploads/quiz_icons/' . $iconName);
    }

    try {
        // Start transaction
        $conn->beginTransaction();
        
        // Insert the quiz
        $stmt = $conn->prepare("INSERT INTO quizzes (title, icon, category_id, status) VALUES (?, ?, ?, ?)");
        $stmt->execute([$title, $iconName, $_POST['category_id'], $status]);
        $quiz_id = $conn->lastInsertId();

        // Insert quiz questions
        if (!empty($question_ids)) {
            $stmt = $conn->prepare("INSERT INTO quiz_questions (quiz_id, question_id) VALUES (?, ?)");
            foreach ($question_ids as $qid) {
                $stmt->execute([$quiz_id, $qid]);
            }
        }

        // Commit the transaction
        $conn->commit();
        $_SESSION['success'] = "Quiz created successfully";
    } catch (PDOException $e) {
        // Rollback on error
        $conn->rollBack();
        $_SESSION['error'] = "Error creating quiz: " . $e->getMessage();
        header("Location: quiz_add.php");
        exit();
    }

    // Clear selected questions session
    unset($_SESSION['new_quiz_selected_questions']);

    header("Location: quizzes.php");
    exit();
}
?>

<?php include 'inc/header.php'; ?>
<div class="content-area">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Add New Quiz</h2>
        <a href="quizzes.php" class="btn btn-secondary">← Back</a>
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

    <form method="POST" enctype="multipart/form-data">
        <div class="mb-3">
            <label class="form-label">Quiz Title</label>
            <input type="text" name="title" class="form-control" value="<?= htmlspecialchars($form_data['title'] ?? '') ?>" required>
        </div>
        <div class="mb-3">
            <label class="form-label">Quiz Icon (optional)</label>
            <input type="file" name="icon" accept="image/*" class="form-control">
            <?php if (!empty($form_data['icon'])): ?>
                <small class="text-muted">Previous icon selected: <?= htmlspecialchars($form_data['icon']) ?></small>
            <?php endif; ?>
        </div>
        <div class="mb-3">
            <label class="form-label">Category</label>
            <select name="category_id" class="form-select" required>
                <option disabled <?= empty($form_data['category_id']) ? 'selected' : '' ?>>Select Category</option>
                <?php foreach ($categories as $cat): ?>
                    <option value="<?= $cat['id'] ?>" <?= ($form_data['category_id'] ?? '') == $cat['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($cat['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <!-- ✅ Question Picker -->
        <div class="mb-3">
            <label class="form-label d-block">Selected Questions</label>
            <button type="button" onclick="saveAndRedirect()" class="btn btn-outline-primary mb-2">Select Questions</button>
            <p class="text-muted">You selected <strong><?= count($selected_questions) ?></strong> question(s).</p>

            <script>
                function saveAndRedirect() {
                    const formData = {
                        title: document.querySelector('input[name="title"]').value,
                        category_id: document.querySelector('select[name="category_id"]').value,
                        status: document.querySelector('select[name="status"]').value
                    };
                    
                    const encodedData = encodeURIComponent(JSON.stringify(formData));
                    window.location.href = 'question_picker.php?mode=new&form_data=' + encodedData;
                }
            </script>

            <!-- Hidden input to carry selected question IDs -->
            <?php foreach ($selected_questions as $qid): ?>
                <input type="hidden" name="question_ids[]" value="<?= $qid ?>">
            <?php endforeach; ?>
        </div>

        <div class="mb-3">
            <label class="form-label">Status</label>
            <select name="status" class="form-select" required>
                <option value="Published" <?= ($form_data['status'] ?? '') == 'Published' ? 'selected' : '' ?>>Published</option>
                <option value="Draft" <?= ($form_data['status'] ?? '') == 'Draft' ? 'selected' : '' ?>>Draft
            </select>
        </div>
        <button type="submit" name="add_quiz" class="btn btn-success">Create Quiz</button>
    </form>
</div>
<?php include 'inc/footer.php'; ?>
