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
        <div class="stat-card">
            <div class="stat-value"><a href="/export" style="color: inherit;">📥 Экспорт</a></div>
            <div class="stat-label">Excel Export</div>
        </div>
    </div>
    
    <!-- Team Workload Chart -->
    <div class="dashboard-section" style="margin-top: 1.5rem;">
        <div class="section-header">
            <h2>Загрузка команды</h2>
        </div>
        <div class="workload-chart" style="background: var(--surface); padding: 1.5rem; border-radius: var(--radius-lg); box-shadow: var(--shadow);">
            <?php if (!empty($team_workload)): ?>
            <div class="workload-bars" style="display: flex; flex-direction: column; gap: 0.75rem;">
                <?php foreach ($team_workload as $member): 
                    $tasks = (int)$member['active_tasks'];
                    $color = $tasks <= 5 ? '#22c55e' : ($tasks <= 7 ? '#f59e0b' : '#ef4444');
                    $width = min($tasks * 10, 100);
                ?>
                <div class="workload-item">
                    <div style="display: flex; justify-content: space-between; margin-bottom: 0.25rem;">
                        <span style="font-weight: 500;"><?= htmlspecialchars($member['full_name']) ?></span>
                        <span style="color: var(--text-secondary);"><?= $tasks ?> задач</span>
                    </div>
                    <div style="background: var(--background); border-radius: var(--radius); height: 24px; overflow: hidden;">
                        <div style="background: <?= $color ?>; width: <?= $width ?>%; height: 100%; transition: width 0.3s;"></div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php else: ?>
            <p class="empty-state">Нет данных о загрузке команды</p>
            <?php endif; ?>
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
