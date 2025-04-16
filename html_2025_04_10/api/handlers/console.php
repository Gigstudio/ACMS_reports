<?php
use GigReportServer\System\Engine\Console;

$action = $input['action'] ?? null;

switch ($action) {
    case 'get':
        echo json_encode(Console::getMessages($input['parameters']['filter'] ?? '0'));
        break;

    case 'clear':
        Console::clearMessages();
        echo json_encode(['status' => 'success', 'message' => 'Консоль очищена']);
        break;

    default:
        echo json_encode(['status' => 'error', 'message' => 'Неизвестное действие']);
}
