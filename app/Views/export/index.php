<?php
ob_start();
?>

<div class="export-page">
    <div class="page-header">
        <h1>Экспорт задач в Excel</h1>
        <a href="/dashboard" class="back-link">← Назад</a>
    </div>
    
    <div class="form-card" style="max-width: 800px;">
        <form method="POST" action="/export/excel">
            <?= CSRF::inputField() ?>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="project_id">Проект</label>
                    <select name="project_id" id="project_id">
                        <option value="">Все проекты</option>
                        <?php foreach ($projects as $project): ?>
                        <option value="<?= $project['id'] ?>"><?= htmlspecialchars($project['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="status">Статус</label>
                    <select name="status" id="status">
                        <option value="all">Все статусы</option>
                        <option value="todo">To Do</option>
                        <option value="in_progress">In Progress</option>
                        <option value="review">Review</option>
                        <option value="done">Done</option>
                        <option value="closed">Closed</option>
                    </select>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="category_id">Категория</label>
                    <select name="category_id" id="category_id">
                        <option value="all">Все категории</option>
                        <?php foreach ($categories as $category): ?>
                        <option value="<?= $category['id'] ?>"><?= htmlspecialchars($category['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="assigned_to">Ответственный</label>
                    <select name="assigned_to" id="assigned_to">
                        <option value="all">Все пользователи</option>
                        <?php foreach ($users as $user): ?>
                        <option value="<?= $user['id'] ?>"><?= htmlspecialchars($user['full_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            
            <div class="form-group">
                <label for="priority">Приоритет</label>
                <select name="priority" id="priority">
                    <option value="all">Все приоритеты</option>
                    <option value="low">Low</option>
                    <option value="medium">Medium</option>
                    <option value="high">High</option>
                    <option value="critical">Critical</option>
                </select>
            </div>
            
            <div class="form-actions">
                <button type="submit" class="btn btn-primary btn-block">
                    📥 Скачать Excel (.xlsx)
                </button>
            </div>
        </form>
    </div>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/main.php';
?>
