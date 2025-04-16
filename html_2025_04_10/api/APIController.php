<?php
namespace GigReportServer\API;

defined('_RUNKEY') or die;

class APIController
{
    private $ldap;
    private $perco;

    public function __construct(LDAPClient $ldap, PercoClient $perco) {
        $this->ldap = $ldap;
        $this->perco = $perco;
    }

    public function authenticate($username, $password): bool {
        return $this->ldap->authenticate($username, $password);
    }

    public function getUserInfo($userId) {
        return $this->perco->getUserInfo($userId);
    }

    public function grantAccess($userId, $accessLevel) {
        return $this->perco->grantAccess($userId, $accessLevel);
    }

    public function revokeAccess($userId) {
        return $this->perco->revokeAccess($userId);
    }

    public function getLogs() {
        return $this->perco->getLogs();
    }
}