<?php
namespace GigReportServer\System\Clients;

defined('_RUNKEY') or die;

use GigReportServer\System\Exceptions\GeneralException;
use GigReportServer\System\Engine\Event;
use GigReportServer\System\Engine\Application;

class PercoWebClient
{
    private string $baseUrl;
    private string $username;
    private string $password;
    private ?string $token = null;
    private bool $authorized = false;

    public function __construct(array $config){
        $this->baseUrl = rtrim($config['perco_uri'], '/');
        $this->username = $config['perco_admin'];
        $this->password = $config['perco_password'];

        $this->token = $_SESSION['perco_token'] ?? null;
        if(!$this->token){
            $this->token = $this->getFromDb();
            if ($this->token) {
                $_SESSION['perco_token'] = $this->token;
            } else {
                $this->getToken();
            }
        }
    }

    private function getFromDb(): ?string {
        return Application::getInstance()->getDatabase()->value(
            "SELECT value FROM db_settings WHERE param = ?",
            ['perco_token']
        ) ?: null;
    }

    private function saveToDb(string $token): void {
        $db = Application::getInstance()->getDatabase();
        $affected = $db->update('db_settings', ['value' => $token], ['param' => 'perco_token']);
        if ($affected === 0) {
            $db->insert('db_settings', [
                'param' => 'perco_token',
                'value' => $token
            ]);
        }
    }

    private function getToken(): void {
        $url = $this->baseUrl . '/system/auth';
        $data = json_encode([
            'login' => $this->username,
            'password' => $this->password
        ]);

        $response = $this->sendRequest('POST', $url, $data, ['Content-Type: application/json'], false);
        $result = json_decode($response, true);

        if (!isset($result['token'])) {
            new Event(Event::EVENT_WARNING, self::class, "Авторизация в PERCo-Web не удалась: $response");
            // throw new GeneralException("Ошибка авторизации в PERCo-Web", 401, ['response' => $response]);
            return;
        }

        $this->token = $result['token'];
        $_SESSION['perco_token'] = $this->token;
        $this->saveToDb($this->token);
        new Event(Event::EVENT_INFO, self::class, 'Токен PERCo-Web успешно получен');
    }

    public function getBaseUrl(){
        return $this->baseUrl;
    }

    public function sendRequest(string $method, string $url, ?string $body = null, array $headers = [], bool $useAuth = true): string {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);

        if ($body !== null) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
        }

        if ($useAuth && $this->token) {
            $headers[] = 'Authorization: Bearer ' . $this->token;
        }

        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        $response = curl_exec($ch);
        if (curl_errno($ch)) {
            return json_encode([
                'status' => 'error',
                'message' => 'Ошибка соединения с сервером PERCo',
                'detail' => curl_error($ch)
            ]);
        }

        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode === 401 && $useAuth) {
            $this->token = null;
            unset($_SESSION['perco_token']);
            $this->getToken();
        
            // если токен не появился — значит перезапрашивать нет смысла
            if (!$this->token) {
                return json_encode([
                    'status' => 'error',
                    'message' => 'Не удалось обновить токен авторизации PERCo-Web',
                    'detail' => 'Ответ сервера: 401. Повторная авторизация не удалась.'
                ]);
            }
        
            return $this->sendRequest($method, $url, $body, $headers, $useAuth);
        }

        return $response;
    }

    public function getUserByIdentifier(string $identifier): array {
        $query = http_build_query([
            'card' => $identifier,
            'target' => 'staff'
        ]);

        $url = $this->baseUrl . '/users/searchCard?' . $query;

        $response = $this->sendRequest('GET', $url);
        $data = json_decode($response, true);

        if (!empty($data['id'])) {
            return ['user_id' => $data['id']];
        }

        return [];
    }

    public function getUserInfoById(int $userId): array {
        $url = $this->baseUrl . '/users/staff/' . $userId;
    
        $response = $this->sendRequest('GET', $url);
        $data = json_decode($response, true);
    
        return $data ?? [];
    }
}