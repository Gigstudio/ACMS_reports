<?php
return [
    'GET' => [
        '/api/console' => [GIG\API\Controller\ConsoleController::class, 'get'],
        '/api/console/count' => [\GIG\API\Controller\ConsoleController::class, 'count'],
        '/api/console/last' => [\GIG\API\Controller\ConsoleController::class, 'last'],
    ],
    'POST' => [
        '/api/console' => [GIG\API\Controller\ConsoleController::class, 'add'],
    ],
    'DELETE' => [
        '/api/console' => [GIG\API\Controller\ConsoleController::class, 'clear']
    ]
];