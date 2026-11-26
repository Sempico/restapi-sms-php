<?php

// Router for the PHP built-in server used by ApiClientTest.
// It answers like the Sempico Solutions API and echoes the request back.

$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
header('Content-Type: application/json');

$echo = [
    'method'       => $_SERVER['REQUEST_METHOD'],
    'path'         => $path,
    'token'        => $_SERVER['HTTP_X_ACCESS_TOKEN'] ?? null,
    'content_type' => $_SERVER['CONTENT_TYPE'] ?? null,
    'user_agent'   => $_SERVER['HTTP_USER_AGENT'] ?? null,
    'body'         => file_get_contents('php://input'),
];

switch ($path) {
    case '/v1/unauthorized':
        http_response_code(401);
        echo json_encode(['status' => false, 'error_code' => 401, 'errors' => 'Invalid Access token']);
        break;
    case '/v1/rate-limit':
        http_response_code(429);
        header('Retry-After: 60');
        echo json_encode(['status' => false, 'error_code' => 429, 'errors' => 'Too Many Requests']);
        break;
    case '/v1/not-json':
        http_response_code(502);
        header('Content-Type: text/html');
        echo '<html>Bad Gateway</html>';
        break;
    case '/v1/slow':
        sleep(3);
        echo json_encode(['status' => true, 'data' => []]);
        break;
    default:
        echo json_encode(['status' => true, 'data' => $echo]);
}
