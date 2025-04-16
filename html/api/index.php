<?php
require_once __DIR__ . '/../system/init.php';

header('Content-Type: application/json; charset=UTF-8');

$input = json_decode(file_get_contents('php://input'), true);
$module = $input['module'] ?? null;

if (!$module) {
    echo json_encode(['status' => 'error', 'message' => 'Команда не передана']);
    exit;
}

// Формируем путь к обработчику
$handlerFile = __DIR__ . "/handlers/{$module}.php";

if (file_exists($handlerFile)) {
    require_once $handlerFile;
} else {
    echo json_encode(['status' => 'error', 'message' => 'Неизвестная команда']);
}
exit;
