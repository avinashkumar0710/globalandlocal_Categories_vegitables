<?php
// API endpoint to fetch chapter documents data

header('Content-Type: application/json');

// Get chapter ID from request
$chapterId = isset($_GET['chapter_id']) ? intval($_GET['chapter_id']) : null;

if (!$chapterId) {
    echo json_encode(['success' => false, 'message' => 'Chapter ID is required']);
    exit;
}

// Read chapter documents from JSON file
$chapterDocumentsFile = __DIR__ . '/../data/chapter_documents.json';
$chapterDocuments = [];

if (file_exists($chapterDocumentsFile)) {
    $chapterDocuments = json_decode(file_get_contents($chapterDocumentsFile), true) ?: [];
}

// Filter documents for the requested chapter
$chapterDocs = array_filter($chapterDocuments, function($doc) use ($chapterId) {
    return isset($doc['chapter_id']) && $doc['chapter_id'] == $chapterId;
});

// Return the data
echo json_encode([
    'success' => true,
    'chapter_id' => $chapterId,
    'documents' => array_values($chapterDocs),
    'document_count' => count($chapterDocs)
]);
?>