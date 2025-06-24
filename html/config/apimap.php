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
    ],
    'POST' => [
        '/api/console'              => [GIG\Api\Controller\ConsoleController::class, 'add'],
        '/api/tasks'                => [GIG\Api\Controller\TaskController::class, 'create'],
        '/api/tasks/{id}/cancel'    => [GIG\Api\Controller\TaskController::class, 'cancel'],
    ],
    'DELETE' => [
        '/api/console'              => [GIG\Api\Controller\ConsoleController::class, 'clear'],
        '/api/tasks/{id}'           => [GIG\Api\Controller\TaskController::class, 'delete'],
    ]
];