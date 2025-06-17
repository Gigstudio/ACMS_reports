<?php
namespace GIG\API;

defined('_RUNKEY') or die;

class ApiAnswer
{
    public const STATUS_SUCCESS = 'success';
    public const STATUS_ERROR = 'error';
    public const STATUS_FAIL = 'FAIL';

    public string $status = self::STATUS_SUCCESS; // success|fail|error
    public int $code = 200; // HTTP-статус (дублирует, но всегда в теле)
    public string $message = ''; // Человеко-ориентированное сообщение
    public array $data = []; // Payload
    public array $extra = []; // Необязательные поля (trace, debug, details...)

    public function __construct(
        $status = self::STATUS_SUCCESS,
        $code = 200,
        $message = '',
        $data = [],
        $extra = []
    ) {
        $this->status = $status;
        $this->code = $code;
        $this->message = $message;
        $this->data = $data;
        $this->extra = $extra;
    }

    /**
     * Возвращает ответ в виде массива (для json_encode)
     */
    public function toArray(): array
    {
        $result = [
            'status'  => $this->status,
            'code'    => $this->code,
            'message' => $this->message,
            'data'    => $this->data,
        ];
        if (!empty($this->extra)) {
            $result['extra'] = $this->extra;
        }
        return $result;
    }

    /**
     * Быстро создать экземпляр-ответ
     */
    public static function build(
        $data = [],
        $status = self::STATUS_SUCCESS,
        $code = 200,
        $message = '',
        $extra = []
    ): self {
        return new self($status, $code, $message, $data, $extra);
    }

    /**
     * Преобразует тело запроса (json) в массив.
     */
    public static function parseRequest(): array
    {
        $raw = file_get_contents('php://input');
        $json = json_decode($raw, true);
        return is_array($json) ? $json : [];
    }
}
