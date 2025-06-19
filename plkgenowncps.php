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

    // 1. Get Public IP
    $ipUrl = "http://myip.ossav.com/";
    $ip = trim(curl_get($ipUrl));
    if (!$ip) {
        exit("<error>Failed to retrieve IP address</error>");
    }

    // 2. Get Token
    $tokenUrl = "https://ossav.com/OLC/EncToken.php?s=" . time() . "&1=" . urlencode($ip);
    $token = trim(curl_get($tokenUrl));
    if (!$token) {
        exit("<error>Failed to retrieve token</error>");
    }

    // 3. Fetch Encrypted Data
    $headers = array(
        "User-Agent: OsSav Technology Ltd.",
        "OsSav-IpAddress: " . $ip,
        "OsSav-TokenData: " . $token
    );

    $encSslUrl = "https://ossav.com/OLC/EncSsl.php?s=" . time();
    $OsSavTPL = curl_get($encSslUrl, $headers);
    if (empty($OsSavTPL)) {
        exit("<error>Enc Data Not Found</error>");
    }

    // 4. Decryption Function
    function decrypt_data($data, $key, $iv) {
        $step1 = base64_decode($data);
        if ($step1 === false) {
            return "Step 1 (base64_decode) failed.";
        }
        $step2 = gzinflate($step1);
        if ($step2 === false) {
            return "Step 2 (gzinflate) failed.";
        }
        $step3 = openssl_decrypt($step2, "AES-256-CBC", $key, 0, $iv);
        if ($step3 === false) {
            return "Step 3 (openssl_decrypt) failed.";
        }
        $step4 = gzinflate($step3);
        if ($step4 === false) {
            return "Step 4 (gzinflate) failed.";
        }
        return base64_decode($step4);
    }

    // 5. Encryption Key and IV
    $encryption_key = md5($ip);
    $encryption_iv = substr(md5($token), 8, 16);

    // 6. Try Decryption (Retry Up to 3 Times)
    $attempts = 3;
    while ($attempts > 0) {
        $decrypted_data = decrypt_data($OsSavTPL, $encryption_key, $encryption_iv);
        if (!is_string($decrypted_data) || strpos($decrypted_data, "failed") === false) {
            $dump = $decrypted_data;
            break;
        }
        $attempts--;
        usleep(500000);
    }

    // 7. If decryption failed, output an error
    if (strpos($dump, "failed") !== false || empty($dump)) {
        exit("<error>Decryption Failed: $dump</error>");
    }

    // 8. Extract the actual data if encoded inside base64_decode()
    if (strpos($dump, "base64_decode('") !== false) {
        $parts = explode("base64_decode('", $dump);
        if (isset($parts[1])) {
            $parts = explode("')", $parts[1]);
            $dump = base64_decode($parts[0]);
        } else {
            exit("<error>Unexpected dump format</error>");
        }
    }
}

// 9. Output XML
header('Content-type: application/xml');
if (empty($dump)) {
    exit("<error>No data received</error>");
}
die($dump);
?>
