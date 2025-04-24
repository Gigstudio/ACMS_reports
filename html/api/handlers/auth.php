<?php
use GigReportServer\Pages\Controllers\AuthController;
use GigReportServer\System\Engine\Application;

$action = $input['action'] ?? null;

switch ($action) {
    case 'getmodal':
        include PATH_VIEWS . '/login.php';
        break;

    case 'login':
        $controller = new AuthController;
        $result = $controller->login($input['data'] ?? []);

        Application::getInstance()->response->json($result);
        break;

    case 'register':
        $controller = new AuthController;
        $result = $controller->register($input['data'] ?? []);

        Application::getInstance()->response->json($result);
        break;

        case 'logout':
        $controller = new AuthController;
        $result = $controller->logout();

        Application::getInstance()->response->json($result);
        break;

    default:
        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode(['status' => 'error', 'message' => 'Неизвестное действие'], JSON_UNESCAPED_UNICODE);
}
