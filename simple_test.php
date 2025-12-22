<?php
// Simple test endpoint to check if the server is working

header('Content-Type: application/json');

echo json_encode(['success' => true, 'message' => 'Server is working correctly']);
?>