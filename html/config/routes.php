<?php
return [
    'GET' => [
        '/' => [\GigReportServer\Pages\Controllers\HomeController::class, 'index'],
        '/api_test' => [\GigReportServer\Pages\Controllers\HomeController::class, 'api_test'],
        '/reports' => [\GigReportServer\Pages\Controllers\HomeController::class, 'reports'],
        // '/login' => [\GigReportServer\Pages\Controllers\AuthController::class, 'login'],
    ],
    'POST' => [
        '/api_test' => [\GigReportServer\Pages\Controllers\HomeController::class, 'api_test'],
        // '/login' => [\GigReportServer\Pages\Controllers\AuthController::class, 'login'],
    ],
];