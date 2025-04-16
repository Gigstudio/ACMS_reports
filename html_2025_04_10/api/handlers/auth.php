<?php
use GigReportServer\System\Engine\Console;

$action = $input['action'] ?? null;

switch ($action) {
    case 'getmodal':
        include PATH_VIEWS . '/login.php';
        break;

    case 'login':
        echo json_encode(['status' => 'success', 'message' => 'Консоль очищена']);
        break;

    case 'register':
        echo json_encode(['status' => 'success', 'message' => 'Сообщение добавлено']);
        break;

    default:
        echo json_encode(['status' => 'error', 'message' => 'Неизвестное действие']);
}
