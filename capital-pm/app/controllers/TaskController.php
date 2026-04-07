<?php

namespace App\Controllers;

use App\Models\Task;
use App\Models\Project;
use App\Models\User;
use App\Models\Category;
use App\Models\Notification;
use App\Models\ChangeLog;

class TaskController
{
    private Task $taskModel;
    private Project $projectModel;
    private User $userModel;
    private Category $categoryModel;
    private Notification $notificationModel;
    private ChangeLog $changeLogModel;

    public function __construct()
    {
        Auth::requireLogin();
        
        $this->taskModel = new Task();
        $this->projectModel = new Project();
        $this->userModel = new User();
        $this->categoryModel = new Category();
        $this->notificationModel = new Notification();
        $this->changeLogModel = new ChangeLog();
    }

    /**
     * Show Kanban board for project
     */
    public function kanban(int $projectId): void
    {
        $userId = Auth::id();
        
        $project = $this->projectModel->findById($projectId);
        if (!$project) {
            http_response_code(404);
            die('Project not found');
        }

        // Check access
        if (!Auth::isAdmin() && !$this->projectModel->isMember($projectId, $userId)) {
            http_response_code(403);
            die('Access denied');
        }

        $tasks = $this->taskModel->getKanbanTasks($projectId);
        $categories = $this->categoryModel->getAllForProject($projectId);
        $members = $this->projectModel->getMembers($projectId);

        require APP_PATH . '/views/task/kanban.php';
    }

    /**
     * Show task details
     */
    public function show(int $id): void
    {
        $task = $this->taskModel->findById($id);
        
        if (!$task) {
            http_response_code(404);
            die('Task not found');
        }

        // Check access
        $userId = Auth::id();
        if (!Auth::isAdmin() && !$this->projectModel->isMember($task['project_id'], $userId)) {
            http_response_code(403);
            die('Access denied');
        }

        $project = $this->projectModel->findById($task['project_id']);
        $subtasks = $this->taskModel->getSubtasks($id);
        $dependencies = $this->taskModel->getDependencies($id);
        $comments = $this->getComments($id);
        $changeLog = $this->changeLogModel->getForEntity('task', $id);
        $members = $this->projectModel->getMembers($task['project_id']);

        require APP_PATH . '/views/task/show.php';
    }

    /**
     * Create new task
     */
    public function store(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            json_error('Invalid request', 400);
        }

        require_csrf();

        $projectId = (int) post('project_id');
        $title = trim(post('title', ''));
        $description = trim(post('description', ''));
        $categoryId = (int) post('category_id') ?: null;
        $priority = post('priority', 'medium');
        $assigneeId = (int) post('assignee_id') ?: null;
        $dueDate = post('due_date', '') ?: null;
        $storyPoints = (int) post('story_points') ?: null;

        if (empty($title)) {
            json_error('Title is required');
        }

        $project = $this->projectModel->findById($projectId);
        if (!$project) {
            json_error('Project not found', 404);
        }

        // Check access
        $userId = Auth::id();
        if (!Auth::isAdmin() && !$this->projectModel->isMember($projectId, $userId)) {
            json_error('Access denied', 403);
        }

        $taskId = $this->taskModel->create([
            'title' => $title,
            'description' => $description,
            'project_id' => $projectId,
            'category_id' => $categoryId,
            'status' => 'todo',
            'priority' => $priority,
            'assignee_id' => $assigneeId,
            'reporter_id' => $userId,
            'due_date' => $dueDate,
            'story_points' => $storyPoints
        ]);

        if ($taskId) {
            // Log creation
            $this->changeLogModel->logTaskCreation($taskId, $userId, [
                'title' => $title,
                'status' => 'todo'
            ]);

            // Notify assignee
            if ($assigneeId) {
                $this->notificationModel->notifyTaskAssigned($taskId, $assigneeId, $userId);
            }

            $task = $this->taskModel->findById($taskId);
            json_success(['task' => $task, 'message' => 'Task created successfully']);
        } else {
            json_error('Failed to create task');
        }
    }

    /**
     * Update task
     */
    public function update(int $id): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            json_error('Invalid request', 400);
        }

        require_csrf();

        $task = $this->taskModel->findById($id);
        if (!$task) {
            json_error('Task not found', 404);
        }

        // Check access
        $userId = Auth::id();
        if (!Auth::isAdmin() && !$this->projectModel->isMember($task['project_id'], $userId)) {
            json_error('Access denied', 403);
        }

        $data = [];
        $fields = ['title', 'description', 'category_id', 'priority', 'assignee_id', 'due_date', 'story_points'];
        
        foreach ($fields as $field) {
            if (isset($_POST[$field])) {
                $data[$field] = $_POST[$field] === '' ? null : $_POST[$field];
            }
        }

        // Handle status change
        if (isset($_POST['status'])) {
            $oldStatus = $task['status'];
            $newStatus = $_POST['status'];
            
            if ($oldStatus !== $newStatus) {
                $this->changeLogModel->logTaskUpdate($id, $userId, 'status', $oldStatus, $newStatus);
                $this->taskModel->changeStatus($id, $newStatus);
            }
        }

        // Log other changes
        foreach ($data as $field => $value) {
            if ($task[$field] !== $value) {
                $this->changeLogModel->logTaskUpdate($id, $userId, $field, $task[$field], $value);
                
                // Notify on assignment
                if ($field === 'assignee_id' && $value) {
                    $this->notificationModel->notifyTaskAssigned($id, (int)$value, $userId);
                }
            }
        }

        if (empty($data) && !isset($_POST['status'])) {
            json_success(['message' => 'No changes made']);
        }

        if ($this->taskModel->update($id, $data)) {
            $task = $this->taskModel->findById($id);
            json_success(['task' => $task, 'message' => 'Task updated successfully']);
        } else {
            json_error('Failed to update task');
        }
    }

    /**
     * Delete task
     */
    public function delete(int $id): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            json_error('Invalid request', 400);
        }

        require_csrf();

        $task = $this->taskModel->findById($id);
        if (!$task) {
            json_error('Task not found', 404);
        }

        // Check access
        $userId = Auth::id();
        if (!Auth::isAdmin() && !$this->projectModel->isMember($task['project_id'], $userId)) {
            json_error('Access denied', 403);
        }

        // Log deletion
        $this->changeLogModel->logTaskDeletion($id, $userId, [
            'title' => $task['title'],
            'status' => $task['status']
        ]);

        if ($this->taskModel->delete($id)) {
            json_success(['message' => 'Task deleted successfully']);
        } else {
            json_error('Failed to delete task');
        }
    }

    /**
     * Move task to different column (Kanban)
     */
    public function move(int $id): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            json_error('Invalid request', 400);
        }

        require_csrf();

        $status = post('status');
        $validStatuses = array_keys(TASK_STATUSES);
        
        if (!in_array($status, $validStatuses)) {
            json_error('Invalid status');
        }

        $task = $this->taskModel->findById($id);
        if (!$task) {
            json_error('Task not found', 404);
        }

        // Check access
        $userId = Auth::id();
        if (!Auth::isAdmin() && !$this->projectModel->isMember($task['project_id'], $userId)) {
            json_error('Access denied', 403);
        }

        if ($this->taskModel->changeStatus($id, $status)) {
            // Log change
            if ($task['status'] !== $status) {
                $this->changeLogModel->logTaskUpdate($id, $userId, 'status', $task['status'], $status);
            }

            json_success(['message' => 'Task moved successfully']);
        } else {
            json_error('Failed to move task');
        }
    }

    /**
     * Get comments for task
     */
    private function getComments(int $taskId): array
    {
        $db = \Database::getInstance();
        $stmt = $db->prepare("
            SELECT c.*, u.first_name, u.last_name, u.avatar
            FROM task_comments c
            INNER JOIN users u ON c.user_id = u.id
            WHERE c.task_id = ? AND c.parent_comment_id IS NULL
            ORDER BY c.created_at ASC
        ");
        $stmt->execute([$taskId]);
        $comments = $stmt->fetchAll();

        // Get replies
        foreach ($comments as &$comment) {
            $stmt = $db->prepare("
                SELECT c.*, u.first_name, u.last_name, u.avatar
                FROM task_comments c
                INNER JOIN users u ON c.user_id = u.id
                WHERE c.parent_comment_id = ?
                ORDER BY c.created_at ASC
            ");
            $stmt->execute([$comment['id']]);
            $comment['replies'] = $stmt->fetchAll();
        }

        return $comments;
    }

    /**
     * Add comment to task
     */
    public function addComment(int $taskId): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            json_error('Invalid request', 400);
        }

        require_csrf();

        $content = trim(post('content', ''));
        $parentId = (int) post('parent_id') ?: null;

        if (empty($content)) {
            json_error('Comment content is required');
        }

        $task = $this->taskModel->findById($taskId);
        if (!$task) {
            json_error('Task not found', 404);
        }

        // Check access
        $userId = Auth::id();
        if (!Auth::isAdmin() && !$this->projectModel->isMember($task['project_id'], $userId)) {
            json_error('Access denied', 403);
        }

        $db = \Database::getInstance();
        $stmt = $db->prepare(
            "INSERT INTO task_comments (task_id, user_id, content, parent_comment_id) VALUES (?, ?, ?, ?)"
        );
        
        if ($stmt->execute([$taskId, $userId, $content, $parentId])) {
            $commentId = $db->lastInsertId();
            
            // Log
            $this->changeLogModel->logCommentCreation($commentId, $userId, $taskId);

            // Notify task assignee
            if ($task['assignee_id'] && $task['assignee_id'] !== $userId) {
                $this->notificationModel->notifyCommentAdded($taskId, $task['assignee_id'], $userId);
            }

            // Get the comment with user info
            $stmt = $db->prepare("
                SELECT c.*, u.first_name, u.last_name, u.avatar
                FROM task_comments c
                INNER JOIN users u ON c.user_id = u.id
                WHERE c.id = ?
            ");
            $stmt->execute([$commentId]);
            $comment = $stmt->fetch();

            json_success(['comment' => $comment]);
        } else {
            json_error('Failed to add comment');
        }
    }
}
