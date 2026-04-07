<?php
ob_start();
?>

<div class="task-detail">
    <div class="page-header">
        <div class="page-title">
            <a href="/projects/<?= $task['project_id'] ?>" class="back-link">← <?= htmlspecialchars($task['project_name']) ?></a>
            <h1><?= htmlspecialchars($task['title']) ?></h1>
        </div>
        <div class="page-actions">
            <span class="badge badge-<?= $task['status'] ?>"><?= ucfirst($task['status']) ?></span>
            <span class="badge badge-<?= $task['priority'] ?>"><?= ucfirst($task['priority']) ?></span>
        </div>
    </div>
    
    <div class="task-content-grid">
        <div class="task-main">
            <div class="task-section">
                <h3>Description</h3>
                <div class="task-description">
                    <?= $task['description'] ? nl2br(htmlspecialchars($task['description'])) : '<em>No description provided.</em>' ?>
                </div>
            </div>
            
            <div class="task-section">
                <h3>Comments (<?= count($comments) ?>)</h3>
                
                <form method="POST" action="/tasks/<?= $task['id'] ?>/comment" class="comment-form">
                    <?= CSRF::inputField() ?>
                    <textarea name="content" rows="3" placeholder="Add a comment..." required></textarea>
                    <button type="submit" class="btn btn-sm btn-primary">Post Comment</button>
                </form>
                
                <div class="comments-list">
                    <?php foreach ($comments as $comment): ?>
                    <div class="comment-item">
                        <div class="comment-avatar">
                            <?= strtoupper(substr($comment['full_name'], 0, 1)) ?>
                        </div>
                        <div class="comment-content">
                            <div class="comment-header">
                                <span class="comment-author"><?= htmlspecialchars($comment['full_name']) ?></span>
                                <span class="comment-date"><?= date('M d, Y H:i', strtotime($comment['created_at'])) ?></span>
                            </div>
                            <div class="comment-text">
                                <?= nl2br(htmlspecialchars($comment['content'])) ?>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    
                    <?php if (empty($comments)): ?>
                    <p class="empty-state">No comments yet. Be the first to comment!</p>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="task-section">
                <h3>History</h3>
                <div class="task-history">
                    <?php foreach ($history as $item): ?>
                    <div class="history-item">
                        <span class="history-action"><?= htmlspecialchars($item['action']) ?></span>
                        <span class="history-user">by <?= htmlspecialchars($item['full_name']) ?></span>
                        <span class="history-date"><?= date('M d, H:i', strtotime($item['created_at'])) ?></span>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        
        <div class="task-sidebar">
            <div class="task-meta-card">
                <div class="meta-item">
                    <label>Assignee</label>
                    <div class="meta-value">
                        <?= $task['assignee_name'] ?? 'Unassigned' ?>
                    </div>
                </div>
                
                <div class="meta-item">
                    <label>Reporter</label>
                    <div class="meta-value">
                        <?= htmlspecialchars($task['creator_name']) ?>
                    </div>
                </div>
                
                <div class="meta-item">
                    <label>Due Date</label>
                    <div class="meta-value <?= $task['due_date'] && $task['due_date'] < date('Y-m-d') && $task['status'] !== 'done' ? 'overdue' : '' ?>">
                        <?= $task['due_date'] ? date('M d, Y', strtotime($task['due_date'])) : 'Not set' ?>
                    </div>
                </div>
                
                <div class="meta-item">
                    <label>Time Tracking</label>
                    <div class="meta-value">
                        <?= $task['estimated_hours'] ? $task['estimated_hours'] . 'h est.' : 'No estimate' ?>
                        <?= $task['actual_hours'] ? ' / ' . $task['actual_hours'] . 'h actual' : '' ?>
                    </div>
                </div>
                
                <div class="meta-item">
                    <label>Created</label>
                    <div class="meta-value">
                        <?= date('M d, Y', strtotime($task['created_at'])) ?>
                    </div>
                </div>
            </div>
            
            <?php if (Auth::isManager() || Auth::isAdmin() || $task['created_by'] == Auth::id()): ?>
            <div class="task-actions-card">
                <h4>Quick Actions</h4>
                <button class="btn btn-sm btn-secondary" onclick="editTaskStatus()">Change Status</button>
                <button class="btn btn-sm btn-secondary" onclick="editTaskAssignee()">Reassign</button>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
function editTaskStatus() {
    const statuses = ['todo', 'in_progress', 'review', 'done'];
    const current = '<?= $task['status'] ?>';
    let html = '<select id="new-status">';
    statuses.forEach(s => {
        html += `<option value="${s}" ${s === current ? 'selected' : ''}>${s.replace('_', ' ').toUpperCase()}</option>`;
    });
    html += '</select>';
    
    if (confirm('Change status to: ' + html)) {
        // Implementation would go here
    }
}
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/main.php';
?>
