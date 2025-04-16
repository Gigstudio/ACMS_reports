<?php
namespace GigReportServer\System\Exceptions;

defined('_RUNKEY') or die;

class GeneralException extends \Exception
{
    protected array $extra;

    public function __construct(string $message, int $code = 400, array $extra = [], \Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
        $this->extra = $extra;
    }

    public function getExtra(): array
    {
        return $this->extra;
    }
}
