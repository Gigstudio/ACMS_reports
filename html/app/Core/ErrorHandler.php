<?php
namespace GIG\Core;

defined('_RUNKEY') or die;

use GIG\Domain\Entities\Event;
use GIG\Domain\Services\EventManager;
use GIG\Core\ContextDetector;
use GIG\Presentation\Controller\MessageController;

class ErrorHandler
{
    protected static int $eventClass = Event::ERROR;

    public static function register(): void
    {
        set_error_handler([self::class, 'handleError']);
        set_exception_handler([self::class, 'handleException']);
        register_shutdown_function([self::class, 'handleShutdown']);
    }

    public static function handleError(int $errno, string $errstr, string $errfile, int $errline): void
    {
        $eventType = match ($errno) {
            E_USER_NOTICE, E_NOTICE        => Event::INFO,
            E_WARNING, E_USER_WARNING      => Event::WARNING,
            // E_ERROR, E_USER_ERROR,
            // E_CORE_ERROR, E_COMPILE_ERROR,
            // E_RECOVERABLE_ERROR            => Event::ERROR,
            default                        => Event::ERROR,
        };

        $message = self::composeMessage($eventType, $errno, $errstr, $errfile, $errline);

        EventManager::logParams($eventType, $errfile, $message);

        // self::dispatch($errno, [
        //     'title'   => EventManager::getTitle($eventType),
        //     'message' => $errstr,
        //     'detail'  => $message
        // ]);
    }

    public static function handleException(\Throwable $e): void
    {
        // --- Фильтрация всех мусорных (браузерных/ботовых) URI для GeneralException 404 ---
        if (
            $e instanceof \GIG\Domain\Exceptions\GeneralException &&
            $e->getCode() === 404
        ) {
            $uri = $_SERVER['REQUEST_URI'] ?? '';
            $ignoreUriPatterns = [
                '#^/\.well-known(/|$)#i',           // .well-known и всё внутри
                '#^/robots\.txt$#i',                // robots.txt
                '#^/favicon\.ico$#i',               // favicon.ico
                '#^/apple-touch-icon.*\.png$#i',    // apple-touch-icon*.png
                '#^/sitemap\.xml$#i',               // sitemap.xml
                '#^/browserconfig\.xml$#i',         // browserconfig.xml (IE/Edge)
                '#^/manifest\.json$#i',             // PWA manifest
                '#^/service-worker\.js$#i',         // PWA service worker
                '#^/site\.webmanifest$#i',          // site.webmanifest
                '#^/humans\.txt$#i',                // humans.txt
                '#^/crossdomain\.xml$#i',           // legacy multimedia
                '#^/security\.txt$#i',              // security.txt
                '#^/ads\.txt$#i',                   // ads.txt
                '#^/yandex_[a-z0-9]+\.txt$#i',      // yandex site verify
                '#^/google[a-z0-9]+\.html$#i',      // google site verify
                '#^/BingSiteAuth\.xml$#i',          // bing site verify
            ];
            foreach ($ignoreUriPatterns as $pattern) {
                if (preg_match($pattern, $uri)) {
                    return;
                }
            }
        }

        $eventType = Event::ERROR;
        $detail = self::composeMessage($eventType, $e->getCode(), $e->getMessage(), $e->getFile(), $e->getLine());

        $extra = method_exists($e, 'getExtra') ? $e->getExtra() : [];
        if (!isset($extra['trace'])) {
            $extra['trace'] = $e->getTraceAsString();
        }

        EventManager::logParams(
            $eventType,
            $e->getFile(),
            $detail,
            [
                'code'  => $e->getCode(),
                'trace' => $extra['trace'],
                'extra' => $extra,
            ]
        );

        $payload = [
            'title'   => EventManager::getTitle($eventType),
            'message' => $e->getMessage(),
            'detail'  => $detail,
            'extra'   => $extra,
        ];

        self::dispatch($e->getCode(), $payload);
    }

    public static function handleShutdown(): void
    {
        $error = error_get_last();
        if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
            $eventType = Event::FATAL;
            $message = self::composeMessage(
                $eventType,
                $error['type'],
                $error['message'],
                $error['file'],
                $error['line']
            );

            EventManager::logParams($eventType, $error['file'], $message);

            self::dispatch($error['type'], [
                'title'   => EventManager::getTitle($eventType),
                'message' => $error['message'],
                'detail'  => $message
            ]);
        }
    }

    // Исправлено: eventType теперь — аргумент, а не self::$eventClass
    private static function composeMessage(int $eventType, int $errno, string $errstr, string $errfile, int $errline): string
    {
        return sprintf(
            '%s (%s), файл %s, строка %s: %s',
            EventManager::getTitle($eventType), $errno, $errfile, $errline, $errstr
        );
    }

    public static function dispatch($code, $payload): void
    {
        $context = method_exists('\GIG\Core\ContextDetector', 'detect')
            ? ContextDetector::detect()
            : (php_sapi_name() === 'cli' ? 'cli' : 'ui');

        if (!defined('APP_READY') || !APP_READY) {
            self::fallbackRender($payload);
            return;
        }

        switch ($context) {
            case ContextDetector::CONTEXT_API:
                header('Content-Type: application/json; charset=utf-8');
                http_response_code($code);

                $extra = $payload['extra'] ?? [];
                // Только для development/DEBUG — отдаём trace во фронт
                // $dev = defined('ENVIRONMENT') ? ENVIRONMENT === 'development' : true;
                if (!DEV_MODE && isset($extra['trace'])) {
                    unset($extra['trace']);
                }

                echo json_encode([
                    'status'  => 'error',
                    'code'    => $code,
                    'message' => $payload['message'] ?? 'Ошибка',
                    'extra'   => $extra
                ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
                break;

            case ContextDetector::CONTEXT_CLI:
                fwrite(STDERR, $payload['detail'] . PHP_EOL);
                break;

            case ContextDetector::CONTEXT_UI:
            default:
                if (headers_sent() || ob_get_level() > 0 && ob_get_length() > 0) {
                    break;
                }
                $controller = new MessageController();
                $controller->error($code, $payload);
                break;
        }
    }

    private static function fallbackRender($payload): void
    {
        if (function_exists('header_sent') && !headers_sent() && self::isApiRequest()) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'status'  => 'error',
                'message' => $payload['message'] ?? 'Ошибка'
            ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
            exit;
        }
        $title   = $payload['title']   ?? 'Ошибка';
        $message = $payload['message'] ?? '';
        $detail  = $payload['detail']  ?? null;

        if (function_exists('showEarlyErrorPage')) {
            showEarlyErrorPage($title, $message, $detail, 500);
        } else {
            echo '<h2>' . htmlspecialchars($title) . '</h2>';
            echo '<p>' . nl2br(htmlspecialchars($message)) . '</p>';
            if ($detail) {
                echo '<pre>' . htmlspecialchars($detail) . '</pre>';
            }
            exit;
        }
    }

    private static function isApiRequest()
    {
        $uri = $_SERVER['REQUEST_URI'] ?? '';
        $accept = $_SERVER['HTTP_ACCEPT'] ?? '';
        $xhr = $_SERVER['HTTP_X_REQUESTED_WITH'] ?? '';

        return str_starts_with($uri, '/api/')
            || str_contains($accept, 'application/json')
            || strtolower($xhr) === 'xmlhttprequest';
    }
}
