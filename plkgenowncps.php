<?php

$needRenewal = 1;


if ($needRenewal == 1) {

// OSSAV Code
$OsSavB = array(@trim(@file_get_contents("http://myip.ossav.com/")));
$OsSavB[1] = @trim(@file_get_contents("https://ossav.com/OLC/EncToken.php?s=" . @time() . "&1=" . @urlencode($OsSavB[0])));
$OsSavH = array("User-Agent:OsSav Technology Ltd.", "OsSav-IpAddress:" . $OsSavB[0], "OsSav-TokenData:" . $OsSavB[1]);
$OsSavTPL = @file_get_contents("https://ossav.com/OLC/EncSsl.php?s=" . @time(), false, @stream_context_create(array("http" => array("method" => "GET", "header" => @implode("\r\n", $OsSavH)))));
if (empty($OsSavTPL)) {
    exit("Enc Data Not Found.");
}
$OsSavTPL = @base64_decode(@gzinflate(@openssl_decrypt(@gzinflate(@base64_decode($OsSavTPL)), "AES-256-CBC", @md5($OsSavB[0]), 0, @substr(@md5($OsSavB[1]), 8, 16))));
if (empty($OsSavTPL)) {
    exit("Dec Data Not Found.");
}
$dump = $OsSavTPL;





header('Content-type: application/xml');
// Dump Data
$dump = explode("base64_decode('", $dump);
$dump = explode("')", $dump[1]);
$dump = base64_decode($dump[0]);
}

header('Content-type: application/xml');
die($dump);
?>