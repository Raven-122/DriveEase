<?php
session_start();
include 'include/db.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: index.php");
    exit();
}

// Summary counts
function getCount($table, $conn) {
    $stmt = $conn->query("SELECT COUNT(*) as total FROM $table");
    $row = $stmt->fetch();
    return $row['total'];
}

$total_users = getCount('users', $conn);
$total_quizzes = getCount('quizzes', $conn);
$total_questions = getCount('questions', $conn);
$total_flashcards = getCount('flashcards', $conn);
$total_notifications = getCount('notifications', $conn);
$total_categories = getCount('categories', $conn);

// Global Accuracy - Calculating against total questions in each quiz
$stmt = $conn->query("
    SELECT 
        SUM(qr.score) AS total_score,
        SUM(
            (SELECT COUNT(*) FROM quiz_questions WHERE quiz_id = q.id)
        ) AS total_possible_score
    FROM quiz_results qr
    JOIN quizzes q ON qr.quiz_id = q.id
");
$global = $stmt->fetch();
$global_accuracy = ($global && $global['total_possible_score'] > 0) 
    ? round(($global['total_score'] / $global['total_possible_score']) * 100, 2) 
    : 0;

// Top Accurate Users
$stmt = $conn->query("
    SELECT 
        u.username, 
        SUM(qr.score) AS total_score,
        SUM((SELECT COUNT(*) FROM quiz_questions WHERE quiz_id = q.id)) AS total_possible_score,
        ROUND((SUM(qr.score) / SUM((SELECT COUNT(*) FROM quiz_questions WHERE quiz_id = q.id)))*100, 2) AS accuracy
    FROM quiz_results qr
    JOIN users u ON qr.user_id = u.id
    JOIN quizzes q ON qr.quiz_id = q.id
    GROUP BY u.id, u.username
    HAVING total_possible_score > 0
    ORDER BY accuracy DESC 
    LIMIT 5
");
$top_users = $stmt->fetchAll();

// Quiz Accuracy (Top 5)
$stmt = $conn->query("
    SELECT 
        q.title,
        ROUND((SUM(qr.score) / (COUNT(*) * (SELECT COUNT(*) FROM quiz_questions WHERE quiz_id = q.id)))*100, 2) AS accuracy
    FROM quiz_results qr
    JOIN quizzes q ON qr.quiz_id = q.id
    GROUP BY q.id, q.title
    ORDER BY q.id DESC 
    LIMIT 5
");
$quiz_accuracies = $stmt->fetchAll();

// Quiz Status Pie
$stmt = $conn->query("SELECT status, COUNT(*) as count FROM quizzes GROUP BY status");
$quiz_status_data = [];
while ($row = $stmt->fetch()) {
    $quiz_status_data[] = ['status' => $row['status'], 'count' => $row['count']];
}

// Quiz Growth Line
$stmt = $conn->query("SELECT DATE(created_at) as date, COUNT(*) as count FROM quizzes GROUP BY DATE(created_at) ORDER BY date ASC LIMIT 14");
$quizzes_by_date = $stmt->fetchAll();

// Most Taken Quizzes
$stmt = $conn->query("
    SELECT 
        q.title,
        COUNT(qr.id) as attempts,
        AVG(qr.score) as avg_score
    FROM quizzes q
    LEFT JOIN quiz_results qr ON q.id = qr.quiz_id
    GROUP BY q.id, q.title
    ORDER BY attempts DESC 
    LIMIT 5
");
$most_taken = $stmt->fetchAll();

// User Quiz Attempts
$stmt = $conn->query("
    SELECT 
        u.username,
        COUNT(qr.id) as total_attempts,
        COUNT(DISTINCT qr.quiz_id) as unique_quizzes,
        ROUND(AVG(qr.score), 2) as avg_score
    FROM users u
    LEFT JOIN quiz_results qr ON u.id = qr.user_id
    GROUP BY u.id, u.username
    ORDER BY total_attempts DESC
    LIMIT 5
");
$user_attempts = $stmt->fetchAll();

// Daily Active Users
$stmt = $conn->query("SELECT DATE(login_time) as day, COUNT(DISTINCT user_id) as count FROM user_logins
GROUP BY DATE(login_time) ORDER BY day DESC LIMIT 7");
$daily_active = $stmt->fetchAll();
?>

<?php include 'inc/header.php'; ?>
<?php include 'inc/sidebar.php'; ?>

<div class="content-area p-4">
<div class="sticky-dashboard-bar px-4 py-3 mb-4">
    <h2 class="m-0 text-white fw-bold">Dashboard</h2>
</div>

    <!-- Cards -->
    <div class="row g-4 mb-4">
        <?php
        $stats = [
            ['icon' => 'bi-people', 'label' => 'Users', 'value' => $total_users],
            ['icon' => 'bi-clipboard', 'label' => 'Quizzes', 'value' => $total_quizzes],
            ['icon' => 'bi-question-circle', 'label' => 'Questions', 'value' => $total_questions],
            ['icon' => 'bi-folder', 'label' => 'Categories', 'value' => $total_categories],
            ['icon' => 'bi-card-text', 'label' => 'Flashcards', 'value' => $total_flashcards],
            ['icon' => 'bi-graph-up', 'label' => 'Global Accuracy', 'value' => $global_accuracy . '%'],
        ];
        foreach ($stats as $stat): ?>
        <div class="col-md-4">
            <div class="modern-card d-flex align-items-center justify-content-between px-4 py-3">
                <div class="icon-circle"><i class="bi <?= $stat['icon'] ?>"></i></div>
                <div class="text-end">
                <div class="fs-3 fw-bold" id="stat-<?= strtolower($stat['label']) ?>"><?= $stat['value'] ?></div>
                    <div class="text-muted"><?= $stat['label'] ?></div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- User Activity Tables -->
    <div class="row g-4 mb-4">
        <div class="col-md-12">
            <div class="card p-4 mb-4">
                <h5 class="mb-3">User Quiz Attempts</h5>
                <table class="table table-bordered mb-0">
                    <thead>
                        <tr>
                            <th>Username</th>
                            <th>Total Attempts</th>
                            <th>Unique Quizzes</th>
                            <th>Average Score</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($user_attempts as $user): ?>
                        <tr>
                            <td><?= htmlspecialchars($user['username']) ?></td>
                            <td><?= $user['total_attempts'] ?></td>
                            <td><?= $user['unique_quizzes'] ?></td>
                            <td><?= $user['avg_score'] ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <div class="col-md-12">
            <div class="card p-4 mb-4">
                <h5 class="mb-3">Top 5 Most Accurate Users</h5>
                <table class="table table-bordered mb-0">
                    <thead><tr><th>Username</th><th>Score</th><th>Possible Score</th><th>Accuracy</th></tr></thead>
                    <tbody>
                        <?php foreach ($top_users as $u): ?>
                        <tr>
                            <td><?= htmlspecialchars($u['username']) ?></td>
                            <td><?= $u['total_score'] ?></td>
                            <td><?= $u['total_possible_score'] ?></td>
                            <td><?= $u['accuracy'] ?>%</td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Charts -->
    <div class="row g-4 mb-4">
        <div class="col-lg-8">
            <div class="card p-4">
                <h5 class="mb-3">Most Taken Quizzes</h5>
                <canvas id="mostTakenChart" height="250"></canvas>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card p-4">
                <h5 class="mb-3">Quiz Status Distribution</h5>
                <div style="height: 250px;">
                    <canvas id="quizStatusPie" style="max-height: 250px; width: 100%;"></canvas>
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card p-4">
                <h5 class="mb-3">Quiz Accuracy (Top 5)</h5>
                <canvas id="quizAccuracyChart" height="200"></canvas>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card p-4">
                <h5 class="mb-3">Quizzes Added Over Time</h5>
                <canvas id="quizGrowthChart" height="200"></canvas>
            </div>
        </div>
        <div class="col-lg-12">
            <div class="card p-4">
                <h5 class="mb-3">Daily Active Users</h5>
                <canvas id="dailyUsersChart" height="150"></canvas>
            </div>
        </div>
    </div>
</div>

<?php include 'inc/footer.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    new Chart(document.getElementById('mostTakenChart'), {
    type: 'bar',
    data: {
        labels: <?= json_encode(array_column($most_taken, 'title')) ?>,
        datasets: [{
            label: 'Attempts',
            data: <?= json_encode(array_column($most_taken, 'attempts')) ?>,
            backgroundColor: '#2e5f80',
            yAxisID: 'y'
        }, {
            label: 'Average Score',
            data: <?= json_encode(array_column($most_taken, 'avg_score')) ?>,
            backgroundColor: '#4caf50',
            yAxisID: 'y1',
            type: 'line'
        }]
    },
    options: {
        indexAxis: 'y',
        responsive: true,
        plugins: {
            legend: { display: true },
            tooltip: { 
                enabled: true,
                callbacks: {
                    label: function(context) {
                        if (context.dataset.label === 'Attempts') {
                            return `Attempts: ${context.parsed.x}`;
                        }
                        return `Avg Score: ${context.parsed.x.toFixed(1)}`;
                    }
                }
            }
        },
        scales: {
            x: {
                beginAtZero: true,
                ticks: { precision: 0 }
            },
            y: {
                beginAtZero: true,
                position: 'left',
                title: {
                    display: true,
                    text: 'Number of Attempts'
                }
            },
            y1: {
                beginAtZero: true,
                position: 'right',
                title: {
                    display: true,
                    text: 'Average Score'
                },
                grid: {
                    drawOnChartArea: false
                }
            }
        }
    }
});

new Chart(document.getElementById('quizAccuracyChart'), {
    type: 'bar',
    data: {
        labels: <?= json_encode(array_column($quiz_accuracies, 'title')) ?>,
        datasets: [{
            label: 'Accuracy (%)',
            data: <?= json_encode(array_column($quiz_accuracies, 'accuracy')) ?>,
            backgroundColor: '#2e5f80'
        }]
    },
    options: { scales: { y: { beginAtZero: true, max: 100 } } }
});

new Chart(document.getElementById('quizStatusPie'), {
    type: 'pie',
    data: {
        labels: <?= json_encode(array_column($quiz_status_data, 'status')) ?>,
        datasets: [{
            data: <?= json_encode(array_column($quiz_status_data, 'count')) ?>,
            backgroundColor: ['#2e5f80', '#57a6a1', '#f7c59f', '#ff6b6b', '#4caf50']
        }]
    }
});

new Chart(document.getElementById('quizGrowthChart'), {
    type: 'line',
    data: {
        labels: <?= json_encode(array_column($quizzes_by_date, 'date')) ?>,
        datasets: [{
            label: 'Quizzes Added',
            data: <?= json_encode(array_column($quizzes_by_date, 'count')) ?>,
            borderColor: '#2e5f80',
            fill: false
        }]
    }
});

new Chart(document.getElementById('dailyUsersChart'), {
    type: 'line',
    data: {
        labels: <?= json_encode(array_column($daily_active, 'day')) ?>,
        datasets: [{
            label: 'Active Users',
            data: <?= json_encode(array_column($daily_active, 'count')) ?>,
            borderColor: '#4caf50',
            fill: false
        }]
    }
});function updateStats() {
    fetch('ajax/get_dashboard_counts.php')
        .then(res => res.json())
        .then(data => {
            document.getElementById('stat-users').textContent = data.users;
            document.getElementById('stat-quizzes').textContent = data.quizzes;
            document.getElementById('stat-questions').textContent = data.questions;
            document.getElementById('stat-flashcards').textContent = data.flashcards;
            document.getElementById('stat-notifications').textContent = data.notifications;
            document.getElementById('stat-categories').textContent = data.categories;
            document.getElementById('stat-global accuracy').textContent = data.global_accuracy + '%';
        });
}

// Run immediately and then every 10 seconds
updateStats();
setInterval(updateStats, 10000);

</script>

<style>
.modern-card {
    background: #fff;
    border-radius: 15px;
    box-shadow: 4px 4px 8px rgba(0,0,0,0.05), -2px -2px 6px rgba(255,255,255,0.8);
    transition: transform 0.2s ease;
    min-height: 110px;
}
.modern-card:hover { transform: translateY(-4px); }
.icon-circle {
    width: 50px;
    height: 50px;
    background-color: #2e5f80;
    color: #fff;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 24px;
}
.dashboard-bar {
    background-color: #2e5f80;
    border-radius: 8px;
    box-shadow: 2px 2px 6px rgba(0, 0, 0, 0.1);
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
    position: relative;
}
.content-area {
    background-color: #E5F1F7;
    padding: 30px;
    border-radius: 16px;
}




</style>
