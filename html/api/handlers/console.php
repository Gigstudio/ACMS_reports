<?php
use GigReportServer\System\Engine\Console;

$action = $input['action'] ?? null;

switch ($action) {
    case 'get':
        echo json_encode(Console::getMessages($input['filter'] ?? '0'));
        break;

    case 'clear':
        Console::clearMessages();
        echo json_encode(['status' => 'success', 'message' => 'Консоль очищена']);
        break;

    case 'add':
        $data = $input['data'] ?? [];
        $result = Console::addMessage($data['level'], $data['source'], $data['message']);
        if($result === true){
            echo json_encode(['status' => 'success', 'message' => 'Сообщение добавлено']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Ошибка при создании системного сообщения']);
        }
        break;


    default:
        echo json_encode(['status' => 'error', 'message' => 'Неизвестное действие']);
}
