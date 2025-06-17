<?php
namespace GIG\Domain\Exceptions;

defined('_RUNKEY') or die;

class GeneralException extends \Exception
{
    protected array $extra;

    public function __construct(string $message, int $code = 400, array $extra = [], \Throwable $previous = null)
    {
        $prepared = [
            'short' => is_array($message) ? ($message['short'] ?? 'Произошла ошибка') : $message,
            'full'  => is_array($message) ? ($message['full'] ?? $message['short'] ?? 'Произошла ошибка') : $message
        ];

        $extra['message'] = $prepared;

        parent::__construct($prepared['short'], $code, $previous);
        $this->extra = $extra;
    }

    public function getExtra(): array
    {
        return $this->extra;
    }

    public function getShortMessage(): string
    {
        return $this->extra['message']['short'] ?? $this->getMessage();
    }

    public function getFullMessage(): string
    {
        return $this->extra['message']['full'] ?? $this->getMessage();
    }
}
