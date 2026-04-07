<?php
ob_start();
?>

<div class="project-detail">
    <div class="page-header">
        <div class="page-title">
            <a href="/projects" class="back-link">← Projects</a>
            <h1><?= htmlspecialchars($project['name']) ?></h1>
        </div>
        <div class="page-actions">
            <span class="badge badge-<?= $project['status'] ?>"><?= ucfirst($project['status']) ?></span>
            <?php if (Auth::isManager() || Auth::isAdmin()): ?>
            <a href="/tasks/create/<?= $project['id'] ?>" class="btn btn-primary">+ New Task</a>
            <?php endif; ?>
        </div>
    </div>
    
    <?php if ($project['description']): ?>
    <div class="project-description-full">
        <?= nl2br(htmlspecialchars($project['description'])) ?>
    </div>
    <?php endif; ?>
    
    <div class="task-board">
        <div class="board-column">
            <div class="column-header">
                <h3>To Do</h3>
                <span class="count"><?= count($grouped_tasks['todo']) ?></span>
            </div>
            <div class="column-tasks">
                <?php foreach ($grouped_tasks['todo'] as $task): ?>
                <?= renderTaskCard($task) ?>
                <?php endforeach; ?>
            </div>
        </div>
        
        <div class="board-column">
            <div class="column-header">
                <h3>In Progress</h3>
                <span class="count"><?= count($grouped_tasks['in_progress']) ?></span>
            </div>
            <div class="column-tasks">
                <?php foreach ($grouped_tasks['in_progress'] as $task): ?>
                <?= renderTaskCard($task) ?>
                <?php endforeach; ?>
            </div>
        </div>
        
        <div class="board-column">
            <div class="column-header">
                <h3>Review</h3>
                <span class="count"><?= count($grouped_tasks['review']) ?></span>
            </div>
            <div class="column-tasks">
                <?php foreach ($grouped_tasks['review'] as $task): ?>
                <?= renderTaskCard($task) ?>
                <?php endforeach; ?>
            </div>
        </div>
        
        <div class="board-column">
            <div class="column-header">
                <h3>Done</h3>
                <span class="count"><?= count($grouped_tasks['done']) ?></span>
            </div>
            <div class="column-tasks">
                <?php foreach ($grouped_tasks['done'] as $task): ?>
                <?= renderTaskCard($task) ?>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<?php
function renderTaskCard($task) {
    $priorityColors = [
        'low' => 'badge-low',
        'medium' => 'badge-medium', 
        'high' => 'badge-high',
        'critical' => 'badge-critical'
    ];
    $priorityClass = $priorityColors[$task['priority']] ?? 'badge-medium';
    ?>
    <div class="task-card" data-task-id="<?= $task['id'] ?>">
        <div class="task-card-header">
            <span class="badge <?= $priorityClass ?>"><?= ucfirst($task['priority']) ?></span>
        </div>
        <h4><a href="/tasks/<?= $task['id'] ?>"><?= htmlspecialchars($task['title']) ?></a></h4>
        <?php if ($task['assignee_name']): ?>
        <div class="task-assignee">
            👤 <?= htmlspecialchars($task['assignee_name']) ?>
        </div>
        <?php endif; ?>
        <?php if ($task['due_date']): ?>
        <div class="task-due <?= $task['due_date'] < date('Y-m-d') && $task['status'] !== 'done' ? 'overdue' : '' ?>">
            📅 <?= date('M d', strtotime($task['due_date'])) ?>
        </div>
        <?php endif; ?>
    </div>
    <?php
}

$content = ob_get_clean();
include __DIR__ . '/../layouts/main.php';
?>
