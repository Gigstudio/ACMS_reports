<?php
use GigReportServer\Pages\Controllers\AuthController;

$action = $input['action'] ?? null;

switch ($action) {
    case 'getmodal':
        include PATH_VIEWS . '/login.php';
        break;

    case 'login':
        $userdata = (new AuthController)->login($input['data']);
        // var_dump($userdata);
        echo json_encode($userdata, JSON_INVALID_UTF8_IGNORE);
        break;

    case 'register':
        echo (new AuthController)->register($input);
        // echo json_encode(['status' => 'success', 'message' => 'Данные для регистрации получены']);
        break;

    case 'logout':
        // echo json_encode(['status' => 'success', 'message' => 'Данные для регистрации получены']);
        break;

    default:
        echo json_encode(['status' => 'error', 'message' => 'Неизвестное действие']);
}
