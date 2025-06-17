<?php
namespace GIG\Infrastructure\Contracts;

defined('_RUNKEY') or die;

use GIG\Domain\Entities\Entity;

interface EventLoggerInterface
{
    public function log(Entity $event): void;
}