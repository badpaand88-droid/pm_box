<?php
ob_start();
?>

<div class="projects-page">
    <div class="page-header">
        <h1>Projects</h1>
        <?php if (Auth::isManager() || Auth::isAdmin()): ?>
        <a href="/projects/create" class="btn btn-primary">+ New Project</a>
        <?php endif; ?>
    </div>
    
    <?php if (empty($projects)): ?>
    <div class="empty-state-large">
        <h2>No projects yet</h2>
        <p>Create your first project to get started</p>
        <?php if (Auth::isManager() || Auth::isAdmin()): ?>
        <a href="/projects/create" class="btn btn-primary">Create Project</a>
        <?php endif; ?>
    </div>
    <?php else: ?>
    <div class="projects-grid">
        <?php foreach ($projects as $project): ?>
        <div class="project-card">
            <div class="project-card-header">
                <h3><a href="/projects/<?= $project['id'] ?>"><?= htmlspecialchars($project['name']) ?></a></h3>
                <span class="badge badge-<?= $project['status'] ?>"><?= ucfirst($project['status']) ?></span>
            </div>
            
            <p class="project-description">
                <?= htmlspecialchars(mb_substr($project['description'] ?? '', 0, 150)) ?>
                <?= strlen($project['description'] ?? '') > 150 ? '...' : '' ?>
            </p>
            
            <div class="project-stats-mini">
                <span>📋 <?= $project['total_tasks'] ?? 0 ?> tasks</span>
                <span>✅ <?= $project['completed_tasks'] ?? 0 ?> done</span>
            </div>
            
            <div class="project-footer">
                <span class="project-creator">By <?= htmlspecialchars($project['creator_name'] ?? 'Unknown') ?></span>
                <span class="project-date"><?= date('M d, Y', strtotime($project['created_at'])) ?></span>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/main.php';
?>
