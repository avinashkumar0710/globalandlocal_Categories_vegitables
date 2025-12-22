<?php
// Public API endpoints that don't require authentication
require_once 'config/database.php';

header('Content-Type: application/json');

// Enable CORS for frontend requests
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Standalone location function to avoid including api.php which requires login
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

$action = $_GET['action'] ?? '';

try {
    $database = new Database();
    $conn = $database->getConnection();
    
    // Test database connection
    if (!$conn) {
        throw new Exception('Database connection failed');
    }
    
    switch ($action) {
        case 'get_visitor_data':
            // Get data from activity_log table (admin activities)
            $stmt = $conn->prepare("SELECT username, action, ip_address, created_at FROM activity_log ORDER BY created_at DESC");
            $stmt->execute();
            $activityVisitors = $stmt->fetchAll();
            
            // Add location information to each activity visitor
            foreach ($activityVisitors as &$visitor) {
                $visitor['location'] = get_location_from_ip($visitor['ip_address']);
            }
            
            // Get data from visitor log files (website visits)
            $fileVisitors = [];
            $logFile = __DIR__ . '/data/visitor_log.json';
            
            if (file_exists($logFile)) {
                $logData = json_decode(file_get_contents($logFile), true);
                if (isset($logData['ips']) && is_array($logData['ips'])) {
                    foreach ($logData['ips'] as $ip) {
                        $fileVisitors[] = [
                            'username' => 'Website Visitor',
                            'action' => 'PAGE_VISIT',
                            'ip_address' => $ip,
                            'location' => get_location_from_ip($ip),
                            'created_at' => date('Y-m-d H:i:s')
                        ];
                    }
                }
            }
            
            // Combine both datasets
            $allVisitors = array_merge($activityVisitors, $fileVisitors);
            
            // Sort by created_at descending
            usort($allVisitors, function($a, $b) {
                return strtotime($b['created_at']) - strtotime($a['created_at']);
            });
            
            // Limit to 100 most recent entries
            $allVisitors = array_slice($allVisitors, 0, 100);
            
            echo json_encode(['success' => true, 'visitors' => array_values($allVisitors)]);
            break;
            
        default:
            throw new Exception('Invalid action: ' . $action);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>