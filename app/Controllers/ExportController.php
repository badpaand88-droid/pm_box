<?php

namespace App\Controllers;

class ExportController extends BaseController
{
    /**
     * Show export form
     */
    public function index(): void
    {
        $this->requireAuth();
        
        $projectModel = new Project();
        $categoryModel = new Category();
        $userModel = new User();
        
        $projects = $projectModel->getAllWithStats();
        $categories = $categoryModel->getGlobal();
        $users = $userModel->getDevelopers();
        
        $this->view('export/index', [
            'projects' => $projects,
            'categories' => $categories,
            'users' => $users
        ]);
    }
    
    /**
     * Export tasks to Excel (XLSX)
     */
    public function toExcel(): void
    {
        $this->requireAuth();
        
        if (!CSRF::validateToken($_POST['csrf_token'] ?? '')) {
            Session::setFlash('error', 'Invalid security token.');
            $this->back();
        }
        
        $taskModel = new Task();
        
        // Build filters from POST data
        $filters = [];
        
        if (!empty($_POST['project_id'])) {
            $filters['project_id'] = (int)$_POST['project_id'];
        }
        
        if (!empty($_POST['status']) && $_POST['status'] !== 'all') {
            $filters['status'] = $_POST['status'];
        }
        
        if (!empty($_POST['category_id']) && $_POST['category_id'] !== 'all') {
            $filters['category_id'] = (int)$_POST['category_id'];
        }
        
        if (!empty($_POST['assigned_to']) && $_POST['assigned_to'] !== 'all') {
            $filters['assigned_to'] = (int)$_POST['assigned_to'];
        }
        
        if (!empty($_POST['priority']) && $_POST['priority'] !== 'all') {
            $filters['priority'] = $_POST['priority'];
        }
        
        // Get filtered tasks
        $tasks = $taskModel->getFiltered($filters);
        
        // Generate Excel file
        $this->generateExcel($tasks);
    }
    
    /**
     * Generate Excel file using Simple XML-based XLSX
     */
    private function generateExcel(array $tasks): void
    {
        // Create XLSX file (Office Open XML format)
        $filename = 'tasks_export_' . date('Y-m-d_H-i-s') . '.xlsx';
        
        // Prepare data
        $headers = [
            'Задача',
            'Категория',
            'Ответственный',
            'Статус',
            'Приоритет',
            'Старт',
            'Конец',
            'Длительность (дней)',
            'Зависит от',
            'Комментарий'
        ];
        
        $rows = [];
        foreach ($tasks as $task) {
            // Get dependencies for this task
            $depModel = new TaskDependency();
            $deps = $depModel->getDependencies((int)$task['id']);
            $depTitles = array_column($deps, 'title');
            
            // Get comments count
            $db = Database::getInstance();
            $commentCount = $db->fetch(
                "SELECT COUNT(*) as count FROM comments WHERE task_id = :taskId",
                ['taskId' => $task['id']]
            );
            
            $rows[] = [
                $task['title'],
                $task['category_name'] ?? '',
                $task['assignee_name'] ?? '',
                ucfirst(str_replace('_', ' ', $task['status'])),
                ucfirst($task['priority']),
                $task['start_date'] ?? '',
                $task['due_date'] ?? '',
                $task['duration_days'] ?? '',
                implode(', ', $depTitles),
                (int)($commentCount['count'] ?? 0) . ' комм.'
            ];
        }
        
        // Generate XLSX using simple XML approach
        $xlsx = $this->createSimpleXLSX($headers, $rows);
        
        // Send headers
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . strlen($xlsx));
        header('Cache-Control: max-age=0');
        
        echo $xlsx;
        exit;
    }
    
    /**
     * Create a simple XLSX file
     */
    private function createSimpleXLSX(array $headers, array $rows): string
    {
        // This is a simplified XLSX generator
        // For production use, consider using PHPSpreadsheet library
        
        $date = date('Y-m-d\TH:i:s\Z');
        
        // [Content_Types].xml
        $contentTypes = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">
    <Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>
    <Default Extension="xml" ContentType="application/xml"/>
    <Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>
    <Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>
    <Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>
</Types>';
        
        // _rels/.rels
        $rels = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
    <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>
</Relationships>';
        
        // xl/_rels/workbook.xml.rels
        $workbookRels = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
    <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>
    <Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>
</Relationships>';
        
        // xl/workbook.xml
        $workbook = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">
    <sheets>
        <sheet name="Tasks" sheetId="1" r:id="rId1"/>
    </sheets>
</workbook>';
        
        // xl/styles.xml
        $styles = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">
    <fonts count="2">
        <font><sz val="11"/><name val="Calibri"/></font>
        <font><sz val="11"/><b/><name val="Calibri"/></font>
    </fonts>
    <fills count="2">
        <fill><patternFill patternType="none"/></fill>
        <fill><patternFill patternType="gray125"/></fill>
    </fills>
    <borders count="1">
        <border><left/><right/><top/><bottom/><diagonal/></border>
    </borders>
    <cellStyleXfs count="1">
        <xf numFmtId="0" fontId="0" fillId="0" borderId="0"/>
    </cellStyleXfs>
    <cellXfs count="2">
        <xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>
        <xf numFmtId="0" fontId="1" fillId="0" borderId="0" xfId="0" applyFont="1"/>
    </cellXfs>
</styleSheet>';
        
        // xl/worksheets/sheet1.xml
        $worksheet = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">
    <dimension ref="A1:J' . (count($rows) + 1) . '"/>
    <sheetData>';
        
        // Header row
        $worksheet .= '<row r="1" spans="1:10">';
        foreach ($headers as $colIndex => $header) {
            $col = chr(65 + $colIndex);
            $escapedHeader = htmlspecialchars($header, ENT_XML1);
            $worksheet .= "<c r=\"{$col}1\" t=\"s\" s=\"1\"><v>{$colIndex}</v></c>";
        }
        $worksheet .= '</row>';
        
        // Data rows
        foreach ($rows as $rowIndex => $row) {
            $rowNum = $rowIndex + 2;
            $worksheet .= "<row r=\"$rowNum\" spans=\"1:10\">";
            foreach ($row as $colIndex => $cell) {
                $col = chr(65 + $colIndex);
                $escapedCell = htmlspecialchars((string)$cell, ENT_XML1);
                $worksheet .= "<c r=\"{$col}{$rowNum}\" t=\"inlineStr\"><is><t>{$escapedCell}</t></is></c>";
            }
            $worksheet .= '</row>';
        }
        
        $worksheet .= '</sheetData>
</worksheet>';
        
        // Shared strings table
        $sharedStrings = '<si><t>' . implode('</t></si><si><t>', array_map(fn($h) => htmlspecialchars($h, ENT_XML1), $headers)) . '</t></si>';
        foreach ($rows as $row) {
            foreach ($row as $cell) {
                $sharedStrings .= '<si><t>' . htmlspecialchars((string)$cell, ENT_XML1) . '</t></si>';
            }
        }
        $sharedStringsXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<sst xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" count="' . (count($headers) + count($rows) * count($headers)) . '" uniqueCount="' . (count($headers) + count($rows) * count($headers)) . '">' . $sharedStrings . '</sst>';
        
        // Create ZIP archive (XLSX is a ZIP file)
        $zip = new ZipArchive();
        $tempFile = tempnam(sys_get_temp_dir(), 'xlsx_');
        
        if ($zip->open($tempFile, ZipArchive::OVERWRITE) !== true) {
            throw new \Exception('Cannot create ZIP file');
        }
        
        $zip->addFromString('[Content_Types].xml', $contentTypes);
        $zip->addFromString('_rels/.rels', $rels);
        $zip->addFromString('xl/_rels/workbook.xml.rels', $workbookRels);
        $zip->addFromString('xl/workbook.xml', $workbook);
        $zip->addFromString('xl/styles.xml', $styles);
        $zip->addFromString('xl/worksheets/sheet1.xml', $worksheet);
        $zip->addFromString('xl/sharedStrings.xml', $sharedStringsXml);
        
        $zip->close();
        
        $content = file_get_contents($tempFile);
        unlink($tempFile);
        
        return $content;
    }
}
