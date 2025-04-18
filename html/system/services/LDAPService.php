<?php
namespace GigReportServer\System\Services;

defined('_RUNKEY') or die;

use GigReportServer\System\Clients\LDAPClient;
use GigReportServer\System\Exceptions\GeneralException;

class LDAPService
{
    public static function safeConnect(array $config, bool $require = false): ?LDAPClient
    {
        try {
            return new LDAPClient($config);
        } catch (GeneralException $e) {
            if ($require) {
                throw $e;
            }

            system_warn("LDAP недоступен: " . $e->getMessage());
            return null;
        }
    }
}
