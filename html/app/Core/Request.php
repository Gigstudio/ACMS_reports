<?php
namespace GIG\Core;

defined('_RUNKEY') or die;

use GIG\Core\ContextDetector;

class Request
{
    protected string $method;
    protected string $path;
    protected array $routeParams = [];
    protected array $queryParams;
    protected array $postParams;
    protected array $bodyParams = [];
    protected array $headers;
    protected array $files;

    public function __construct()
    {
        $this->method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
        $this->path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?? '/';
        $this->queryParams = $_GET;
        $this->postParams = $_POST;
        $this->headers = function_exists('getallheaders') ? getallheaders() : [];
        $this->files = $_FILES ?? [];

        // Обрабатываем тело запроса (для PUT, PATCH, DELETE и JSON POST)
        if (!empty($_SERVER['CONTENT_TYPE']) && strpos($_SERVER['CONTENT_TYPE'], 'application/json') !== false) {
            $this->bodyParams = json_decode(file_get_contents('php://input'), true) ?? [];
        } elseif (in_array($this->method, ['PUT', 'PATCH', 'DELETE'])) {
            parse_str(file_get_contents('php://input'), $this->bodyParams);
        } else {
            $this->bodyParams = [];
        }
    }

    public function setRouteParams(array $params): void
    {
        $this->routeParams = array_filter(
            $params, fn($key) => !is_numeric($key), ARRAY_FILTER_USE_KEY
        );
    }

    public function getRouteParams(): array
    {
        return $this->routeParams;
    }

    public function getMethod(): string
    {
        return strtoupper($this->method);
    }

    public function isGet(): bool{
        return $this->getMethod() === 'GET';
    }

    public function isPost(): bool{
        return $this->getMethod() === 'POST';
    }

    public function isAjax(): bool
    {
        return !empty($this->getHeader('X-Requested-With')) && strtolower($this->getHeader('X-Requested-With')) === 'xmlhttprequest';
    }

    public function isApi(): bool
    {
        return class_exists('\GIG\Core\ContextDetector')
            ? ContextDetector::detect() === ContextDetector::CONTEXT_API
            : self::isApiStatic(); // fallback, если ContextDetector не загружен
    }

    public static function isApiStatic(){
        $uri = $_SERVER['REQUEST_URI'] ?? '';
        $accept = $_SERVER['HTTP_ACCEPT'] ?? '';
        $xhr = $_SERVER['HTTP_X_REQUESTED_WITH'] ?? '';
    
        return str_starts_with($uri, '/api/')
            || str_contains($accept, 'application/json')
            || strtolower($xhr) === 'xmlhttprequest';
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function getQueryParam(string $key, $default = null): mixed
    {
        return $this->queryParams[$key] ?? $default;
    }

    public function getPostParam(string $key, $default = null): mixed
    {
        return $this->postParams[$key] ?? $default;
    }

    public function getBody(){
        $body = in_array($this->method, ['POST', 'PUT', 'PATCH', 'DELETE'])
            ? ($this->bodyParams ?: $this->postParams) // JSON или обычный POST
            : $this->queryParams;

        return filter_var_array($body, FILTER_SANITIZE_SPECIAL_CHARS) ?? [];
    }

    public function getBodyParams(){
        return $this->bodyParams;
    }

    public function getBodyParam(string $key, $default = null)
    {
        return $this->bodyParams[$key] ?? $default;
    }

    public function getHeader(string $key, $default = null)
    {
        $keyFormatted = str_replace(' ', '-', ucwords(strtolower(str_replace('-', ' ', $key))));
        return $this->headers[$keyFormatted]
            ?? $_SERVER['HTTP_' . strtoupper(str_replace('-', '_', $key))] 
            ?? $default;
    }

    public function getHeaders(): array
    {
        return $this->headers;
    }

    public function getFile(string $key)
    {
        return $this->files[$key] ?? null;
    }

    public function getIp(): string
    {
        if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            return trim(explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0]);
        }

        return $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    }
}
