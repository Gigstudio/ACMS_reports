<?php
namespace GigReportServer\System\Services;

defined('_RUNKEY') or die;

use GigReportServer\System\Clients\LDAPClient;
use GigReportServer\System\Exceptions\GeneralException;

class LDAPService
{
    protected LDAPClient $client;

    public function __construct(array $config)
    {
        $this->client = new LDAPClient($config);
    }

    public static function safeConnect(array $config, bool $require = false): ?LDAPClient
    {
        try {
            return new LDAPClient($config);
        } catch (GeneralException $e) {
            if ($require) {
                throw $e;
            }
            trigger_error("LDAP недоступен: " . $e->getMessage(), E_USER_WARNING);
            return null;
        }
    }

    public function getConnection(){
        return $this->client->getConnection();
    }
}
