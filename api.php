<?php
// api.php
require_once 'config/database.php';
require_once 'includes/functions.php';
require_once 'includes/smtp_mailer.php'; // For Gmail SMTP

requireLogin();

header('Content-Type: application/json');

$database = new Database();
$conn = $database->getConnection();

$action = $_POST['action'] ?? $_GET['action'] ?? '';

try {
    switch ($action) {
        case 'get_table_data':
            // NEW: Load table data with pagination
            $tableName = sanitizeTableName($_GET['table']);
            $offset = intval($_GET['offset'] ?? 0);
            $limit = intval($_GET['limit'] ?? 100);
            
            if (empty($tableName)) {
                throw new Exception('Table name is required');
            }
            
            $sql = "SELECT * FROM `$tableName` LIMIT :limit OFFSET :offset";
            $stmt = $conn->prepare($sql);
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();
            $data = $stmt->fetchAll();
            
            echo json_encode(['success' => true, 'data' => $data]);
            break;
            
        case 'create_table':
            $tableName = sanitizeTableName($_POST['table_name']);
            $numColumns = intval($_POST['num_columns']);
            
            if (empty($tableName)) {
                throw new Exception('Table name is required');
            }
            
            $sql = "CREATE TABLE `$tableName` (";
            $columns = [];
            
            for ($i = 0; $i < $numColumns; $i++) {
                $colName = sanitizeColumnName($_POST["col_name_$i"]);
                $colType = $_POST["col_type_$i"];
                $isPK = isset($_POST["col_pk_$i"]);
                $isAI = isset($_POST["col_ai_$i"]);
                
                if (empty($colName)) continue;
                
                $colDef = "`$colName` $colType";
                if ($isPK) $colDef .= " PRIMARY KEY";
                if ($isAI) $colDef .= " AUTO_INCREMENT";
                
                $columns[] = $colDef;
            }
            
            if (empty($columns)) {
                throw new Exception('At least one column is required');
            }
            
            $sql .= implode(', ', $columns) . ")";
            
            $conn->exec($sql);
            echo json_encode(['success' => true, 'message' => 'Table created successfully']);
            break;
            
        case 'delete_table':
            $tableName = sanitizeTableName($_POST['table_name']);
            if (empty($tableName)) {
                throw new Exception('Table name is required');
            }
            
            $stmt = $conn->prepare("DROP TABLE `$tableName`");
            $stmt->execute();
            echo json_encode(['success' => true, 'message' => 'Table deleted successfully']);
            break;
            
        case 'rename_table':
            $oldName = sanitizeTableName($_POST['old_name']);
            $newName = sanitizeTableName($_POST['new_name']);
            
            if (empty($oldName) || empty($newName)) {
                throw new Exception('Both old and new table names are required');
            }
            
            $stmt = $conn->prepare("RENAME TABLE `$oldName` TO `$newName`");
            $stmt->execute();
            echo json_encode(['success' => true, 'message' => 'Table renamed successfully']);
            break;
            
        case 'add_column':
            $tableName = sanitizeTableName($_POST['table_name']);
            $columnName = sanitizeColumnName($_POST['column_name']);
            $dataType = $_POST['data_type'];
            $nullable = isset($_POST['nullable']) ? 'NULL' : 'NOT NULL';
            
            if (empty($tableName) || empty($columnName)) {
                throw new Exception('Table name and column name are required');
            }
            
            $sql = "ALTER TABLE `$tableName` ADD `$columnName` $dataType $nullable";
            $conn->exec($sql);
            echo json_encode(['success' => true, 'message' => 'Column added successfully']);
            break;
            
        case 'delete_column':
            $tableName = sanitizeTableName($_POST['table_name']);
            $columnName = sanitizeColumnName($_POST['column_name']);
            
            if (empty($tableName) || empty($columnName)) {
                throw new Exception('Table name and column name are required');
            }
            
            $sql = "ALTER TABLE `$tableName` DROP COLUMN `$columnName`";
            $conn->exec($sql);
            echo json_encode(['success' => true, 'message' => 'Column deleted successfully']);
            break;
            
        case 'add_row':
            $tableName = sanitizeTableName($_POST['table_name']);
            if (empty($tableName)) {
                throw new Exception('Table name is required');
            }
            
            $columns = [];
            $values = [];
            $placeholders = [];
            
            foreach ($_POST as $key => $value) {
                if ($key === 'action' || $key === 'table_name') continue;
                $columns[] = "`" . sanitizeColumnName($key) . "`";
                $values[] = $value;
                $placeholders[] = '?';
            }
            
            if (empty($columns)) {
                throw new Exception('No data to insert');
            }
            
            $sql = "INSERT INTO `$tableName` (" . implode(', ', $columns) . ") VALUES (" . implode(', ', $placeholders) . ")";
            $stmt = $conn->prepare($sql);
            $stmt->execute($values);
            echo json_encode(['success' => true, 'message' => 'Row added successfully']);
            break;
            
        case 'update_row':
            $tableName = sanitizeTableName($_POST['table_name']);
            $primaryKey = sanitizeColumnName($_POST['primary_key']);
            
            if (empty($tableName) || empty($primaryKey)) {
                throw new Exception('Table name and primary key are required');
            }
            
            $setClauses = [];
            $values = [];
            $pkValue = null;
            
            foreach ($_POST as $key => $value) {
                if (in_array($key, ['action', 'table_name', 'primary_key'])) continue;
                
                $cleanKey = sanitizeColumnName($key);
                if ($key === $primaryKey) {
                    $pkValue = $value;
                } else {
                    $setClauses[] = "`$cleanKey` = ?";
                    $values[] = $value;
                }
            }
            
            if (empty($setClauses) || $pkValue === null) {
                throw new Exception('No data to update');
            }
            
            $values[] = $pkValue;
            $sql = "UPDATE `$tableName` SET " . implode(', ', $setClauses) . " WHERE `$primaryKey` = ?";
            $stmt = $conn->prepare($sql);
            $stmt->execute($values);
            echo json_encode(['success' => true, 'message' => 'Row updated successfully']);
            break;
            
        case 'delete_row':
            $tableName = sanitizeTableName($_POST['table_name']);
            $pkColumn = sanitizeColumnName($_POST['pk_column']);
            $pkValue = $_POST['pk_value'];
            
            if (empty($tableName) || empty($pkColumn)) {
                throw new Exception('Table name and primary key column are required');
            }
            
            $sql = "DELETE FROM `$tableName` WHERE `$pkColumn` = ?";
            $stmt = $conn->prepare($sql);
            $stmt->execute([$pkValue]);
            echo json_encode(['success' => true, 'message' => 'Row deleted successfully']);
            break;

        // ==================== CHAPTER MANAGEMENT ====================
        case 'get_chapters':
            $chaptersFile = __DIR__ . '/data/chapters.json';
            $chapters = [];
            
            if (file_exists($chaptersFile)) {
                $chapters = json_decode(file_get_contents($chaptersFile), true) ?: [];
            }
            
            // Add document count for each chapter
            $docsFile = __DIR__ . '/data/chapter_documents.json';
            $documents = file_exists($docsFile) ? json_decode(file_get_contents($docsFile), true) ?: [] : [];
            
            foreach ($chapters as &$chapter) {
                $chapter['doc_count'] = count(array_filter($documents, fn($d) => $d['chapter_id'] == $chapter['id']));
            }
            
            echo json_encode(['success' => true, 'chapters' => $chapters]);
            break;

        case 'create_chapter':
            $chapterName = trim($_POST['chapter_name'] ?? '');
            if (empty($chapterName)) {
                throw new Exception('Chapter name is required');
            }
            
            $chaptersFile = __DIR__ . '/data/chapters.json';
            $chapters = [];
            
            if (file_exists($chaptersFile)) {
                $chapters = json_decode(file_get_contents($chaptersFile), true) ?: [];
            }
            
            // Check for duplicate name
            foreach ($chapters as $ch) {
                if (strtolower($ch['name']) === strtolower($chapterName)) {
                    throw new Exception('A chapter with this name already exists');
                }
            }
            
            // Generate new ID
            $maxId = 0;
            foreach ($chapters as $ch) {
                if ($ch['id'] > $maxId) $maxId = $ch['id'];
            }
            
            $newChapter = [
                'id' => $maxId + 1,
                'name' => $chapterName,
                'created_at' => date('Y-m-d H:i:s')
            ];
            
            $chapters[] = $newChapter;
            
            // Create chapters directory if not exists
            $chapterDir = __DIR__ . '/files/chapters/' . ($maxId + 1);
            if (!is_dir($chapterDir)) {
                mkdir($chapterDir, 0755, true);
            }
            
            file_put_contents($chaptersFile, json_encode($chapters, JSON_PRETTY_PRINT));
            echo json_encode(['success' => true, 'message' => 'Chapter created successfully', 'chapter' => $newChapter]);
            break;

        case 'rename_chapter':
            $chapterId = intval($_POST['chapter_id'] ?? 0);
            $newName = trim($_POST['new_name'] ?? '');
            
            if (empty($chapterId) || empty($newName)) {
                throw new Exception('Chapter ID and new name are required');
            }
            
            $chaptersFile = __DIR__ . '/data/chapters.json';
            $chapters = [];
            
            if (file_exists($chaptersFile)) {
                $chapters = json_decode(file_get_contents($chaptersFile), true) ?: [];
            }
            
            $found = false;
            foreach ($chapters as &$chapter) {
                if ($chapter['id'] == $chapterId) {
                    $chapter['name'] = $newName;
                    $found = true;
                    break;
                }
            }
            
            if (!$found) {
                throw new Exception('Chapter not found');
            }
            
            file_put_contents($chaptersFile, json_encode($chapters, JSON_PRETTY_PRINT));
            echo json_encode(['success' => true, 'message' => 'Chapter renamed successfully']);
            break;

        case 'delete_chapter':
            $chapterId = intval($_POST['chapter_id'] ?? 0);
            
            if (empty($chapterId)) {
                throw new Exception('Chapter ID is required');
            }
            
            $chaptersFile = __DIR__ . '/data/chapters.json';
            $chapters = [];
            
            if (file_exists($chaptersFile)) {
                $chapters = json_decode(file_get_contents($chaptersFile), true) ?: [];
            }
            
            // Remove chapter
            $chapters = array_filter($chapters, fn($ch) => $ch['id'] != $chapterId);
            $chapters = array_values($chapters);
            
            // Delete documents for this chapter
            $docsFile = __DIR__ . '/data/chapter_documents.json';
            $documents = file_exists($docsFile) ? json_decode(file_get_contents($docsFile), true) ?: [] : [];
            
            foreach ($documents as $doc) {
                if ($doc['chapter_id'] == $chapterId && file_exists($doc['file_path'])) {
                    unlink($doc['file_path']);
                }
            }
            
            $documents = array_filter($documents, fn($d) => $d['chapter_id'] != $chapterId);
            $documents = array_values($documents);
            
            // Delete chapter directory
            $chapterDir = __DIR__ . '/files/chapters/' . $chapterId;
            if (is_dir($chapterDir)) {
                array_map('unlink', glob("$chapterDir/*"));
                rmdir($chapterDir);
            }
            
            file_put_contents($chaptersFile, json_encode($chapters, JSON_PRETTY_PRINT));
            file_put_contents($docsFile, json_encode($documents, JSON_PRETTY_PRINT));
            echo json_encode(['success' => true, 'message' => 'Chapter deleted successfully']);
            break;

        case 'upload_chapter_document':
            $chapterId = intval($_POST['chapter_id'] ?? 0);
            
            if (empty($chapterId)) {
                throw new Exception('Chapter ID is required');
            }
            
            if (!isset($_FILES['chapter_document']) || $_FILES['chapter_document']['error'] !== UPLOAD_ERR_OK) {
                throw new Exception('No file uploaded or upload error');
            }
            
            $file = $_FILES['chapter_document'];
            $allowedExtensions = ['pdf', 'doc', 'docx', 'txt', 'xls', 'xlsx', 'ppt', 'pptx'];
            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            
            if (!in_array($ext, $allowedExtensions)) {
                throw new Exception('File type not allowed. Allowed: ' . implode(', ', $allowedExtensions));
            }
            
            // Create chapter directory if not exists
            $chapterDir = __DIR__ . '/files/chapters/' . $chapterId;
            if (!is_dir($chapterDir)) {
                mkdir($chapterDir, 0755, true);
            }
            
            // Generate unique filename
            $newFilename = time() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '_', $file['name']);
            $destPath = $chapterDir . '/' . $newFilename;
            
            if (!move_uploaded_file($file['tmp_name'], $destPath)) {
                throw new Exception('Failed to save uploaded file');
            }
            
            // Save document record
            $docsFile = __DIR__ . '/data/chapter_documents.json';
            $documents = file_exists($docsFile) ? json_decode(file_get_contents($docsFile), true) ?: [] : [];
            
            $maxId = 0;
            foreach ($documents as $doc) {
                if ($doc['id'] > $maxId) $maxId = $doc['id'];
            }
            
            $documents[] = [
                'id' => $maxId + 1,
                'chapter_id' => $chapterId,
                'original_name' => $file['name'],
                'file_path' => 'files/chapters/' . $chapterId . '/' . $newFilename,
                'uploaded_at' => date('Y-m-d H:i:s')
            ];
            
            file_put_contents($docsFile, json_encode($documents, JSON_PRETTY_PRINT));
            echo json_encode(['success' => true, 'message' => 'Document uploaded successfully']);
            break;

        case 'get_chapter_documents':
            $chapterId = intval($_GET['chapter_id'] ?? 0);
            
            if (empty($chapterId)) {
                throw new Exception('Chapter ID is required');
            }
            
            $docsFile = __DIR__ . '/data/chapter_documents.json';
            $documents = file_exists($docsFile) ? json_decode(file_get_contents($docsFile), true) ?: [] : [];
            
            $chapterDocs = array_filter($documents, fn($d) => $d['chapter_id'] == $chapterId);
            $chapterDocs = array_values($chapterDocs);
            
            echo json_encode(['success' => true, 'documents' => $chapterDocs]);
            break;

        case 'delete_chapter_document':
            $docId = intval($_POST['document_id'] ?? 0);
            
            if (empty($docId)) {
                throw new Exception('Document ID is required');
            }
            
            $docsFile = __DIR__ . '/data/chapter_documents.json';
            $documents = file_exists($docsFile) ? json_decode(file_get_contents($docsFile), true) ?: [] : [];
            
            // Find and delete file
            foreach ($documents as $doc) {
                if ($doc['id'] == $docId) {
                    // Delete physical file if exists
                    if (!empty($doc['file_path']) && !$doc['is_rich_doc']) {
                        $filePath = __DIR__ . '/' . $doc['file_path'];
                        if (file_exists($filePath)) {
                            unlink($filePath);
                        }
                    }
                    // Delete rich document content file if exists
                    if (!empty($doc['is_rich_doc']) && !empty($doc['content_file'])) {
                        $contentPath = __DIR__ . '/' . $doc['content_file'];
                        if (file_exists($contentPath)) {
                            unlink($contentPath);
                        }
                    }
                    break;
                }
            }
            
            // Remove from list
            $documents = array_filter($documents, fn($d) => $d['id'] != $docId);
            $documents = array_values($documents);
            
            file_put_contents($docsFile, json_encode($documents, JSON_PRETTY_PRINT));
            echo json_encode(['success' => true, 'message' => 'Document deleted successfully']);
            break;

        // ==================== RICH DOCUMENT EDITOR ====================
        case 'create_rich_document':
            $title = trim($_POST['title'] ?? '');
            $content = $_POST['content'] ?? '';
            $chapterId = intval($_POST['chapter_id'] ?? 0);
            
            if (empty($title)) {
                throw new Exception('Document title is required');
            }
            
            // Create documents content directory
            $contentDir = __DIR__ . '/data/documents';
            if (!is_dir($contentDir)) {
                mkdir($contentDir, 0755, true);
            }
            
            // Generate unique filename for content
            $contentFilename = 'doc_' . time() . '_' . uniqid() . '.html';
            $contentPath = $contentDir . '/' . $contentFilename;
            
            // Save content to file
            file_put_contents($contentPath, $content);
            
            // Save document record
            $docsFile = __DIR__ . '/data/chapter_documents.json';
            $documents = file_exists($docsFile) ? json_decode(file_get_contents($docsFile), true) ?: [] : [];
            
            $maxId = 0;
            foreach ($documents as $doc) {
                if ($doc['id'] > $maxId) $maxId = $doc['id'];
            }
            
            $newDoc = [
                'id' => $maxId + 1,
                'chapter_id' => $chapterId ?: null,
                'original_name' => $title . '.doc',
                'title' => $title,
                'is_rich_doc' => true,
                'content_file' => 'data/documents/' . $contentFilename,
                'file_path' => 'api.php?action=download_rich_document&document_id=' . ($maxId + 1),
                'uploaded_at' => date('Y-m-d H:i:s')
            ];
            
            $documents[] = $newDoc;
            file_put_contents($docsFile, json_encode($documents, JSON_PRETTY_PRINT));
            
            echo json_encode(['success' => true, 'message' => 'Document created successfully', 'document_id' => $maxId + 1]);
            break;

        case 'update_rich_document':
            $docId = intval($_POST['document_id'] ?? 0);
            $title = trim($_POST['title'] ?? '');
            $content = $_POST['content'] ?? '';
            $chapterId = intval($_POST['chapter_id'] ?? 0);
            
            if (empty($docId)) {
                throw new Exception('Document ID is required');
            }
            if (empty($title)) {
                throw new Exception('Document title is required');
            }
            
            $docsFile = __DIR__ . '/data/chapter_documents.json';
            $documents = file_exists($docsFile) ? json_decode(file_get_contents($docsFile), true) ?: [] : [];
            
            $found = false;
            foreach ($documents as &$doc) {
                if ($doc['id'] == $docId) {
                    // Update content file
                    if (!empty($doc['content_file'])) {
                        $contentPath = __DIR__ . '/' . $doc['content_file'];
                        file_put_contents($contentPath, $content);
                    } else {
                        // Create new content file if doesn't exist
                        $contentDir = __DIR__ . '/data/documents';
                        if (!is_dir($contentDir)) {
                            mkdir($contentDir, 0755, true);
                        }
                        $contentFilename = 'doc_' . time() . '_' . uniqid() . '.html';
                        file_put_contents($contentDir . '/' . $contentFilename, $content);
                        $doc['content_file'] = 'data/documents/' . $contentFilename;
                    }
                    
                    $doc['title'] = $title;
                    $doc['original_name'] = $title . '.doc';
                    $doc['chapter_id'] = $chapterId ?: null;
                    $doc['updated_at'] = date('Y-m-d H:i:s');
                    $found = true;
                    break;
                }
            }
            
            if (!$found) {
                throw new Exception('Document not found');
            }
            
            file_put_contents($docsFile, json_encode($documents, JSON_PRETTY_PRINT));
            echo json_encode(['success' => true, 'message' => 'Document updated successfully']);
            break;

        case 'get_document_content':
            $docId = intval($_GET['document_id'] ?? 0);
            
            if (empty($docId)) {
                throw new Exception('Document ID is required');
            }
            
            $docsFile = __DIR__ . '/data/chapter_documents.json';
            $documents = file_exists($docsFile) ? json_decode(file_get_contents($docsFile), true) ?: [] : [];
            
            $foundDoc = null;
            foreach ($documents as $doc) {
                if ($doc['id'] == $docId) {
                    $foundDoc = $doc;
                    break;
                }
            }
            
            if (!$foundDoc) {
                throw new Exception('Document not found');
            }
            
            $content = '';
            if (!empty($foundDoc['content_file'])) {
                $contentPath = __DIR__ . '/' . $foundDoc['content_file'];
                if (file_exists($contentPath)) {
                    $content = file_get_contents($contentPath);
                }
            }
            
            echo json_encode([
                'success' => true, 
                'document' => [
                    'id' => $foundDoc['id'],
                    'title' => $foundDoc['title'] ?? $foundDoc['original_name'],
                    'chapter_id' => $foundDoc['chapter_id'] ?? '',
                    'content' => $content
                ]
            ]);
            break;

        case 'download_rich_document':
            $docId = intval($_GET['document_id'] ?? 0);
            
            if (empty($docId)) {
                throw new Exception('Document ID is required');
            }
            
            $docsFile = __DIR__ . '/data/chapter_documents.json';
            $documents = file_exists($docsFile) ? json_decode(file_get_contents($docsFile), true) ?: [] : [];
            
            $foundDoc = null;
            foreach ($documents as $doc) {
                if ($doc['id'] == $docId) {
                    $foundDoc = $doc;
                    break;
                }
            }
            
            if (!$foundDoc) {
                throw new Exception('Document not found');
            }
            
            $content = '';
            if (!empty($foundDoc['content_file'])) {
                $contentPath = __DIR__ . '/' . $foundDoc['content_file'];
                if (file_exists($contentPath)) {
                    $content = file_get_contents($contentPath);
                }
            }
            
            $title = $foundDoc['title'] ?? 'document';
            
            // Create Word-compatible HTML
            $htmlContent = '
                <html xmlns:o="urn:schemas-microsoft-com:office:office" 
                      xmlns:w="urn:schemas-microsoft-com:office:word" 
                      xmlns="http://www.w3.org/TR/REC-html40">
                <head>
                    <meta charset="utf-8">
                    <title>' . htmlspecialchars($title) . '</title>
                    <style>
                        body { font-family: Arial, sans-serif; font-size: 12pt; line-height: 1.6; }
                        table { border-collapse: collapse; }
                        td, th { border: 1px solid #000; padding: 5px; }
                    </style>
                </head>
                <body>
                    ' . $content . '
                </body>
                </html>
            ';
            
            header('Content-Type: application/msword');
            header('Content-Disposition: attachment; filename="' . $title . '.doc"');
            header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
            echo "\xEF\xBB\xBF" . $htmlContent;
            exit;
            
        // ==================== HOME PAGE MANAGEMENT ====================
        case 'get_home_sections':
            $sectionsFile = __DIR__ . '/data/home_sections.json';
            $sections = [];
            
            if (file_exists($sectionsFile)) {
                $sections = json_decode(file_get_contents($sectionsFile), true) ?: [];
            }
            
            echo json_encode(['success' => true, 'sections' => $sections]);
            break;

        case 'create_home_section':
            $title = trim($_POST['title'] ?? '');
            $type = $_POST['type'] ?? 'custom';
            $order = intval($_POST['order'] ?? 1);
            
            if (empty($title)) {
                throw new Exception('Section title is required');
            }
            
            $sectionsFile = __DIR__ . '/data/home_sections.json';
            $sections = [];
            
            if (file_exists($sectionsFile)) {
                $sections = json_decode(file_get_contents($sectionsFile), true) ?: [];
            }
            
            // Generate new ID
            $maxId = 0;
            foreach ($sections as $sec) {
                if ($sec['id'] > $maxId) $maxId = $sec['id'];
            }
            
            $newSection = [
                'id' => $maxId + 1,
                'title' => $title,
                'type' => $type,
                'order' => $order,
                'status' => 'active',
                'content' => '',
                'created_at' => date('Y-m-d H:i:s')
            ];
            
            $sections[] = $newSection;
            
            file_put_contents($sectionsFile, json_encode($sections, JSON_PRETTY_PRINT));
            echo json_encode(['success' => true, 'message' => 'Section created successfully', 'section' => $newSection]);
            break;

        case 'get_home_section':
            $sectionId = intval($_GET['id'] ?? 0);
            
            if (empty($sectionId)) {
                throw new Exception('Section ID is required');
            }
            
            $sectionsFile = __DIR__ . '/data/home_sections.json';
            $sections = file_exists($sectionsFile) ? json_decode(file_get_contents($sectionsFile), true) ?: [] : [];
            
            $foundSection = null;
            foreach ($sections as $section) {
                if ($section['id'] == $sectionId) {
                    $foundSection = $section;
                    break;
                }
            }
            
            if (!$foundSection) {
                throw new Exception('Section not found');
            }
            
            echo json_encode(['success' => true, 'section' => $foundSection]);
            break;

        case 'update_home_section':
            $sectionId = intval($_POST['section_id'] ?? 0);
            $title = trim($_POST['title'] ?? '');
            $type = $_POST['type'] ?? 'custom';
            $order = intval($_POST['order'] ?? 1);
            $status = $_POST['status'] ?? 'active';
            $content = $_POST['content'] ?? '';
            
            if (empty($sectionId)) {
                throw new Exception('Section ID is required');
            }
            if (empty($title)) {
                throw new Exception('Section title is required');
            }
            
            $sectionsFile = __DIR__ . '/data/home_sections.json';
            $sections = file_exists($sectionsFile) ? json_decode(file_get_contents($sectionsFile), true) ?: [] : [];
            
            $found = false;
            foreach ($sections as &$section) {
                if ($section['id'] == $sectionId) {
                    $section['title'] = $title;
                    $section['type'] = $type;
                    $section['order'] = $order;
                    $section['status'] = $status;
                    $section['content'] = $content;
                    $section['updated_at'] = date('Y-m-d H:i:s');
                    $found = true;
                    break;
                }
            }
            
            if (!$found) {
                throw new Exception('Section not found');
            }
            
            file_put_contents($sectionsFile, json_encode($sections, JSON_PRETTY_PRINT));
            echo json_encode(['success' => true, 'message' => 'Section updated successfully']);
            break;

        case 'update_home_section_status':
            $sectionId = intval($_POST['section_id'] ?? 0);
            $status = $_POST['status'] ?? 'inactive';
            
            if (empty($sectionId)) {
                throw new Exception('Section ID is required');
            }
            
            $sectionsFile = __DIR__ . '/data/home_sections.json';
            $sections = file_exists($sectionsFile) ? json_decode(file_get_contents($sectionsFile), true) ?: [] : [];
            
            $found = false;
            foreach ($sections as &$section) {
                if ($section['id'] == $sectionId) {
                    $section['status'] = $status;
                    $section['updated_at'] = date('Y-m-d H:i:s');
                    $found = true;
                    break;
                }
            }
            
            if (!$found) {
                throw new Exception('Section not found');
            }
            
            file_put_contents($sectionsFile, json_encode($sections, JSON_PRETTY_PRINT));
            echo json_encode(['success' => true, 'message' => 'Section status updated successfully']);
            break;

        case 'delete_home_section':
            $sectionId = intval($_POST['section_id'] ?? 0);
            
            if (empty($sectionId)) {
                throw new Exception('Section ID is required');
            }
            
            $sectionsFile = __DIR__ . '/data/home_sections.json';
            $sections = file_exists($sectionsFile) ? json_decode(file_get_contents($sectionsFile), true) ?: [] : [];
            
            $sections = array_filter($sections, fn($s) => $s['id'] != $sectionId);
            $sections = array_values($sections);
            
            file_put_contents($sectionsFile, json_encode($sections, JSON_PRETTY_PRINT));
            echo json_encode(['success' => true, 'message' => 'Section deleted successfully']);
            break;

        // ==================== PRIVACY POLICY MANAGEMENT ====================
        case 'get_privacy_policies':
            try {
                $stmt = $conn->prepare("SELECT * FROM privacy_policy ORDER BY id DESC");
                $stmt->execute();
                $policies = $stmt->fetchAll();
                
                echo json_encode(['success' => true, 'policies' => $policies]);
            } catch (Exception $e) {
                throw new Exception('Failed to get privacy policies: ' . $e->getMessage());
            }
            break;
            
        case 'get_active_privacy_policy':
            try {
                $stmt = $conn->prepare("SELECT * FROM privacy_policy WHERE status = 'active' ORDER BY id DESC LIMIT 1");
                $stmt->execute();
                $policy = $stmt->fetch();
                
                echo json_encode(['success' => true, 'policy' => $policy]);
            } catch (Exception $e) {
                throw new Exception('Failed to get active privacy policy: ' . $e->getMessage());
            }
            break;
            
        case 'save_privacy_policy':
            try {
                $title = $_POST['title'] ?? '';
                $content = $_POST['content'] ?? '';
                $version = $_POST['version'] ?? '1.0';
                $status = $_POST['status'] ?? 'active';
                $policyId = intval($_POST['policy_id'] ?? 0);
                
                if (empty($title) || empty($content)) {
                    throw new Exception('Title and content are required');
                }
                
                if ($policyId > 0) {
                    // Update existing policy
                    $stmt = $conn->prepare("UPDATE privacy_policy SET title = :title, content = :content, version = :version, status = :status WHERE id = :id");
                    $stmt->bindParam(':id', $policyId);
                } else {
                    // Insert new policy
                    $stmt = $conn->prepare("INSERT INTO privacy_policy (title, content, version, status) VALUES (:title, :content, :version, :status)");
                }
                
                $stmt->bindParam(':title', $title);
                $stmt->bindParam(':content', $content);
                $stmt->bindParam(':version', $version);
                $stmt->bindParam(':status', $status);
                
                $stmt->execute();
                
                echo json_encode(['success' => true, 'message' => 'Privacy policy saved successfully']);
            } catch (Exception $e) {
                throw new Exception('Failed to save privacy policy: ' . $e->getMessage());
            }
            break;
            
        case 'delete_privacy_policy':
            try {
                $policyId = intval($_POST['policy_id'] ?? 0);
                
                if (empty($policyId)) {
                    throw new Exception('Policy ID is required');
                }
                
                // Don't allow deletion of the last active policy
                $stmt = $conn->prepare("SELECT COUNT(*) as count FROM privacy_policy WHERE status = 'active'");
                $stmt->execute();
                $activeCount = $stmt->fetch()['count'];
                
                $stmt = $conn->prepare("SELECT status FROM privacy_policy WHERE id = :id");
                $stmt->bindParam(':id', $policyId);
                $stmt->execute();
                $policy = $stmt->fetch();
                
                if ($policy && $policy['status'] === 'active' && $activeCount <= 1) {
                    throw new Exception('Cannot delete the last active privacy policy');
                }
                
                $stmt = $conn->prepare("DELETE FROM privacy_policy WHERE id = :id");
                $stmt->bindParam(':id', $policyId);
                $stmt->execute();
                
                echo json_encode(['success' => true, 'message' => 'Privacy policy deleted successfully']);
            } catch (Exception $e) {
                throw new Exception('Failed to delete privacy policy: ' . $e->getMessage());
            }
            break;
            
        case 'get_visitor_data':
            try {
                // Get visitor data from activity_log table
                $stmt = $conn->prepare("SELECT username, action, ip_address, created_at FROM activity_log ORDER BY created_at DESC LIMIT 100");
                $stmt->execute();
                $visitors = $stmt->fetchAll();
                
                // Add location information to each visitor
                foreach ($visitors as &$visitor) {
                    $visitor['location'] = get_location_from_ip($visitor['ip_address']);
                }
                
                echo json_encode(['success' => true, 'visitors' => $visitors]);
            } catch (Exception $e) {
                throw new Exception('Failed to get visitor data: ' . $e->getMessage());
            }
            break;
            
case 'toggle_privacy_policy_status':
            try {
                $policyId = intval($_POST['policy_id'] ?? 0);
                $status = $_POST['status'] ?? 'inactive';
                
                if (empty($policyId)) {
                    throw new Exception('Policy ID is required');
                }
                
                $stmt = $conn->prepare("UPDATE privacy_policy SET status = :status WHERE id = :id");
                $stmt->bindParam(':id', $policyId);
                $stmt->bindParam(':status', $status);
                $stmt->execute();
                
                echo json_encode(['success' => true, 'message' => 'Privacy policy status updated successfully']);
            } catch (Exception $e) {
                throw new Exception('Failed to update privacy policy status: ' . $e->getMessage());
            }
            break;
        case 'get_feedback_list':
            try {
                // Get all contact submissions from database
                $stmt = $conn->query("SELECT id, name, email, subject, submitted_at, 
                    CASE WHEN reply_message IS NOT NULL AND reply_message != '' THEN 'replied' ELSE 'new' END as status 
                    FROM contact_submissions ORDER BY submitted_at DESC");
                $feedback = $stmt->fetchAll();
                
                echo json_encode(['success' => true, 'feedback' => $feedback]);
            } catch (Exception $e) {
                throw new Exception('Failed to fetch feedback: ' . $e->getMessage());
            }
            break;

        case 'get_feedback_detail':
            $feedbackId = intval($_GET['id'] ?? 0);
            
            if (empty($feedbackId)) {
                throw new Exception('Feedback ID is required');
            }
            
            try {
                // Get specific contact submission with reply info
                $stmt = $conn->prepare("SELECT id, name, email, subject, message, submitted_at, ip_address, user_agent,
                    CASE WHEN reply_message IS NOT NULL AND reply_message != '' THEN 'replied' ELSE 'new' END as status,
                    reply_message, reply_subject, reply_sent_at
                    FROM contact_submissions WHERE id = ?");
                $stmt->execute([$feedbackId]);
                $feedback = $stmt->fetch();
                
                if (!$feedback) {
                    throw new Exception('Feedback not found');
                }
                
                echo json_encode(['success' => true, 'feedback' => $feedback]);
            } catch (Exception $e) {
                throw new Exception('Failed to fetch feedback detail: ' . $e->getMessage());
            }
            break;

        case 'send_feedback_reply':
            $feedbackId = intval($_POST['feedback_id'] ?? 0);
            $toEmail = $_POST['to_email'] ?? '';
            $subject = $_POST['subject'] ?? '';
            $message = $_POST['message'] ?? '';
            
            if (empty($feedbackId) || empty($toEmail) || empty($subject) || empty($message)) {
                throw new Exception('All fields are required');
            }
            
            // Validate email
            if (!filter_var($toEmail, FILTER_VALIDATE_EMAIL)) {
                throw new Exception('Invalid email address');
            }
            
            try {
                // Update contact submission with reply info
                $stmt = $conn->prepare("UPDATE contact_submissions SET 
                    reply_message = ?, reply_subject = ?, reply_sent_at = NOW() 
                    WHERE id = ?");
                $result = $stmt->execute([$message, $subject, $feedbackId]);
                
                if (!$result) {
                    throw new Exception('Failed to update feedback');
                }
                
                // Send actual email using Gmail SMTP
                $fullMessage = "Hello,\n\nYou received a reply to your feedback submission:\n\n" . $message . "\n\nBest regards,\nAdmin Team";
                
                $mailConfig = getMailConfigFromDB();
                $mailResult = sendGmailSMTP(
                    $toEmail, 
                    $subject, 
                    $fullMessage, 
                    $mailConfig
                );
                
                if ($mailResult === true) {
                    echo json_encode(['success' => true, 'message' => 'Reply sent successfully to user via Gmail SMTP!']);
                } else {
                    echo json_encode(['success' => true, 'message' => 'Reply recorded but email delivery failed: ' . $mailResult]);
                }
            } catch (Exception $e) {
                throw new Exception('Failed to send reply: ' . $e->getMessage());
            }
            break;

        // ==================== MAIL CONFIGURATION ====================
        case 'get_mail_config':
            try {
                // Get mail configuration from database
                $stmt = $conn->prepare("SELECT smtp_host, smtp_port, smtp_username, smtp_password, from_email, from_name FROM mail_settings ORDER BY id DESC LIMIT 1");
                $stmt->execute();
                $config = $stmt->fetch();
                
                if (!$config) {
                    // Return default configuration if none exists
                    $config = [
                        'smtp_host' => 'ssl://smtp.gmail.com',
                        'smtp_port' => 465,
                        'smtp_username' => '',
                        'smtp_password' => '',
                        'from_email' => '',
                        'from_name' => 'Admin Team'
                    ];
                } else {
                    // Don't return the password for security
                    unset($config['smtp_password']);
                }
                
                echo json_encode(['success' => true, 'config' => $config]);
            } catch (Exception $e) {
                throw new Exception('Failed to get mail config: ' . $e->getMessage());
            }
            break;

        case 'save_mail_config':
            try {
                // Get JSON data from request body
                $input = json_decode(file_get_contents('php://input'), true);
                
                if (!$input) {
                    throw new Exception('Invalid JSON data');
                }
                
                // Define default values
                $defaults = [
                    'smtp_host' => 'ssl://smtp.gmail.com',
                    'smtp_port' => 465,
                    'smtp_username' => '',
                    'smtp_password' => '',
                    'from_email' => '',
                    'from_name' => 'Admin Team'
                ];
                
                // Merge input with defaults
                $config = array_merge($defaults, $input);
                
                // Check if there's an existing record
                $stmt = $conn->prepare("SELECT id, smtp_password FROM mail_settings ORDER BY id DESC LIMIT 1");
                $stmt->execute();
                $existing = $stmt->fetch();
                
                // If password is empty in input, keep existing password
                if (isset($input['smtp_password']) && empty($input['smtp_password']) && $existing) {
                    $config['smtp_password'] = $existing['smtp_password'];
                }
                
                if ($existing) {
                    // Update existing record
                    $stmt = $conn->prepare("UPDATE mail_settings SET 
                        smtp_host = :smtp_host,
                        smtp_port = :smtp_port,
                        smtp_username = :smtp_username,
                        smtp_password = :smtp_password,
                        from_email = :from_email,
                        from_name = :from_name
                        WHERE id = :id");
                    
                    $stmt->bindParam(':id', $existing['id']);
                    $stmt->bindParam(':smtp_host', $config['smtp_host']);
                    $stmt->bindParam(':smtp_port', $config['smtp_port']);
                    $stmt->bindParam(':smtp_username', $config['smtp_username']);
                    $stmt->bindParam(':smtp_password', $config['smtp_password']);
                    $stmt->bindParam(':from_email', $config['from_email']);
                    $stmt->bindParam(':from_name', $config['from_name']);
                } else {
                    // Insert new record
                    $stmt = $conn->prepare("INSERT INTO mail_settings 
                        (smtp_host, smtp_port, smtp_username, smtp_password, from_email, from_name)
                        VALUES 
                        (:smtp_host, :smtp_port, :smtp_username, :smtp_password, :from_email, :from_name)");
                    
                    $stmt->bindParam(':smtp_host', $config['smtp_host']);
                    $stmt->bindParam(':smtp_port', $config['smtp_port']);
                    $stmt->bindParam(':smtp_username', $config['smtp_username']);
                    $stmt->bindParam(':smtp_password', $config['smtp_password']);
                    $stmt->bindParam(':from_email', $config['from_email']);
                    $stmt->bindParam(':from_name', $config['from_name']);
                }
                
                $stmt->execute();
                
                echo json_encode(['success' => true, 'message' => 'Mail configuration saved successfully']);
            } catch (Exception $e) {
                throw new Exception('Failed to save mail config: ' . $e->getMessage());
            }
            break;

        default:
            throw new Exception('Invalid action');
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

function get_location_from_ip($ip) {
    // For localhost IPs, return local information
    if ($ip == '::1' || $ip == '127.0.0.1' || $ip == 'localhost') {
        return 'Localhost, Local Network';
    }
    
    // Use ip-api.com for real location detection
    try {
        // Make request to ip-api.com with fields we need
        $url = "http://ip-api.com/json/" . urlencode($ip) . "?fields=status,message,country,regionName,city,isp";
        $response = @file_get_contents($url);
        
        if ($response) {
            $data = json_decode($response, true);
            
            if ($data && $data['status'] === 'success') {
                // Build location string with city, region, country
                $location_parts = [];
                if (!empty($data['city'])) {
                    $location_parts[] = $data['city'];
                }
                if (!empty($data['regionName'])) {
                    $location_parts[] = $data['regionName'];
                }
                if (!empty($data['country'])) {
                    $location_parts[] = $data['country'];
                }
                
                $location = implode(', ', $location_parts);
                
                // Add ISP information if available
                if (!empty($data['isp'])) {
                    $location .= ' (' . $data['isp'] . ')';
                }
                
                return $location ?: 'Unknown Location';
            } else if ($data && !empty($data['message'])) {
                return 'Location Error: ' . $data['message'];
            }
        }
    } catch (Exception $e) {
        // Fall back to placeholder if API fails
        error_log("Location API error for IP $ip: " . $e->getMessage());
    }
    
    return 'Unknown Location';
}
