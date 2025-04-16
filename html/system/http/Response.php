<?php
namespace GigReportServer\System\Http;

defined('_RUNKEY') or die;

class Response
{
    protected int $statusCode = 200;
    protected array $headers = [];
    protected mixed $body = null;

    /**
     * Устанавливает HTTP-статус ответа.
     */
    public function setStatus(int $code): static{
        $this->statusCode = $code;
        return $this;
    }

    /**
     * Добавляет заголовок в ответ.
     */
    public function setHeader(string $name, string $value): static{
        $this->headers[$name] = $value;
        return $this;
    }

    /**
     * Устанавливает тело ответа.
     */
    public function setBody(mixed $body): static{
        $this->body = $body;
        return $this;
    }
    

    /**
     * Отправляет ответ клиенту.
     */
    public function send(): void{
        $contentType = $this->headers['Content-Type'] ?? '';

        http_response_code($this->statusCode);
        if ($contentType === 'application/json; charset=UTF-8') {
            echo json_encode($this->body ?? [], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        } elseif ($contentType === 'text/html; charset=UTF-8' && is_array($this->body)) {
            echo "<!DOCTYPE html>\n<html>\n<head>\n" . ($this->body['head'] ?? '') . "\n</head>\n<body>\n" . ($this->body['body'] ?? '') . "\n</body>\n</html>";
        } else {
            echo $this->body;
        }
        exit;
    }

    /**
     * Отправляет JSON-ответ.
     */
    public function json(array $data, int $statusCode = 200): void{
        $this->setStatus($statusCode)
            ->setHeader('Content-Type', 'application/json; charset=UTF-8')
            ->setBody($data)
            ->send();
    }

    /**
     * Отправляет текстовый ответ.
     */
    public function text(string $message, int $statusCode = 200): void{
        $this->setStatus($statusCode)
            ->setHeader('Content-Type', 'text/plain; charset=UTF-8')
            ->setBody($message)
            ->send();
    }

    /**
     * Отправляет HTML-ответ с заголовком и телом.
     */
    public function html(string $body = '', int $statusCode = 200): void{
        $this->setStatus($statusCode)
            ->setHeader('Content-Type', 'text/html; charset=UTF-8')
            ->setBody($body)
            ->send();
    }
}
