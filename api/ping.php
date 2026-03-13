<?php
header('Content-Type: application/json');
echo json_encode([
    'status' => 'online',
    'timestamp' => time(),
    'environment' => 'vercel-serverless',
    'php_version' => phpversion()
]);
