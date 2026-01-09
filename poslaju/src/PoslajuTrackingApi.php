<?php
// src/PoslajuTrackingApi.php
// This is the MISSING main class file!

namespace Afzafri;

class PoslajuTrackingApi
{
    public static function crawl($trackingNo, $include_info = false)
    {
        $domain = "ttu-svc.pos.com.my";
        $url = "https://$domain/api/trackandtrace/v1/request";
        $postData = json_encode([
            "connote_ids" => [trim($trackingNo)],
            "culture" => "en"
        ]);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        
        // Optimized timeout settings
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);           // Total timeout: 15 seconds
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);     // Connection timeout: 5 seconds
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);   // Follow redirects
        curl_setopt($ch, CURLOPT_MAXREDIRS, 3);          // Max 3 redirects
        
        // Performance optimizations
        curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_2_0); // Use HTTP/2 if available
        curl_setopt($ch, CURLOPT_ENCODING, '');          // Enable all supported encodings
        curl_setopt($ch, CURLOPT_TCP_KEEPALIVE, 1);      // Keep connection alive
        curl_setopt($ch, CURLOPT_TCP_KEEPIDLE, 600);     // Keep alive for 10 minutes
        curl_setopt($ch, CURLOPT_TCP_KEEPINTVL, 60);     // Send keep-alive every 60 seconds
        
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
        
        // Optimized headers
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'P-Request-ID: ' . self::generateUUID(),
            'Accept: application/json, text/plain, */*',
            'Content-Type: application/json',
            'Accept-Encoding: gzip, deflate, br',        // Enable compression
            'Connection: keep-alive',                     // Keep connection alive
            'Cache-Control: no-cache',                    // Don't cache
            'User-Agent: Mozilla/5.0 (compatible; PoslajuTracker/1.0)',
            'host: ' . $domain
        ]);
        
        // Measure execution time
        $start_time = microtime(true);
        
        $result = curl_exec($ch);
        $httpstatus = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $transfer_time = curl_getinfo($ch, CURLINFO_TOTAL_TIME);
        $errormsg = (curl_error($ch)) ? curl_error($ch) : "No error";
        curl_close($ch);
        
        $execution_time = round((microtime(true) - $start_time) * 1000, 2); // Convert to milliseconds

        // Initialize response array
        $trackres = array();
        $trackres['http_code'] = $httpstatus;
        $trackres['error_msg'] = $errormsg;
        $trackres['execution_time'] = $execution_time . 'ms';
        $trackres['transfer_time'] = round($transfer_time * 1000, 2) . 'ms';

        // Better error handling
        if ($result === false) {
            $trackres['status'] = 0;
            $trackres['message'] = "Connection failed: " . $errormsg;
            return $trackres;
        }

        // Parse JSON response
        $jsonData = json_decode($result, true);
        
        // Improved JSON error handling
        if (json_last_error() !== JSON_ERROR_NONE) {
            $trackres['status'] = 0;
            $trackres['message'] = "Invalid JSON response: " . json_last_error_msg();
            $trackres['raw_response'] = substr($result, 0, 500); // First 500 chars for debugging
            return $trackres;
        }
        
        // Check if response is valid and has tracking data
        if ($httpstatus == 200 && !empty($jsonData) && isset($jsonData['data']) && !empty($jsonData['data'])) {
            $trackingData = $jsonData['data'][0] ?? null;
            
            if ($trackingData && isset($trackingData['tracking_data']) && !empty($trackingData['tracking_data'])) {
                $trackres['status'] = 1;
                $trackres['message'] = "Record Found";

                // Process tracking events
                $trackres['data'] = [];
                foreach ($trackingData['tracking_data'] as $i => $event) {
                    $trackres['data'][$i] = [
                        'date_time' => isset($event['date']) ? $event['date'] : '',
                        'process' => isset($event['process']) ? $event['process'] : '',
                        'event' => isset($event['process_summary']) ? $event['process_summary'] : '',
                    ];
                }
                
                // Add package info if available
                if (isset($trackingData['connote_id'])) {
                    $trackres['tracking_number'] = $trackingData['connote_id'];
                }
                if (isset($trackingData['status'])) {
                    $trackres['overall_status'] = $trackingData['status'];
                }
                
            } else {
                $trackres['status'] = 0;
                $trackres['message'] = "No tracking data found for this number";
            }
        } elseif ($httpstatus != 200) {
            $trackres['status'] = 0;
            $trackres['message'] = "API returned HTTP $httpstatus";
        } else {
            $trackres['status'] = 0;
            $trackres['message'] = "No Record Found";
        }

        if ($include_info) {
            // Project info
            $trackres['info']['creator'] = "Afif Zafri (afzafri)";
            $trackres['info']['project_page'] = "https://github.com/afzafri/Poslaju-Tracking-API";
            $trackres['info']['date_updated'] = "24/09/2025";
            $trackres['info']['optimization'] = "Enhanced with performance improvements";
        }

        return $trackres;
    }

    // Optimized UUID generation
    private static function generateUUID()
    {
        // Use more efficient method if available
        if (function_exists('random_bytes')) {
            $data = random_bytes(16);
            $data[6] = chr(ord($data[6]) & 0x0f | 0x40); // Version 4
            $data[8] = chr(ord($data[8]) & 0x3f | 0x80); // Variant 1
            
            return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
        } else {
            // Fallback for older PHP versions
            return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
                mt_rand(0, 0xffff), mt_rand(0, 0xffff),
                mt_rand(0, 0xffff),
                mt_rand(0, 0x0fff) | 0x4000,
                mt_rand(0, 0x3fff) | 0x8000,
                mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
            );
        }
    }
}