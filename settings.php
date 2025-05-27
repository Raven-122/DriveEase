<?php
session_start();
include 'include/db.php';  // Make sure this file has PDO connection as $conn

if (!isset($_SESSION['admin_id'])) {
    header("Location: index.php");
    exit();
}

// UPDATE WEBSITE SETTINGS
if (isset($_POST['update_settings'])) {
    $website_name = trim($_POST['website_name']);
    $website_url = trim($_POST['website_url']);
    $website_small_description = trim($_POST['website_small_description']);

    try {
        // Check if settings exist
        $check = $conn->query("SELECT id FROM settings LIMIT 1");
        $exists = $check->fetch(PDO::FETCH_ASSOC);

        if ($exists) {
            // Update existing settings
            $stmt = $conn->prepare("UPDATE settings SET 
                website_name = :name, 
                website_url = :url, 
                website_small_description = :description 
                WHERE id = 1");
        } else {
            // Insert new settings
            $stmt = $conn->prepare("INSERT INTO settings 
                (website_name, website_url, website_small_description) 
                VALUES (:name, :url, :description)");
        }

        $stmt->execute([
            ':name' => $website_name,
            ':url' => $website_url,
            ':description' => $website_small_description
        ]);

        $_SESSION['success'] = "Settings updated successfully!";
    } catch (PDOException $e) {
        $_SESSION['error'] = "Error updating settings: " . $e->getMessage();
    }

    header("Location: settings.php");
    exit();
}

// FETCH CURRENT SETTINGS
try {
    $stmt = $conn->query("SELECT * FROM settings LIMIT 1");
    $setting = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $_SESSION['error'] = "Error fetching settings: " . $e->getMessage();
    $setting = [];
}
?>

<?php include 'inc/header.php'; ?>
<?php include 'inc/sidebar.php'; ?>

<div class="content-area p-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="m-0"><i class="bi bi-gear me-2"></i>Website Settings</h2>
    </div>

    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <?php 
                echo $_SESSION['success'];
                unset($_SESSION['success']);
            ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <?php 
                echo $_SESSION['error'];
                unset($_SESSION['error']);
            ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="row">
        <div class="col-lg-8">
            <form method="POST">
                <div class="card shadow-sm">
                    <div class="card-header bg-white py-3">
                        <h5 class="card-title m-0">General Settings</h5>
                    </div>
                    <div class="card-body">
                        <div class="setting-section mb-4">
                            <label class="form-label fw-bold">Website Name</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-building"></i></span>
                                <input type="text" name="website_name" class="form-control" 
                                    value="<?php echo htmlspecialchars($setting['website_name'] ?? ''); ?>" 
                                    placeholder="Enter website name" required>
                            </div>
                        </div>
                        
                        <div class="setting-section mb-4">
                            <label class="form-label fw-bold">Website URL</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-link-45deg"></i></span>
                                <input type="text" name="website_url" class="form-control" 
                                    value="<?php echo htmlspecialchars($setting['website_url'] ?? ''); ?>" 
                                    placeholder="Enter website URL" required>
                            </div>
                        </div>
                        
                        <div class="setting-section">
                            <label class="form-label fw-bold">Website Description</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-file-text"></i></span>
                                <textarea name="website_small_description" class="form-control" 
                                    rows="4" placeholder="Enter website description" 
                                    required><?php echo htmlspecialchars($setting['website_small_description'] ?? ''); ?></textarea>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer bg-white py-3">
                        <button type="submit" name="update_settings" class="btn btn-primary px-4">
                            <i class="bi bi-save me-2"></i>Save Changes
                        </button>
                    </div>
                </div>
            </form>
        </div>
        
        <div class="col-lg-4">
            <div class="card shadow-sm">
                <div class="card-header bg-white py-3">
                    <h5 class="card-title m-0">Quick Tips</h5>
                </div>
                <div class="card-body">
                    <div class="tips-list">
                        <div class="tip-item mb-3">
                            <i class="bi bi-info-circle text-primary me-2"></i>
                            <span>Keep your website name clear and memorable</span>
                        </div>
                        <div class="tip-item mb-3">
                            <i class="bi bi-link text-primary me-2"></i>
                            <span>Use a complete URL including https://</span>
                        </div>
                        <div class="tip-item">
                            <i class="bi bi-pencil text-primary me-2"></i>
                            <span>Write a concise but informative description</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.setting-section label {
    color: #2c3e50;
    margin-bottom: 0.5rem;
}
.input-group-text {
    background-color: #f8f9fa;
    border-right: none;
}
.input-group .form-control {
    border-left: none;
}
.input-group .form-control:focus {
    border-color: #dee2e6;
    box-shadow: none;
}
.card {
    border: 1px solid rgba(0,0,0,.08);
}
.card-header {
    border-bottom: 1px solid rgba(0,0,0,.08);
}
.tip-item {
    display: flex;
    align-items: start;
    font-size: 0.9rem;
    color: #6c757d;
}
</style>

<?php include 'inc/footer.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
