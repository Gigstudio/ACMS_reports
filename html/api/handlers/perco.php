<?php
use GigReportServer\System\Engine\Application;
use GigReportServer\System\Engine\Event;

$action = $input['action'] ?? null;
$param = $input['data'] ?? [];

switch ($action) {
    case 'lookup':
        $identifier = $param['identifier'] ?? '';
        if (!$identifier) {
            echo json_encode([
                'status' => 'error',
                'message' => 'Номер пропуска не передан'
            ]);
            break;
        }

        try {
            new Event(Event::EVENT_INFO, 'api/perco.php', "Получен запрос на поиск пользователя PERCo-Web по номеру пропуска $identifier");
            $perco = Application::getInstance()->percoWebClient;
            $card = $perco->getUserByIdentifier($identifier);

            if (!isset($card['user_id'])) {
                // new Event(Event::EVENT_INFO, 'api/perco.php', "Пользователь с номером пропуска $identifier в PERCo-Web не найден");
                echo json_encode([
                    'status' => 'not_found',
                    'message' => "Пользователь PERCo-Web по номеру пропуска $identifier не найден"
                ]);
                break;
            }

            $userData = $perco->getUserInfoById($card['user_id']);
            echo json_encode([
                'status' => 'success',
                'message' => 'Пользователь найден',
                'data' => $userData
            ]);
        } catch (\Throwable $e) {
            // new Event(Event::EVENT_ERROR, 'api/perco.php', "Ошибка при обращении к PERCo-Web API: {$e->getMessage()}");
            echo json_encode([
                'status' => 'error',
                'message' => 'Ошибка при обращении к PERCo-Web API',
                'detail' => $e->getMessage()
            ]);
        }
        break;

    default:
        echo json_encode([
            'status' => 'error',
            'message' => 'Неизвестное действие'
        ]);
}
