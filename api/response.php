<?php
function jsonResponse($httpCode, $status, $message, $data = null) {
    http_response_code($httpCode);
    header("Content-Type: application/json; charset=UTF-8");
    $response = [
        "status" => $status,
        "message" => $message
    ];
    if ($data !== null) {
        $response["data"] = $data;
    }
    echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit;
}

function requireMethod($method) {
    if ($_SERVER["REQUEST_METHOD"] !== $method) {
        header("Allow: " . $method);
        jsonResponse(405, false, "Method tidak diizinkan. Gunakan " . $method . ".");
    }
}
