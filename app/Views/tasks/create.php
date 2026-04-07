<?php
ob_start();
?>

<div class="task-form-page">
    <div class="page-header">
        <h1>Create New Task</h1>
        <a href="/projects/<?= $project['id'] ?>" class="btn btn-secondary">Cancel</a>
    </div>
    
    <form method="POST" action="/tasks/create" class="form-card">
        <?= CSRF::inputField() ?>
        
        <input type="hidden" name="project_id" value="<?= $project['id'] ?>">
        
        <div class="form-group">
            <label for="title">Task Title *</label>
            <input type="text" id="title" name="title" required 
                   value="<?= htmlspecialchars($_POST['title'] ?? '') ?>"
                   placeholder="Enter task title" maxlength="255">
        </div>
        
        <div class="form-group">
            <label for="description">Description</label>
            <textarea id="description" name="description" rows="6" 
                      placeholder="Describe the task in detail..."><?= htmlspecialchars($_POST['description'] ?? '') ?></textarea>
        </div>
        
        <div class="form-row">
            <div class="form-group">
                <label for="priority">Priority</label>
                <select id="priority" name="priority">
                    <option value="low" <?= ($_POST['priority'] ?? '') === 'low' ? 'selected' : '' ?>>Low</option>
                    <option value="medium" <?= ($_POST['priority'] ?? '') === 'medium' ? 'selected' : '' ?>>Medium</option>
                    <option value="high" <?= ($_POST['priority'] ?? '') === 'high' ? 'selected' : '' ?>>High</option>
                    <option value="critical" <?= ($_POST['priority'] ?? '') === 'critical' ? 'selected' : '' ?>>Critical</option>
                </select>
            </div>
            
            <div class="form-group">
                <label for="status">Status</label>
                <select id="status" name="status">
                    <option value="todo" <?= ($_POST['status'] ?? '') === 'todo' ? 'selected' : '' ?>>To Do</option>
                    <option value="in_progress" <?= ($_POST['status'] ?? '') === 'in_progress' ? 'selected' : '' ?>>In Progress</option>
                    <option value="review" <?= ($_POST['status'] ?? '') === 'review' ? 'selected' : '' ?>>Review</option>
                    <option value="done" <?= ($_POST['status'] ?? '') === 'done' ? 'selected' : '' ?>>Done</option>
                </select>
            </div>
        </div>
        
        <div class="form-row">
            <div class="form-group">
                <label for="assigned_to">Assign To</label>
                <select id="assigned_to" name="assigned_to">
                    <option value="">Unassigned</option>
                    <?php foreach ($users as $u): ?>
                    <option value="<?= $u['id'] ?>" <?= ($_POST['assigned_to'] ?? '') == $u['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($u['full_name']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label for="due_date">Due Date</label>
                <input type="date" id="due_date" name="due_date" 
                       value="<?= htmlspecialchars($_POST['due_date'] ?? '') ?>">
            </div>
        </div>
        
        <div class="form-group">
            <label for="estimated_hours">Estimated Hours</label>
            <input type="number" id="estimated_hours" name="estimated_hours" step="0.5" min="0"
                   value="<?= htmlspecialchars($_POST['estimated_hours'] ?? '') ?>"
                   placeholder="e.g., 8">
        </div>
        
        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Create Task</button>
        </div>
    </form>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/main.php';
?>
