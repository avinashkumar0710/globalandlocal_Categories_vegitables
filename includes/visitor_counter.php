<?php
// includes/visitor_counter.php
// Provides $visitorCount (int) representing unique visitors for today (unique IP per day).
// Data files: data/visitor_log.json, data/visitor_count.txt

$visitorCount = 0;
try {
    $dataDir = __DIR__ . '/../data';
    if (!is_dir($dataDir)) {
        mkdir($dataDir, 0755, true);
    }
    $logFile = $dataDir . '/visitor_log.json';
    $countFile = $dataDir . '/visitor_count.txt';

    // helper to get client IP (respect X-Forwarded-For when behind proxies)
    function get_client_ip() {
        $keys = ['HTTP_CLIENT_IP','HTTP_X_FORWARDED_FOR','HTTP_X_FORWARDED','HTTP_X_CLUSTER_CLIENT_IP','HTTP_FORWARDED_FOR','REMOTE_ADDR'];
        foreach ($keys as $k) {
            if (!empty($_SERVER[$k])) {
                $ips = explode(',', $_SERVER[$k]);
                return trim(reset($ips));
            }
        }
        return '0.0.0.0';
    }

    $ip = filter_var(get_client_ip(), FILTER_VALIDATE_IP) ? get_client_ip() : '0.0.0.0';
    $today = date('Y-m-d');

    // Acquire a lock using a temp file
    $lockFile = $dataDir . '/visitor_log.lock';
    $fpLock = fopen($lockFile, 'c');
    if ($fpLock) {
        flock($fpLock, LOCK_EX);

        // load existing log
        $log = ['date' => $today, 'ips' => []];
        if (file_exists($logFile)) {
            $raw = file_get_contents($logFile);
            $decoded = json_decode($raw, true);
            if (is_array($decoded) && isset($decoded['date']) && isset($decoded['ips'])) {
                // if date matches today, keep; else reset
                if ($decoded['date'] === $today) {
                    $log = $decoded;
                } else {
                    $log = ['date' => $today, 'ips' => []];
                }
            }
        }

        // if IP not present, add and increment count
        $isNew = false;
        if (!in_array($ip, $log['ips'])) {
            $log['ips'][] = $ip;
            $isNew = true;
            // update count file
            $count = 0;
            if (file_exists($countFile)) {
                $count = (int)file_get_contents($countFile);
            }
            $count++;
            file_put_contents($countFile, (string)$count, LOCK_EX);
        }

        // persist log
        file_put_contents($logFile, json_encode($log, JSON_PRETTY_PRINT), LOCK_EX);

        // release lock
        flock($fpLock, LOCK_UN);
        fclose($fpLock);
    }

    // read final count
    if (file_exists($countFile)) {
        $visitorCount = (int)file_get_contents($countFile);
    } else {
        // fallback to length of ips in log
        if (isset($log['ips']) && is_array($log['ips'])) $visitorCount = count($log['ips']);
    }

} catch (Exception $e) {
    // on any error, default to 0
    $visitorCount = isset($visitorCount) ? (int)$visitorCount : 0;
}
?>
