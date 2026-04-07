<?php
ob_start();
?>

<div class="dashboard">
    <h1>Dashboard</h1>
    
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-value"><?= $stats['total_projects'] ?></div>
            <div class="stat-label">Total Projects</div>
        </div>
        <div class="stat-card">
            <div class="stat-value"><?= $stats['active_projects'] ?></div>
            <div class="stat-label">Active Projects</div>
        </div>
        <div class="stat-card">
            <div class="stat-value"><?= $unread_notifications ?></div>
            <div class="stat-label">Unread Notifications</div>
        </div>
    </div>
    
    <div class="dashboard-grid">
        <div class="dashboard-section">
            <div class="section-header">
                <h2>Recent Projects</h2>
                <a href="/projects" class="btn btn-sm">View All</a>
            </div>
            
            <?php if (empty($projects)): ?>
            <p class="empty-state">No projects yet. <a href="/projects/create">Create your first project</a></p>
            <?php else: ?>
            <div class="project-list">
                <?php foreach ($projects as $project): ?>
                <div class="project-item">
                    <div class="project-info">
                        <h3><a href="/projects/<?= $project['id'] ?>"><?= htmlspecialchars($project['name']) ?></a></h3>
                        <p class="project-description"><?= htmlspecialchars(mb_substr($project['description'] ?? '', 0, 100)) ?></p>
                        <span class="badge badge-<?= $project['status'] ?>"><?= ucfirst($project['status']) ?></span>
                    </div>
                    <div class="project-stats">
                        <span><?= $project['total_tasks'] ?? 0 ?> tasks</span>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
        
        <div class="dashboard-section">
            <div class="section-header">
                <h2>Overdue Tasks</h2>
            </div>
            
            <?php if (empty($overdue_tasks)): ?>
            <p class="empty-state">No overdue tasks! 🎉</p>
            <?php else: ?>
            <div class="task-list">
                <?php foreach ($overdue_tasks as $task): ?>
                <div class="task-item task-overdue">
                    <div class="task-info">
                        <h4><a href="/tasks/<?= $task['id'] ?>"><?= htmlspecialchars($task['title']) ?></a></h4>
                        <span class="task-project"><?= htmlspecialchars($task['project_name']) ?></span>
                    </div>
                    <div class="task-meta">
                        <span class="due-date">Due: <?= date('M d', strtotime($task['due_date'])) ?></span>
                        <span class="badge badge-<?= $task['priority'] ?>"><?= ucfirst($task['priority']) ?></span>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/layouts/main.php';
?>
