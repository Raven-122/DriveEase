<?php
session_start();
include 'include/db.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: index.php");
    exit();
}

// DELETE USER
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $stmt = $conn->prepare("DELETE FROM users WHERE id = :id");
    $stmt->execute(['id' => $id]);
    header("Location: users.php");
    exit();
}

// Get search and sort parameters
$search = $_GET['search'] ?? '';
$sort = $_GET['sort'] ?? 'id_desc'; // Default sort: newest first

// Define sorting options
$sort_options = [
    'id_asc' => 'id ASC',
    'id_desc' => 'id DESC',
    'username_asc' => 'username ASC',
    'username_desc' => 'username DESC',
    'email_asc' => 'email ASC',
    'email_desc' => 'email DESC',
    'role_asc' => 'role ASC',
    'role_desc' => 'role DESC',
    'status_asc' => 'status ASC',
    'status_desc' => 'status DESC'
];

$order_by = $sort_options[$sort] ?? $sort_options['id_desc'];

// Prepare the query with search and sort
$where = [];
$params = [];

if (!empty($search)) {
    $where[] = "(username LIKE :search OR email LIKE :search)";
    $params['search'] = "%$search%";
}

$where_sql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$stmt = $conn->prepare("
    SELECT * FROM users 
    $where_sql 
    ORDER BY $order_by");
$stmt->execute($params);
$all_users = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<?php include 'inc/header.php'; ?>
<?php include 'inc/sidebar.php'; ?>

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
.table {
    background: white;
    border-radius: 10px;
    overflow: hidden;
    box-shadow: 0 8px 16px rgba(0, 0, 0, 0.05);
}
.table thead {
    background-color: #2e5f80;
    color: white;
}
.table th {
    font-weight: 600;
    padding: 15px;
    border-bottom: none;
}
.table td {
    padding: 12px 15px;
    vertical-align: middle;
}
.form-control, .form-select {
    border-radius: 10px;
    border: 1px solid #e2e8f0;
    padding: 12px;
    height: 42px;
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
            <h2 class="m-0 text-white fw-bold">Users Management</h2>
        </div>
    </div>

    <form method="GET" class="mb-4">
        <div class="d-flex flex-wrap gap-3 align-items-start">
            <div class="search-wrapper position-relative" style="min-width: 200px; max-width: 300px;">
                <i class="bi bi-search position-absolute" style="left: 12px; top: 12px; color: #6c757d;"></i>
                <input type="text" name="search" placeholder="Search users..." value="<?= htmlspecialchars($search) ?>" 
                    class="form-control ps-4" style="height: 42px; padding-left: 35px;">
            </div>
            <div class="sort-wrapper" style="min-width: 180px; max-width: 220px;">
                <select name="sort" class="form-select" style="height: 42px;">
                    <option value="id_desc" <?= ($sort == 'id_desc') ? 'selected' : '' ?>>Newest First</option>
                    <option value="id_asc" <?= ($sort == 'id_asc') ? 'selected' : '' ?>>Oldest First</option>
                    <option value="username_asc" <?= ($sort == 'username_asc') ? 'selected' : '' ?>>Username (A-Z)</option>
                    <option value="username_desc" <?= ($sort == 'username_desc') ? 'selected' : '' ?>>Username (Z-A)</option>
                    <option value="email_asc" <?= ($sort == 'email_asc') ? 'selected' : '' ?>>Email (A-Z)</option>
                    <option value="email_desc" <?= ($sort == 'email_desc') ? 'selected' : '' ?>>Email (Z-A)</option>
                </select>
            </div>
            <div class="button-group d-flex gap-2">
                <button type="submit" class="btn btn-primary" style="height: 42px;">Apply Filters</button>
                <?php if (!empty($_GET['search']) || !empty($_GET['sort'])): ?>
                    <a href="users.php" class="btn btn-secondary" style="height: 42px;">Clear</a>
                <?php endif; ?>
            </div>
        </div>
    </form>

    <div class="table-responsive">
        <table class="table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Username</th>
                <th>Email</th>
                <th>Role</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($all_users as $row): ?>
                <tr>
                    <td><?= $row['id'] ?></td>
                    <td><?= htmlspecialchars($row['username']) ?></td>
                    <td><?= htmlspecialchars($row['email']) ?></td>
                    <td>
                        <span class="badge <?= (isset($row['role']) && $row['role'] == 'admin') ? 'bg-danger' : 'bg-info' ?>">
                            <?= htmlspecialchars(ucfirst(isset($row['role']) ? $row['role'] : 'user')) ?>
                        </span>
                    </td>
                    <td>
                        <span class="badge <?= (isset($row['status']) && $row['status'] == 'active') ? 'bg-success' : 'bg-danger' ?>">
                            <?= htmlspecialchars(ucfirst(isset($row['status']) ? $row['status'] : 'inactive')) ?>
                        </span>
                    </td>
                    <td class="text-nowrap">
                        <a href="users.php?delete=<?php echo $row['id']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Are you sure you want to delete this user? This action cannot be undone.');">
                            <i class="fas fa-trash"></i> Delete
                        </a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php include 'inc/footer.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
