<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start(); // prevents "session already active" warning
}
if (!isset($_SESSION['admin_id'])) {
    header("Location: ../index.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>DriveEase Admin Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet" />
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet" />
    <style>
        body {
            margin: 0;
            padding: 0;
            font-family: 'Arial', sans-serif;
        }
        .layout {
            display: absolute;
            overflow: hidden;

            .sidebar {
        transition: all 0.3s ease;
        transform: translateX(-250px); /* hidden off-screen */
        }
.sidebar.active {
  transform: translateX(0);
}
        }
        .sidebar-box {
            width: 260px;
            background-color: #f8f9fa;
            border-radius: 0 15px 15px 0;
            box-shadow: 2px 0 10px rgba(0, 0, 0, 0.1);
            position: fixed;
            top: 0;
            left: -260px; /* Start hidden */
            height: 100vh;
            overflow-y: auto;
            z-index: 1000;
            transition: all 0.3s ease;
        }
        /* Visible sidebar */
        .sidebar-box.visible {
            left: 0;
        }
        .content-area {
            margin-left: 0;
            transition: margin-left 0.3s ease;
        }
        .content-area.shifted {
            margin-left: 260px;
        }
        .sidebar-nav .nav-link {
            font-size: 18px;
            padding: 15px 20px;
            display: flex;
            align-items: center;
            gap: 12px;
            color: #333;
            text-decoration: none;
            border-radius: 8px;
            transition: background-color 0.3s, color 0.3s;
            white-space: nowrap;
        }
        .sidebar-nav .nav-link i {
            font-size: 24px;
        }
        .sidebar-nav .nav-link:hover,
        .sidebar-nav .nav-link.active {
            background-color: #2e5f80;
            color: white;
        }
        .sidebar-nav .nav-item {
            margin-bottom: 10px;
        }
        .text-center img {
            margin-bottom: 10px;
        }

       
       #sidebarToggle {
    position: fixed;
    top: 15px;
    left: 15px;
    z-index: 1100;
     background-color: #2d4f6c;
    border-color:rgb(10, 50, 102);
    color: white; 
}
    </style>
</head>
<body>
    <div class="sidebar-box">
        <div class="text-center py-4">
            <img src="logo.png" alt="DriveEase Logo" width="130" />
            <h4>DriveEase</h4>
            
        </div>
        <ul class="nav flex-column sidebar-nav px-2">
            <li class="nav-item">
                <a class="nav-link <?php if(basename($_SERVER['PHP_SELF']) == 'dashboard.php') echo 'active'; ?>" href="dashboard.php">
                    <i class="bi bi-grid"></i> Dashboard
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php if(basename($_SERVER['PHP_SELF']) == 'categories.php') echo 'active'; ?>" href="categories.php">
                    <i class="bi bi-folder"></i> Categories
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php if(basename($_SERVER['PHP_SELF']) == 'quizzes.php') echo 'active'; ?>" href="quizzes.php">
                    <i class="bi bi-clipboard"></i> Quizzes
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php if(basename($_SERVER['PHP_SELF']) == 'questions.php') echo 'active'; ?>" href="questions.php">
                    <i class="bi bi-question-circle"></i> Questions
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php if(basename($_SERVER['PHP_SELF']) == 'flashcards.php') echo 'active'; ?>" href="flashcards.php">
                    <i class="bi bi-card-text"></i> Flashcards
                </a>
            
            <li class="nav-item">
                <a class="nav-link <?php if(basename($_SERVER['PHP_SELF']) == 'users.php') echo 'active'; ?>" href="users.php">
                    <i class="bi bi-people"></i> Users
                </a>
            </li>
            
            <li class="nav-item">
                <a class="nav-link text-danger" href="logout.php">
                    <i class="bi bi-box-arrow-right"></i> Logout
                </a>
            </li>
        </ul>
    </div>


        <!-- Sidebar toggle button -->
        <button id="sidebarToggle" class="btn btn-primary">
            <i class="bi bi-list"></i>
        </button>

    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const toggleBtn = document.getElementById('sidebarToggle');
        const sidebar = document.querySelector('.sidebar-box');
        const contentAreas = document.querySelectorAll('.content-area');
        
        // Initialize - sidebar hidden by default
        sidebar.classList.remove('visible');
        contentAreas.forEach(area => area.classList.remove('shifted'));
        
        toggleBtn.addEventListener('click', () => {
            sidebar.classList.toggle('visible');
            contentAreas.forEach(area => area.classList.toggle('shifted'));
        });
    });
</script>

</body>
</html>