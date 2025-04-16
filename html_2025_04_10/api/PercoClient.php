<?php
namespace GigReportServer\API;

use GigReportServer\System\Engine\Application;

defined('_RUNKEY') or die;

class PercoClient 
{
    protected array $cred;
    private string $apiUrl;
    protected string $token;

    public function __construct() {
        $this->cred = Application::getInstance()->getConfig()->get('perco');
        $this->apiUrl = rtrim($this->cred['perco_uri'], '/');
        $this->token = $this->getToken();
    }

    private function request(string $endpoint, array $data = [], string $method = 'GET', $full = true): mixed {
        $url = $this->apiUrl . $endpoint;
        $headers = $full ? [
            "Authorization: Bearer {$this->token}",
            "Content-Type: application/json; charset=UTF-8"
        ] : [
            "Content-Type: application/json; charset=UTF-8"
        ];

        $options = [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => $headers
        ];

        if ($method === 'POST') {
            $options[CURLOPT_POST] = true;
            $options[CURLOPT_POSTFIELDS] = json_encode($data);
        } elseif ($method === 'DELETE') {
            $options[CURLOPT_CUSTOMREQUEST] = 'DELETE';
        }

        $ch = curl_init();
        curl_setopt_array($ch, $options);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode >= 400) {
            throw new \Exception("PERCo API error: $response", $httpCode);
        }

        return json_decode($response, true);
    }

    public function getToken(){
        $data = ['login' => $this->cred['perco_admin'], 'password' => $this->cred['perco_password']];
        $response = $this->request("/system/auth", $data, 'POST', false);
        return $response['token'] ?? '';
    }

    public function getStaffList() {
        return $this->request("/users/staff/list");
    }

    public function getStaffTable() {
        return $this->request("/users/staff/table");
    }

    public function getUserInfo($userId) {
        return $this->request("/users/$userId");
    }

    public function grantAccess($userId, $accessLevel) {
        return $this->request("/users/$userId/access", ['level' => $accessLevel], 'POST');
    }

    public function revokeAccess($userId) {
        return $this->request("/users/$userId/access", [], 'DELETE');
    }

    public function getLogs() {
        return $this->request("/logs");
    }
}
