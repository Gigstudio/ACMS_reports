<?php
namespace GigReportServer\System\Engine;

defined('_RUNKEY') or die;

use GigReportServer\System\Exceptions\GeneralException;

class AuthService
{
    public function authenticate(string $login, string $pass){
        new Event(Event::EVENT_WARNING, self::class, "Пользователь не найден: $login. Продолжаем в ограниченном режиме.");
        return null;
    }
}