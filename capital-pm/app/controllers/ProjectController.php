<?php

namespace App\Controllers;

use App\Models\Project;
use App\Models\User;
use App\Models\Category;
use App\Models\ChangeLog;

class ProjectController
{
    private Project $projectModel;
    private User $userModel;
    private Category $categoryModel;
    private ChangeLog $changeLogModel;

    public function __construct()
    {
        Auth::requireLogin();
        
        $this->projectModel = new Project();
        $this->userModel = new User();
        $this->categoryModel = new Category();
        $this->changeLogModel = new ChangeLog();
    }

    /**
     * List all projects
     */
    public function index(): void
    {
        $userId = Auth::id();
        $filters = [
            'user_id' => $userId,
            'search' => get('search', ''),
            'status' => get('status', '')
        ];

        if (Auth::isAdmin()) {
            unset($filters['user_id']);
            // Admins can see all projects or filter by user
            if (get('owner_id')) {
                $filters['owner_id'] = (int) get('owner_id');
            }
        }

        $projects = $this->projectModel->getAll($filters);
        $users = Auth::isAdmin() ? $this->userModel->getAll() : [];

        require APP_PATH . '/views/project/index.php';
    }

    /**
     * Show project details
     */
    public function show(int $id): void
    {
        $userId = Auth::id();
        
        $project = $this->projectModel->findById($id);
        
        if (!$project) {
            http_response_code(404);
            die('Project not found');
        }

        // Check access
        if (!Auth::isAdmin() && !$this->projectModel->isMember($id, $userId)) {
            http_response_code(403);
            die('Access denied');
        }

        $members = $this->projectModel->getMembers($id);
        $categories = $this->categoryModel->getAllForProject($id);
        $stats = $this->projectModel->getStatistics($id);
        $availableUsers = $this->userModel->getAvailableForProject($id);

        require APP_PATH . '/views/project/show.php';
    }

    /**
     * Show create form
     */
    public function create(): void
    {
        require APP_PATH . '/views/project/create.php';
    }

    /**
     * Store new project
     */
    public function store(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect(APP_URL . '/projects/create');
        }

        require_csrf();

        $name = trim(post('name', ''));
        $description = trim(post('description', ''));
        $status = post('status', 'planning');
        $priority = post('priority', 'medium');
        $startDate = post('start_date', '');
        $endDate = post('end_date', '');
        $budget = post('budget', '');

        $errors = [];

        if (empty($name)) {
            $errors[] = 'Project name is required';
        }

        if (!empty($errors)) {
            $_SESSION['project_errors'] = $errors;
            $_SESSION['old_input'] = $_POST;
            redirect(APP_URL . '/projects/create');
        }

        $projectId = $this->projectModel->create([
            'name' => $name,
            'description' => $description,
            'status' => $status,
            'priority' => $priority,
            'start_date' => $startDate ?: null,
            'end_date' => $endDate ?: null,
            'budget' => $budget ?: null,
            'owner_id' => Auth::id()
        ]);

        if ($projectId) {
            // Log creation
            $this->changeLogModel->logProjectCreation($projectId, Auth::id(), [
                'name' => $name,
                'status' => $status
            ]);

            redirect(APP_URL . '/projects/' . $projectId);
        } else {
            $_SESSION['project_errors'] = ['Failed to create project'];
            redirect(APP_URL . '/projects/create');
        }
    }

    /**
     * Show edit form
     */
    public function edit(int $id): void
    {
        $userId = Auth::id();
        $project = $this->projectModel->findById($id);

        if (!$project) {
            http_response_code(404);
            die('Project not found');
        }

        // Check access
        if (!Auth::isAdmin() && !$this->projectModel->isMember($id, $userId)) {
            http_response_code(403);
            die('Access denied');
        }

        require APP_PATH . '/views/project/edit.php';
    }

    /**
     * Update project
     */
    public function update(int $id): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect(APP_URL . '/projects/' . $id);
        }

        require_csrf();

        $project = $this->projectModel->findById($id);
        if (!$project) {
            http_response_code(404);
            die('Project not found');
        }

        // Check access - only owner, manager or admin can edit
        $userRole = $this->projectModel->getUserRole($id, Auth::id());
        if (!Auth::isAdmin() && !in_array($userRole, ['owner', 'manager'])) {
            http_response_code(403);
            die('Access denied');
        }

        $data = [
            'name' => trim(post('name', $project['name'])),
            'description' => trim(post('description', $project['description'])),
            'status' => post('status', $project['status']),
            'priority' => post('priority', $project['priority']),
            'start_date' => post('start_date', $project['start_date']) ?: null,
            'end_date' => post('end_date', $project['end_date']) ?: null,
            'budget' => post('budget', $project['budget']) ?: null
        ];

        // Log changes
        foreach ($data as $field => $value) {
            if ($project[$field] !== $value) {
                $this->changeLogModel->logProjectUpdate(
                    $id, 
                    Auth::id(), 
                    $field, 
                    $project[$field], 
                    $value
                );
            }
        }

        if ($this->projectModel->update($id, $data)) {
            $_SESSION['project_success'] = 'Project updated successfully';
        } else {
            $_SESSION['project_errors'] = ['Failed to update project'];
        }

        redirect(APP_URL . '/projects/' . $id);
    }

    /**
     * Delete project
     */
    public function delete(int $id): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect(APP_URL . '/projects');
        }

        require_csrf();

        $project = $this->projectModel->findById($id);
        if (!$project) {
            http_response_code(404);
            die('Project not found');
        }

        // Only owner or admin can delete
        if (!Auth::isAdmin() && $project['owner_id'] !== Auth::id()) {
            http_response_code(403);
            die('Access denied');
        }

        if ($this->projectModel->delete($id)) {
            $_SESSION['project_success'] = 'Project deleted successfully';
        } else {
            $_SESSION['project_errors'] = ['Failed to delete project'];
        }

        redirect(APP_URL . '/projects');
    }

    /**
     * Add member to project
     */
    public function addMember(int $id): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            json_error('Invalid request', 400);
        }

        require_csrf();

        $userId = (int) post('user_id');
        $role = post('role', 'member');

        if (!$userId || !in_array($role, ['manager', 'member', 'viewer'])) {
            json_error('Invalid data');
        }

        $project = $this->projectModel->findById($id);
        if (!$project) {
            json_error('Project not found', 404);
        }

        // Check access
        $userRole = $this->projectModel->getUserRole($id, Auth::id());
        if (!Auth::isAdmin() && !in_array($userRole, ['owner', 'manager'])) {
            json_error('Access denied', 403);
        }

        if ($this->projectModel->addMember($id, $userId, $role)) {
            json_success(['message' => 'Member added successfully']);
        } else {
            json_error('Failed to add member');
        }
    }

    /**
     * Remove member from project
     */
    public function removeMember(int $projectId, int $userId): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect(APP_URL . '/projects/' . $projectId);
        }

        require_csrf();

        // Check access
        $userRole = $this->projectModel->getUserRole($projectId, Auth::id());
        if (!Auth::isAdmin() && !in_array($userRole, ['owner', 'manager'])) {
            http_response_code(403);
            die('Access denied');
        }

        if ($this->projectModel->removeMember($projectId, $userId)) {
            $_SESSION['project_success'] = 'Member removed successfully';
        } else {
            $_SESSION['project_errors'] = ['Failed to remove member'];
        }

        redirect(APP_URL . '/projects/' . $projectId);
    }
}
