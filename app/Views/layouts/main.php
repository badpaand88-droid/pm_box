<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($page_title ?? 'PM Box') ?> - PM Box</title>
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
    <?php if (Auth::check()): ?>
    <nav class="navbar">
        <div class="navbar-brand">
            <a href="/dashboard">PM Box</a>
        </div>
        
        <div class="navbar-search">
            <input type="text" id="search-input" placeholder="Search tasks & projects..." autocomplete="off">
            <div id="search-results" class="search-results"></div>
        </div>
        
        <div class="navbar-menu">
            <a href="/projects" class="nav-link">Projects</a>
            
            <div class="dropdown">
                <button class="dropdown-toggle" id="notifications-btn">
                    🔔 <span id="notification-badge" class="badge" style="display: none;">0</span>
                </button>
                <div class="dropdown-menu" id="notifications-dropdown">
                    <div class="dropdown-header">Notifications</div>
                    <div id="notifications-list">Loading...</div>
                    <div class="dropdown-footer">
                        <button onclick="markAllNotificationsRead()">Mark all as read</button>
                    </div>
                </div>
            </div>
            
            <div class="dropdown">
                <button class="dropdown-toggle">
                    <?= htmlspecialchars($user['full_name']) ?>
                </button>
                <div class="dropdown-menu">
                    <div class="dropdown-header"><?= htmlspecialchars($user['email']) ?></div>
                    <a href="/profile" class="dropdown-item">Profile</a>
                    <a href="/logout" class="dropdown-item">Logout</a>
                </div>
            </div>
        </div>
    </nav>
    <?php endif; ?>
    
    <main class="main-content">
        <?php if (isset($flash_success)): ?>
        <div class="alert alert-success">
            <?= htmlspecialchars($flash_success) ?>
            <button class="alert-close" onclick="this.parentElement.remove()">×</button>
        </div>
        <?php endif; ?>
        
        <?php if (isset($flash_error)): ?>
        <div class="alert alert-danger">
            <?= htmlspecialchars($flash_error) ?>
            <button class="alert-close" onclick="this.parentElement.remove()">×</button>
        </div>
        <?php endif; ?>
        
        <?= $content ?? '' ?>
    </main>
    
    <script src="/assets/js/app.js"></script>
    <script>
        const csrfToken = '<?= $csrf_token ?>';
    </script>
</body>
</html>
