<?php

namespace App\Controllers;

class TaskController extends BaseController
{
    public function show(int $id): void
    {
        $this->requireAuth();
        
        $taskModel = new Task();
        $commentModel = new Comment();
        
        $task = $taskModel->getWithDetails($id);
        
        if (!$task) {
            Session::setFlash('error', 'Task not found.');
            $this->redirect('/dashboard');
        }
        
        $comments = $commentModel->getByTask($id);
        $history = $this->getTaskHistory($id);
        
        $this->view('tasks/show', [
            'task' => $task,
            'comments' => $comments,
            'history' => $history
        ]);
    }
    
    private function getTaskHistory(int $taskId): array
    {
        $db = Database::getInstance();
        return $db->fetchAll("
            SELECT h.*, u.full_name
            FROM task_history h
            JOIN users u ON h.user_id = u.id
            WHERE h.task_id = :taskId
            ORDER BY h.created_at DESC
            LIMIT 20
        ", ['taskId' => $taskId]);
    }
    
    public function create(int $projectId): void
    {
        $this->requireAuth();
        
        $projectModel = new Project();
        $userModel = new User();
        
        $project = $projectModel->find($projectId);
        
        if (!$project) {
            Session::setFlash('error', 'Project not found.');
            $this->redirect('/projects');
        }
        
        $users = $userModel->getDevelopers();
        
        $this->view('tasks/create', [
            'project' => $project,
            'users' => $users
        ]);
    }
    
    public function store(): void
    {
        $this->requireAuth();
        
        if (!CSRF::validateToken($_POST['csrf_token'] ?? '')) {
            Session::setFlash('error', 'Invalid security token.');
            $this->back();
        }
        
        $validator = Validator::make($_POST, [
            'title' => 'required|min:3|max:255',
            'description' => 'max:5000',
            'priority' => 'in:low,medium,high,critical',
            'status' => 'in:todo,in_progress,review,done',
            'due_date' => 'date',
            'estimated_hours' => 'numeric'
        ]);
        
        if ($validator->fails()) {
            Session::setFlash('error', $validator->firstError());
            $this->back();
        }
        
        $taskModel = new Task();
        $notificationModel = new Notification();
        
        $data = [
            'project_id' => $_POST['project_id'],
            'title' => $_POST['title'],
            'description' => $_POST['description'] ?? '',
            'priority' => $_POST['priority'] ?? 'medium',
            'status' => $_POST['status'] ?? 'todo',
            'created_by' => Auth::id()
        ];
        
        if (!empty($_POST['assigned_to'])) {
            $data['assigned_to'] = (int) $_POST['assigned_to'];
        }
        
        if (!empty($_POST['due_date'])) {
            $data['due_date'] = $_POST['due_date'];
        }
        
        if (!empty($_POST['estimated_hours'])) {
            $data['estimated_hours'] = (float) $_POST['estimated_hours'];
        }
        
        $taskId = $taskModel->create($data);
        
        // Notify assigned user
        if (!empty($data['assigned_to']) && $data['assigned_to'] !== Auth::id()) {
            $notificationModel->notifyTaskAssigned($taskId, $data['assigned_to'], Auth::id());
        }
        
        if ($taskId) {
            Session::setFlash('success', 'Task created successfully!');
            $this->redirect("/tasks/$taskId");
        } else {
            Session::setFlash('error', 'Failed to create task.');
            $this->back();
        }
    }
    
    public function update(int $id): void
    {
        $this->requireAuth();
        
        if (!CSRF::validateToken($_POST['csrf_token'] ?? '')) {
            $this->json(['success' => false, 'message' => 'Invalid token'], 400);
        }
        
        $taskModel = new Task();
        $notificationModel = new Notification();
        
        $task = $taskModel->find($id);
        
        if (!$task) {
            $this->json(['success' => false, 'message' => 'Task not found'], 404);
        }
        
        $data = [];
        
        if (isset($_POST['title'])) {
            $data['title'] = substr(trim($_POST['title']), 0, 255);
        }
        
        if (isset($_POST['description'])) {
            $data['description'] = trim($_POST['description']);
        }
        
        if (isset($_POST['priority'])) {
            $allowedPriorities = ['low', 'medium', 'high', 'critical'];
            if (in_array($_POST['priority'], $allowedPriorities, true)) {
                $data['priority'] = $_POST['priority'];
            }
        }
        
        if (isset($_POST['status'])) {
            $allowedStatuses = ['todo', 'in_progress', 'review', 'done', 'closed'];
            if (in_array($_POST['status'], $allowedStatuses, true)) {
                $oldStatus = $task['status'];
                $data['status'] = $_POST['status'];
                
                // Notify about status change
                if ($oldStatus !== $data['status']) {
                    $notificationModel->notifyTaskStatusChange($id, Auth::id(), $data['status']);
                }
            }
        }
        
        if (isset($_POST['assigned_to'])) {
            $data['assigned_to'] = $_POST['assigned_to'] ? (int) $_POST['assigned_to'] : null;
            
            // Notify newly assigned user
            if ($data['assigned_to'] && $data['assigned_to'] !== $task['assigned_to']) {
                $notificationModel->notifyTaskAssigned($id, $data['assigned_to'], Auth::id());
            }
        }
        
        if (isset($_POST['due_date'])) {
            $data['due_date'] = $_POST['due_date'] ?: null;
        }
        
        if (isset($_POST['estimated_hours'])) {
            $data['estimated_hours'] = $_POST['estimated_hours'] ? (float) $_POST['estimated_hours'] : null;
        }
        
        if (isset($_POST['actual_hours'])) {
            $data['actual_hours'] = $_POST['actual_hours'] ? (float) $_POST['actual_hours'] : null;
        }
        
        if (!empty($data)) {
            $taskModel->update($id, $data, Auth::id());
        }
        
        $this->json(['success' => true, 'task' => $taskModel->getWithDetails($id)]);
    }
    
    public function delete(int $id): void
    {
        $this->requireAuth();
        
        $taskModel = new Task();
        $task = $taskModel->find($id);
        
        if (!$task) {
            Session::setFlash('error', 'Task not found.');
            $this->redirect('/dashboard');
        }
        
        // Check permission
        $projectModel = new Project();
        $project = $projectModel->find($task['project_id']);
        
        if (!Auth::isAdmin() && !Auth::isManager() && $task['created_by'] !== Auth::id()) {
            Session::setFlash('error', 'Permission denied.');
            $this->back();
        }
        
        $taskModel->delete($id);
        
        Session::setFlash('success', 'Task deleted successfully.');
        $this->redirect("/projects/{$task['project_id']}");
    }
    
    public function addComment(int $id): void
    {
        $this->requireAuth();
        
        if (!CSRF::validateToken($_POST['csrf_token'] ?? '')) {
            Session::setFlash('error', 'Invalid security token.');
            $this->back();
        }
        
        $content = trim($_POST['content'] ?? '');
        
        if (empty($content)) {
            Session::setFlash('error', 'Comment cannot be empty.');
            $this->back();
        }
        
        $commentModel = new Comment();
        $commentId = $commentModel->create($id, Auth::id(), $content);
        
        if ($commentId) {
            Session::setFlash('success', 'Comment added.');
        } else {
            Session::setFlash('error', 'Failed to add comment.');
        }
        
        $this->redirect("/tasks/$id");
    }
}
