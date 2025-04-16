<?php
namespace GigReportServer\System\Engine\Exceptions;

defined('_RUNKEY') or die;

class GeneralException extends \Exception
{
    protected string $module;
    protected array $extra;

    public function __construct(string $message, int $code = 400, array $extra = [], \Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
        $this->module = $this->getFile();
        // $this->line = $this->getLine();
        $this->extra = $extra;
    }

    public function getModule(): string
    {
        return $this->module;
    }

    public function getExtra(): array
    {
        return $this->extra;
    }
}
