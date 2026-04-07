<?php

namespace App\Controllers;

class ProjectController extends BaseController
{
    public function index(): void
    {
        $this->requireAuth();
        
        $projectModel = new Project();
        
        if (Auth::isAdmin() || Auth::isManager()) {
            $projects = $projectModel->getAllWithStats();
        } else {
            $projects = $projectModel->getProjectsByUser(Auth::id());
        }
        
        $this->view('projects/index', ['projects' => $projects]);
    }
    
    public function show(int $id): void
    {
        $this->requireAuth();
        
        $projectModel = new Project();
        $taskModel = new Task();
        
        $project = $projectModel->find($id);
        
        if (!$project) {
            Session::setFlash('error', 'Project not found.');
            $this->redirect('/projects');
        }
        
        // Check permission
        if (!Auth::isAdmin() && !Auth::isManager() && $project['created_by'] !== Auth::id()) {
            // Check if user is assigned to any task in this project
            $tasks = $taskModel->getByProject($id);
            $hasAccess = false;
            foreach ($tasks as $task) {
                if ($task['assigned_to'] == Auth::id()) {
                    $hasAccess = true;
                    break;
                }
            }
            
            if (!$hasAccess) {
                Session::setFlash('error', 'You do not have permission to view this project.');
                $this->redirect('/projects');
            }
        }
        
        $tasks = $taskModel->getByProject($id);
        $taskCounts = $projectModel->getTaskCounts($id);
        
        // Group tasks by status
        $groupedTasks = [
            'todo' => [],
            'in_progress' => [],
            'review' => [],
            'done' => []
        ];
        
        foreach ($tasks as $task) {
            $groupedTasks[$task['status']][] = $task;
        }
        
        $this->view('projects/show', [
            'project' => $project,
            'tasks' => $tasks,
            'grouped_tasks' => $groupedTasks,
            'task_counts' => $taskCounts
        ]);
    }
    
    public function create(): void
    {
        $this->requireAuth();
        $this->setLayout('main');
        $this->view('projects/create');
    }
    
    public function store(): void
    {
        $this->requireAuth();
        
        if (!CSRF::validateToken($_POST['csrf_token'] ?? '')) {
            Session::setFlash('error', 'Invalid security token.');
            $this->back();
        }
        
        $validator = Validator::make($_POST, [
            'name' => 'required|min:3|max:255',
            'description' => 'max:1000',
            'status' => 'in:planning,active,on_hold,completed,cancelled'
        ]);
        
        if ($validator->fails()) {
            Session::setFlash('error', $validator->firstError());
            $this->back();
        }
        
        $projectModel = new Project();
        
        $projectId = $projectModel->create([
            'name' => $_POST['name'],
            'description' => $_POST['description'] ?? '',
            'status' => $_POST['status'] ?? 'planning',
            'created_by' => Auth::id()
        ]);
        
        if ($projectId) {
            Session::setFlash('success', 'Project created successfully!');
            $this->redirect("/projects/$projectId");
        } else {
            Session::setFlash('error', 'Failed to create project.');
            $this->back();
        }
    }
    
    public function update(int $id): void
    {
        $this->requireAuth();
        
        if (!CSRF::validateToken($_POST['csrf_token'] ?? '')) {
            Session::setFlash('error', 'Invalid security token.');
            $this->json(['success' => false, 'message' => 'Invalid token'], 400);
        }
        
        $projectModel = new Project();
        $project = $projectModel->find($id);
        
        if (!$project) {
            $this->json(['success' => false, 'message' => 'Project not found'], 404);
        }
        
        // Check permission
        if (!Auth::isAdmin() && !Auth::isManager() && $project['created_by'] !== Auth::id()) {
            $this->json(['success' => false, 'message' => 'Permission denied'], 403);
        }
        
        $data = [];
        
        if (isset($_POST['name'])) {
            $data['name'] = substr(trim($_POST['name']), 0, 255);
        }
        
        if (isset($_POST['description'])) {
            $data['description'] = substr(trim($_POST['description']), 0, 1000);
        }
        
        if (isset($_POST['status'])) {
            $allowedStatuses = ['planning', 'active', 'on_hold', 'completed', 'cancelled'];
            if (in_array($_POST['status'], $allowedStatuses, true)) {
                $data['status'] = $_POST['status'];
            }
        }
        
        if (!empty($data)) {
            $projectModel->update($id, $data);
        }
        
        $this->json(['success' => true, 'project' => $projectModel->find($id)]);
    }
    
    public function delete(int $id): void
    {
        $this->requireAuth();
        
        if (!CSRF::validateToken($_POST['csrf_token'] ?? '')) {
            Session::setFlash('error', 'Invalid security token.');
            $this->back();
        }
        
        $projectModel = new Project();
        $project = $projectModel->find($id);
        
        if (!$project) {
            Session::setFlash('error', 'Project not found.');
            $this->redirect('/projects');
        }
        
        // Check permission (only admin/manager or creator can delete)
        if (!Auth::isAdmin() && !Auth::isManager() && $project['created_by'] !== Auth::id()) {
            Session::setFlash('error', 'Permission denied.');
            $this->redirect('/projects');
        }
        
        $projectModel->delete($id);
        
        Session::setFlash('success', 'Project deleted successfully.');
        $this->redirect('/projects');
    }
}
