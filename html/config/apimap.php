<?php
return [
    'GET' => [
        '/api/console'              => [GIG\API\Controller\ConsoleController::class, 'get'],
        '/api/console/count'        => [GIG\API\Controller\ConsoleController::class, 'count'],
        '/api/console/last'         => [GIG\API\Controller\ConsoleController::class, 'last'],
        '/api/tasks'                => [GIG\Api\Controller\TaskController::class, 'list'],
        '/api/tasks/{id}'           => [GIG\Api\Controller\TaskController::class, 'view'],
    ],
    'POST' => [
        '/api/console'              => [GIG\API\Controller\ConsoleController::class, 'add'],
        '/api/tasks'                => [GIG\Api\Controller\TaskController::class, 'create'],
        '/api/tasks/{id}/cancel'    => [GIG\Api\Controller\TaskController::class, 'cancel'],
    ],
    'DELETE' => [
        '/api/console'              => [GIG\API\Controller\ConsoleController::class, 'clear'],
        '/api/tasks/{id}'           => [GIG\Api\Controller\TaskController::class, 'delete'],
    ]
];