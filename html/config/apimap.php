<?php
return [
    'GET' => [
        '/api/console'              => [GIG\Api\Controller\ConsoleController::class, 'get'],
        '/api/console/count'        => [GIG\Api\Controller\ConsoleController::class, 'count'],
        '/api/console/last'         => [GIG\Api\Controller\ConsoleController::class, 'last'],
        '/api/tasks'                => [GIG\Api\Controller\TaskController::class, 'list'],
        '/api/tasks/{id}'           => [GIG\Api\Controller\TaskController::class, 'view'],
        '/api/status'               => [GIG\Api\Controller\StatusController::class, 'getAll'],
        '/api/status/stream'        => [GIG\Api\Controller\StatusController::class, 'stream'],
        '/api/auth/modal'           => [GIG\Api\Controller\AuthController::class, 'getModal'],
        '/api/background/workers'   => [GIG\Api\Controller\TaskController::class, 'getWorkers'],
    ],
    'POST' => [
        '/api/console'              => [GIG\Api\Controller\ConsoleController::class, 'add'],
        '/api/tasks'                => [GIG\Api\Controller\TaskController::class, 'create'],
        '/api/tasks/{id}/cancel'    => [GIG\Api\Controller\TaskController::class, 'cancel'],
        '/api/auth/login'           => [GIG\Api\Controller\AuthController::class, 'login'],
        '/api/auth/badge-check'     => [GIG\Api\Controller\AuthController::class, 'badgeCheck'],
        '/api/auth/login-check'     => [GIG\Api\Controller\AuthController::class, 'loginCheck'],
        '/api/auth/register'        => [GIG\Api\Controller\AuthController::class, 'register'],
        '/api/background/workers'   => [GIG\Api\Controller\TaskController::class, 'workerAction'],
    ],
    'DELETE' => [
        '/api/console'              => [GIG\Api\Controller\ConsoleController::class, 'clear'],
        '/api/tasks/{id}'           => [GIG\Api\Controller\TaskController::class, 'delete'],
    ]
];