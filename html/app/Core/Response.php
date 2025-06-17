<?php
namespace GIG\Core;

defined('_RUNKEY') or die;

use GIG\API\ApiAnswer;

class Response
{
    protected int $statusCode = 200;
    /**
     * @var array<string, string[]>
     */
    protected array $headers = [];
    protected mixed $body = null;
    protected bool $sent = false;

    /**
     * Устанавливает HTTP-статус.
     */
    public function setStatus(int $code): static {
        $this->statusCode = $code;
        return $this;
    }

    /**
     * Добавляет заголовок (поддерживает множественные).
     */
    public function setHeader(string $name, string $value, bool $append = false): static {
        $normalized = $this->normalizeHeader($name);
        if ($append) {
            $this->headers[$normalized][] = $value;
        } else {
            $this->headers[$normalized] = [$value];
        }
        return $this;
    }

    /**
     * Проверяет, был ли уже отправлен ответ.
     */
    public function isSent(): bool {
        return $this->sent;
    }

    /**
     * Устанавливает тело ответа.
     */
    public function setBody(mixed $body): static {
        $this->body = $body;
        return $this;
    }

    /**
     * Отправляет ответ.
     */
    public function send(): void {
        if ($this->sent) return;
        $this->sent = true;
        http_response_code($this->statusCode);

        foreach ($this->headers as $name => $values) {
            foreach ((array)$values as $v) {
                header("$name: $v", false);
            }
        }

        $contentType = $this->headers['Content-Type'][0] ?? '';

        if (str_starts_with($contentType, 'application/json')) {
            echo json_encode($this->body ?? [], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        } else {
            echo $this->body;
        }
        exit;
    }

    public function json(ApiAnswer|array $data, int $statusCode = 200): void {
        if ($data instanceof ApiAnswer) {
            $payload = $data->toArray();
            $statusCode = $data->code;
        } else {
            $payload = $data;
        }
        $this->setStatus($statusCode)
            ->setHeader('Content-Type', 'application/json; charset=UTF-8')
            ->setBody($payload)
            ->send();
    }

    public function text(string $message, int $statusCode = 200): void {
        $this->setStatus($statusCode)
            ->setHeader('Content-Type', 'text/plain; charset=UTF-8')
            ->setBody($message)
            ->send();
    }

    public function html(string $body = '', int $statusCode = 200): void {
        $this->setStatus($statusCode)
            ->setHeader('Content-Type', 'text/html; charset=UTF-8')
            ->setBody($body)
            ->send();
    }

    /**
     * Отправляет файл для скачивания.
     */
    public function download(string $filepath, ?string $filename = null): void {
        if (!file_exists($filepath)) {
            $this->setStatus(404)
                ->text("File not found");
            return;
        }
        $filename = $filename ?? basename($filepath);
        $this->setHeader('Content-Description', 'File Transfer')
            ->setHeader('Content-Type', 'application/octet-stream')
            ->setHeader('Content-Disposition', 'attachment; filename="' . $filename . '"')
            ->setHeader('Expires', '0')
            ->setHeader('Cache-Control', 'must-revalidate')
            ->setHeader('Pragma', 'public')
            ->setHeader('Content-Length', (string)filesize($filepath));
        $this->sent = true;
        http_response_code($this->statusCode);
        foreach ($this->headers as $name => $values) {
            foreach ((array)$values as $v) {
                header("$name: $v", false);
            }
        }
        readfile($filepath);
        exit;
    }

    protected function normalizeHeader(string $name): string {
        $name = strtolower($name);
        $parts = explode('-', $name);
        $parts = array_map('ucfirst', $parts);
        return implode('-', $parts);
    }
}
