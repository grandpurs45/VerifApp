<?php

declare(strict_types=1);

$socket = @fsockopen('127.0.0.1', 80, $errno, $errstr, 5.0);
if ($socket === false) {
    fwrite(STDERR, sprintf("HTTP connect failed: %s (%d)\n", $errstr, $errno));
    exit(1);
}

stream_set_timeout($socket, 5);
fwrite($socket, "GET /health.php HTTP/1.1\r\nHost: 127.0.0.1\r\nConnection: close\r\n\r\n");
$response = stream_get_contents($socket);
fclose($socket);

if ($response === false || !str_contains($response, "\r\n\r\n")) {
    fwrite(STDERR, "Invalid health response\n");
    exit(1);
}

[$headers] = explode("\r\n\r\n", $response, 2);
if (!str_contains($headers, ' 200 ')) {
    fwrite(STDERR, "Health endpoint did not return HTTP 200\n");
    exit(1);
}

if (!str_contains($response, '"status":"ok"') || !str_contains($response, '"db":"ok"')) {
    fwrite(STDERR, "Health payload is not ok\n");
    exit(1);
}

exit(0);
