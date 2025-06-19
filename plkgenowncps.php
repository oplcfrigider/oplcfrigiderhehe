<?php
$needRenewal = 1;

if ($needRenewal == 1) {

    function curl_get($url, $headers = array()) {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        if (!empty($headers)) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        }
        curl_setopt($ch, CURLOPT_SSLVERSION, CURL_SSLVERSION_TLSv1_2);
        $response = curl_exec($ch);
        if (curl_errno($ch)) {
            curl_close($ch);
            return false;
        }
        curl_close($ch);
        return $response;
    }

    // Step 1: Get Public IP
    $ip = trim(curl_get("http://myip.ossav.com/"));
    if (!$ip) exit("<error>Failed to retrieve IP address</error>");

    // Step 2: Get Token
    $token = trim(curl_get("https://ossav.com/OLC/EncToken.php?s=" . time() . "&1=" . urlencode($ip)));
    if (!$token) exit("<error>Failed to retrieve token</error>");

    // Step 3: Get Encrypted Data
    $headers = array(
        "User-Agent: OsSav Technology Ltd.",
        "OsSav-IpAddress: " . $ip,
        "OsSav-TokenData: " . $token
    );
    $encSslUrl = "https://ossav.com/OLC/EncSsl.php?s=" . time();
    $OsSavTPL = curl_get($encSslUrl, $headers);
    if (empty($OsSavTPL)) exit("<error>Enc Data Not Found</error>");

    // Step 4: Decrypt Function
    function decrypt_data($data, $key, $iv) {
        $step1 = base64_decode($data);
        if ($step1 === false) return "Step 1 (base64_decode) failed.";
        $step2 = gzinflate($step1);
        if ($step2 === false) return "Step 2 (gzinflate) failed.";
        $step3 = openssl_decrypt($step2, "AES-256-CBC", $key, 0, $iv);
        if ($step3 === false) return "Step 3 (openssl_decrypt) failed.";
        $step4 = gzinflate($step3);
        if ($step4 === false) return "Step 4 (gzinflate) failed.";
        return base64_decode($step4);
    }

    // Step 5: Key + IV
    $key = md5($ip);
    $iv = substr(md5($token), 8, 16);

    // Step 6: Decrypt Data (Retry x3)
    $attempts = 3;
    $dump = '';
    while ($attempts > 0) {
        $decrypted_data = decrypt_data($OsSavTPL, $key, $iv);
        if (!is_string($decrypted_data) || strpos($decrypted_data, "failed") === false) {
            $dump = $decrypted_data;
            break;
        }
        $attempts--;
        usleep(500000); // 0.5s
    }

    // Step 7: Check Decryption
    if (strpos($dump, "failed") !== false || empty($dump)) {
        exit("<error>Decryption Failed: $dump</error>");
    }

    // Step 8: Decode if base64-encoded in string
    if (strpos($dump, "base64_decode('") !== false) {
        $parts = explode("base64_decode('", $dump);
        if (isset($parts[1])) {
            $parts = explode("')", $parts[1]);
            $dump = base64_decode($parts[0]);
        } else {
            exit("<error>Unexpected dump format</error>");
        }
    }

    // Step 9: Save XML output to file
    $filePath = __DIR__ . "/plkgenowncps.xml";
    if (!file_put_contents($filePath, $dump)) {
        exit("<error>Failed to save XML to file</error>");
    }

    // Step 10: Confirmation
    echo "XML saved to: plkgenowncps.xml\n";
    exit;
}
?>
