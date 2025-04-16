<?php
namespace GigReportServer\System\Engine;

defined('_RUNKEY') or die;

abstract class Model
{
    public const RULE_REQUIRED = 'required';
    public const RULE_EMAIL = 'email';
    public const RULE_MIN = 'min';
    public const RULE_MAX = 'max';
    public const RULE_MATCH = 'match';

    public function loadData($data){
        foreach($data as $key => $val){
            if(property_exists($this, $key)){
                $this->$key = $val;
            }
        }
    }

    abstract public function rules(): array; 

    public function validate(){}
}
