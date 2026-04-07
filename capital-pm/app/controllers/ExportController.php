<?php

namespace App\Controllers;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class ExportController
{
    public function __construct()
    {
        Auth::requireLogin();
    }

    /**
     * Export tasks to Excel
     */
    public function tasks(int $projectId): void
    {
        $projectModel = new \App\Models\Project();
        $taskModel = new \App\Models\Task();

        $project = $projectModel->findById($projectId);
        
        if (!$project) {
            http_response_code(404);
            die('Project not found');
        }

        // Check access
        $userId = Auth::id();
        if (!Auth::isAdmin() && !$projectModel->isMember($projectId, $userId)) {
            http_response_code(403);
            die('Access denied');
        }

        $tasks = $taskModel->getAll(['project_id' => $projectId]);

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Headers
        $headers = [
            'ID',
            'Title',
            'Description',
            'Status',
            'Priority',
            'Assignee',
            'Reporter',
            'Category',
            'Due Date',
            'Story Points',
            'Created At',
            'Updated At'
        ];

        $col = 1;
        foreach ($headers as $header) {
            $sheet->setCellValueByColumnAndRow($col, 1, $header);
            $col++;
        }

        // Style header row
        $sheet->getStyle('A1:L1')->getFont()->setBold(true);
        $sheet->getStyle('A1:L1')->getFill()
            ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
            ->getStartColor()->setRGB('3498db');

        // Data rows
        $row = 2;
        foreach ($tasks as $task) {
            $assignee = $task['assignee_first_name'] . ' ' . $task['assignee_last_name'];
            $reporter = $task['reporter_first_name'] . ' ' . $task['reporter_last_name'];

            $sheet->fromArray([
                $task['id'],
                $task['title'],
                $task['description'] ?? '',
                TASK_STATUSES[$task['status']] ?? $task['status'],
                TASK_PRIORITIES[$task['priority']] ?? $task['priority'],
                $assignee ?: 'Unassigned',
                $reporter,
                $task['category_name'] ?? '-',
                $task['due_date'] ? date('Y-m-d', strtotime($task['due_date'])) : '',
                $task['story_points'] ?? '',
                date('Y-m-d H:i', strtotime($task['created_at'])),
                date('Y-m-d H:i', strtotime($task['updated_at']))
            ], null, 'A' . $row);

            $row++;
        }

        // Auto-size columns
        foreach (range('A', 'L') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        // Set filename
        $filename = sprintf(
            'tasks_%s_%s.xlsx',
            preg_replace('/[^a-z0-9]+/i', '_', strtolower($project['name'])),
            date('Y-m-d')
        );

        // Output file
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $filename . '"');
        header('Cache-Control: max-age=0');

        $writer = new Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
    }

    /**
     * Export projects list to Excel
     */
    public function projects(): void
    {
        $projectModel = new \App\Models\Project();

        $filters = ['user_id' => Auth::id()];
        if (Auth::isAdmin()) {
            unset($filters['user_id']);
        }

        $projects = $projectModel->getAll($filters);

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Headers
        $headers = [
            'ID',
            'Name',
            'Description',
            'Status',
            'Priority',
            'Owner',
            'Start Date',
            'End Date',
            'Budget',
            'Created At'
        ];

        $col = 1;
        foreach ($headers as $header) {
            $sheet->setCellValueByColumnAndRow($col, 1, $header);
            $col++;
        }

        // Style header row
        $sheet->getStyle('A1:J1')->getFont()->setBold(true);
        $sheet->getStyle('A1:J1')->getFill()
            ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
            ->getStartColor()->setRGB('27ae60');

        // Data rows
        $row = 2;
        foreach ($projects as $project) {
            $owner = $project['owner_first_name'] . ' ' . $project['owner_last_name'];

            $sheet->fromArray([
                $project['id'],
                $project['name'],
                $project['description'] ?? '',
                PROJECT_STATUSES[$project['status']] ?? $project['status'],
                TASK_PRIORITIES[$project['priority']] ?? $project['priority'],
                $owner,
                $project['start_date'] ? date('Y-m-d', strtotime($project['start_date'])) : '',
                $project['end_date'] ? date('Y-m-d', strtotime($project['end_date'])) : '',
                $project['budget'] ? '$' . number_format($project['budget'], 2) : '',
                date('Y-m-d H:i', strtotime($project['created_at']))
            ], null, 'A' . $row);

            $row++;
        }

        // Auto-size columns
        foreach (range('A', 'J') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        // Set filename
        $filename = 'projects_' . date('Y-m-d') . '.xlsx';

        // Output file
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $filename . '"');
        header('Cache-Control: max-age=0');

        $writer = new Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
    }
}
