<?php
namespace GIG\API\Controller;

defined('_RUNKEY') or die;

use GIG\Core\Application;
use GIG\Core\Request;
use GIG\Core\Response;
use GIG\API\ApiAnswer;

/**
 * Базовый API-контроллер.
 * Наследники: GIG\Api\Controller\*
 */
abstract class ApiController
{
    protected Application $app;
    protected Request $request;
    protected Response $response;
    /** @var int HTTP статус ответа */
    // protected ?User $user;

    public function __construct()
    {
        $this->app = Application::getInstance();
        $this->request = $this->app->request;
        $this->response = $this->app->response;
        // $this->user = $this->app->getCurrentUser();
    }

    /**
     * Бросает ошибку, если пользователь не авторизован.
     */
    // protected function requireAuth(): void
    // {
    //     $user = $this->app->getCurrentUser();
    //     if (!$user) {
    //         $this->abort('Требуется авторизация', 401);
    //     }
    // }

    /**
     * Проверка роли пользователя (строка или массив ролей).
     */
    // protected function requireRole(string|array $roles): void
    // {
    //     $user = $this->app->getCurrentUser();
    //     $have = $user && method_exists($user, 'hasRole')
    //         ? $user->hasRole($roles)
    //         : (in_array($user->role ?? null, (array)$roles, true));
    //     if (!$have) {
    //         $this->abort('Недостаточно прав', 403);
    //     }
    // }

    /**
     * Получить параметр из любого источника (GET, POST, JSON, route).
     */
    protected function param(string $key, $default = null)
    {
        return
            $this->request->getRouteParams()[$key] ??
            $this->request->getBody()[$key] ??
            $this->request->getQueryParam($key, $default);
    }

    /**
     * Унифицированная отправка успешного ответа.
     */
    protected function success(
        array $data = [],
        string $message = 'OK',
        int $code = 200,
        array $extra = []
    ): void {
        $answer = new ApiAnswer(
            status: ApiAnswer::STATUS_SUCCESS,
            code: $code,
            message: $message,
            data: $data,
            extra: $extra
        );
        $this->response->json($answer);
    }

    /**
     * Унифицированная отправка ошибки.
     */
    protected function error(
        string $message = 'Ошибка',
        int $code = 400,
        array $data = [],
        array $extra = []
    ): void {
        $answer = new ApiAnswer(
            status: ApiAnswer::STATUS_ERROR,
            code: $code,
            message: $message,
            data: $data,
            extra: $extra
        );
        $this->response->json($answer);
    }

    /**
     * Немедленно прерывает выполнение и отдаёт ошибку (аборт).
     */
    protected function abort(
        string $message = 'Доступ запрещён',
        int $code = 403,
        array $data = [],
        array $extra = []
    ): void {
        $this->error($message, $code, $data, $extra);
        exit;
    }

    /**
     * Генерация произвольного ответа (например, кастомные статусы).
     */
    protected function answer(ApiAnswer $answer): void
    {
        $this->response->json($answer);
    }

    /**
     * Получить загруженный файл по ключу.
     */
    protected function file(string $key)
    {
        return $this->request->getFile($key);
    }

    /**
     * Быстрая отправка текстового/plain ответа (например, для healthcheck).
     */
    protected function text(string $text, int $status = 200): void
    {
        $this->response->setStatus($status)
            ->setHeader('Content-Type', 'text/plain; charset=UTF-8')
            ->setBody($text)
            ->send();
    }
}
