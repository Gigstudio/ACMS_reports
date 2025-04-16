<?php
namespace GigReportServer\Pages\Models;

defined('_RUNKEY') or die;

class UserModel
{
    public string $login;
    public string $pass;
    public string $confirmPass;

    public function loadData(array $data){
        foreach ($data as $key => $value) {
            if (property_exists($this, $key)) {
                $this->$key = $value;
            }
        }
    }

    public function login(){
        // $event = new Event(self::$eventClass, basename(str_replace('\\', '/', self::class)), $message);
    }

    public function register(){
        // $event = new Event(self::$eventClass, basename(str_replace('\\', '/', self::class)), $message);
    }

    public function validate(){
        return true;
    }
}
