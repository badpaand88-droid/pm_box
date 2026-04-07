<?php
ob_start();
?>

<div class="project-form-page">
    <div class="page-header">
        <h1>Create New Project</h1>
        <a href="/projects" class="btn btn-secondary">Cancel</a>
    </div>
    
    <form method="POST" action="/projects/create" class="form-card">
        <?= CSRF::inputField() ?>
        
        <div class="form-group">
            <label for="name">Project Name *</label>
            <input type="text" id="name" name="name" required 
                   value="<?= htmlspecialchars($_POST['name'] ?? '') ?>"
                   placeholder="Enter project name" maxlength="255">
        </div>
        
        <div class="form-group">
            <label for="description">Description</label>
            <textarea id="description" name="description" rows="4" 
                      placeholder="Describe the project goals and scope..."><?= htmlspecialchars($_POST['description'] ?? '') ?></textarea>
        </div>
        
        <div class="form-group">
            <label for="status">Status</label>
            <select id="status" name="status">
                <option value="planning" <?= ($_POST['status'] ?? '') === 'planning' ? 'selected' : '' ?>>Planning</option>
                <option value="active" <?= ($_POST['status'] ?? '') === 'active' ? 'selected' : '' ?>>Active</option>
                <option value="on_hold" <?= ($_POST['status'] ?? '') === 'on_hold' ? 'selected' : '' ?>>On Hold</option>
                <option value="completed" <?= ($_POST['status'] ?? '') === 'completed' ? 'selected' : '' ?>>Completed</option>
                <option value="cancelled" <?= ($_POST['status'] ?? '') === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
            </select>
        </div>
        
        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Create Project</button>
        </div>
    </form>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/main.php';
?>
