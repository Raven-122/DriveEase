<?php
session_start();
include 'include/db.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: index.php");
    exit();
}

$mode = $_GET['mode'] ?? 'new';
$quiz_id = $_GET['id'] ?? 0;
$back_url = ($mode === 'edit') 
    ? "quiz_edit.php?id=$quiz_id" 
    : "quizzes.php?selected_questions=" . implode(',', $_SESSION['new_quiz_selected_questions'] ?? []);

// Store the form data in session if it was passed
if ($mode === 'new' && !empty($_GET['form_data'])) {
    $_SESSION['quiz_form_data'] = json_decode(urldecode($_GET['form_data']), true);
}

$questions = $conn->query("SELECT * FROM questions ORDER BY question_text ASC")->fetchAll(PDO::FETCH_ASSOC);
$categories = $conn->query("SELECT * FROM categories ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);

$session_key = $mode === 'edit' ? 'edit_quiz_selected_questions' : 'new_quiz_selected_questions';
$selected_ids = $_SESSION[$session_key] ?? [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $selected = $_POST['selected_ids'] ?? [];
    
    // Validate that selected questions exist
    if (!empty($selected)) {
        $placeholders = str_repeat('?,', count($selected) - 1) . '?';
        $stmt = $conn->prepare("SELECT COUNT(*) FROM questions WHERE id IN ($placeholders)");
        $stmt->execute($selected);
        $count = $stmt->fetchColumn();
        
        if ($count !== count($selected)) {
            $_SESSION['error'] = "Some selected questions no longer exist";
            header("Location: $back_url");
            exit();
        }
    }
    
    $_SESSION[$session_key] = $selected;
    $_SESSION['success'] = "Questions successfully selected";
    header("Location: $back_url");
    exit();
}
?>

<?php include 'inc/header.php'; ?>
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
.question-column {
    flex: 1;
    background: #ffffff;
    border: 1px solid #b5c7d3;
    border-radius: 12px;
    padding: 20px;
    box-shadow: 0 4px 10px rgba(0,0,0,0.04);
    max-height: 600px;
    overflow-y: auto;
}
.question-grid-container {
    display: flex;
    flex-direction: column;
    gap: 15px;
}
.question-card {
    background-color: #ffffff;
    border-radius: 10px;
    padding: 18px 20px;
    box-shadow: 0 1px 4px rgba(0,0,0,0.08);
    display: flex;
    align-items: center;
    justify-content: space-between;
    cursor: grab;
    transition: background-color 0.3s, color 0.3s;
}
.question-text {
    font-size: 14px;
    color: #333;
    font-weight: 500;
    flex: 1;
}
.question-card:hover {
    background-color: #2e5f80 !important;
    color: #ffffff;
}
.question-card:hover .question-text {
    color: #ffffff;
}
.card-flash {
    background-color: #fff3cd !important;
    transition: background-color 0.6s ease;
}
</style>

<div class="content-area">
    <div class="sticky-dashboard-bar px-4 py-3 mb-4 d-flex justify-content-between align-items-center">
        <h2 class="m-0 text-white fw-bold">Questions</h2>
        <a href="<?= $back_url ?>" class="btn btn-light text-dark"><i class="bi bi-arrow-left"></i> Back</a>
    </div>

    <form method="POST">
        <div class="mb-4">
            <div class="row">
                <!-- Unselected -->
                <div class="col-md-6 question-column">
                    <div class="mb-3 d-flex gap-2 align-items-center">
                        <select id="filterCategory" class="form-select">
                            <option value="all">All Categories</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <input type="text" id="searchInput" class="form-control" placeholder="Search questions...">
                    </div>

                    <h5 style="color:#2e5f80;" class="fw-bold mb-3">Available Questions</h5>
                    <div id="unselected" class="question-grid-container">
                        <?php foreach ($questions as $q): ?>
                            <?php if (!in_array($q['id'], $selected_ids)): ?>
                            <div class="question-card" 
                                 data-id="<?= $q['id'] ?>" 
                                 data-category="<?= $q['category_id'] ?>"
                                 data-text="<?= strtolower($q['question_text']) ?>"
                                 ondblclick="selectQuestion(<?= $q['id'] ?>)">
                                <div class="question-text"><?= htmlspecialchars($q['question_text']) ?></div>
                            </div>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Selected -->
                <div class="col-md-6 question-column">
                    <h5 style="color:#2e5f80;" class="fw-bold mb-3">Selected Questions (Drag or Double Click to Remove)</h5>
                    <div id="selected" class="question-grid-container">
                        <?php foreach ($questions as $q): ?>
                            <?php if (in_array($q['id'], $selected_ids)): ?>
                            <div class="question-card" 
                                 data-id="<?= $q['id'] ?>" 
                                 ondblclick="unselectQuestion(<?= $q['id'] ?>)">
                                <input type="hidden" name="selected_ids[]" value="<?= $q['id'] ?>">
                                <div class="question-text"><?= htmlspecialchars($q['question_text']) ?></div>
                            </div>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="mt-4">
            <button type="submit" class="btn btn-success">Save Selected Questions</button>
        </div>
    </form>
</div>

<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
<script>
Sortable.create(document.getElementById('selected'), {
    group: 'questions',
    animation: 150,
    onAdd: updateOrderInputs,
    onSort: updateOrderInputs
});

Sortable.create(document.getElementById('unselected'), {
    group: 'questions',
    animation: 150,
    onAdd: function () {
        const card = this.el.querySelector('.question-card:last-child');
        if (card) card.querySelector('input[type="hidden"]')?.remove();
    }
});

function updateOrderInputs() {
    const container = document.getElementById('selected');
    const cards = container.querySelectorAll('.question-card');
    container.querySelectorAll('input[name="selected_ids[]"]').forEach(i => i.remove());

    cards.forEach(card => {
        const id = card.getAttribute('data-id');
        const hidden = document.createElement('input');
        hidden.type = 'hidden';
        hidden.name = 'selected_ids[]';
        hidden.value = id;
        card.appendChild(hidden);
    });
}

function flashCard(card) {
    card.classList.add('card-flash');
    setTimeout(() => card.classList.remove('card-flash'), 600);
}

function selectQuestion(id) {
    const card = document.querySelector(`#unselected .question-card[data-id='${id}']`);
    if (!card) return;

    document.getElementById('selected').appendChild(card);
    card.setAttribute('ondblclick', `unselectQuestion(${id})`);

    const hidden = document.createElement('input');
    hidden.type = 'hidden';
    hidden.name = 'selected_ids[]';
    hidden.value = id;
    card.appendChild(hidden);

    flashCard(card);
    updateOrderInputs();
}

function unselectQuestion(id) {
    const card = document.querySelector(`#selected .question-card[data-id='${id}']`);
    if (!card) return;

    card.querySelector('input[type="hidden"]')?.remove();
    document.getElementById('unselected').appendChild(card);
    card.setAttribute('ondblclick', `selectQuestion(${id})`);

    flashCard(card);
}

document.getElementById('filterCategory').addEventListener('change', filterQuestions);
document.getElementById('searchInput').addEventListener('input', filterQuestions);

function filterQuestions() {
    const category = document.getElementById('filterCategory').value;
    const keyword = document.getElementById('searchInput').value.toLowerCase();
    const cards = document.querySelectorAll('#unselected .question-card');

    cards.forEach(card => {
        const cardCat = card.getAttribute('data-category');
        const text = card.getAttribute('data-text');
        const matchesCategory = category === 'all' || cardCat === category;
        const matchesText = text.includes(keyword);
        card.style.display = (matchesCategory && matchesText) ? '' : 'none';
    });
}
</script>

<?php include 'inc/footer.php'; ?>
