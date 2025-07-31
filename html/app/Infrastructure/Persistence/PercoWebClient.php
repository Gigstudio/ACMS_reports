<?php
namespace GIG\Infrastructure\Persistence;

defined('_RUNKEY') or die;

use GIG\Core\Application;
use GIG\Domain\Exceptions\GeneralException;
use GIG\Domain\Services\EventManager;
use GIG\Domain\Entities\Event;

class PercoWebClient
{
    private string $baseUrl;
    private string $username;
    private string $password;
    private ?string $token = null;
    private bool $authorized = false;
    protected array $config;

    public function __construct(array $config = [])
    {
        $config = $config ?: $this->getDefaultConfig();

        $this->baseUrl = rtrim($config['perco_uri'], '/');
        $this->username = $config['perco_admin'];
        $this->password = $config['perco_password'];

        $this->token = $this->getFromLocalDb();
        if (!$this->token) {
            $this->connect();
        }
    }

    public function connect(): bool
    {
        if (!$this->token) {
            $this->getToken();
        }
        return $this->isConnected();
    }

    protected function getDefaultConfig(): array
    {
        return Application::getInstance()->getConfig('services.PERCo-Web') ?? [];
    }

    public function isConnected(): bool
    {
        return !empty($this->token);
    }

    public function disconnect(): void
    {
        $this->token = null;
        $this->saveToLocalDb('');
    }

    public function request(string $resource, array $params = [], array $flags = [], string $method = 'GET', ?string $body = null): mixed
    {
        $url = $this->baseUrl . '/' . ltrim($resource, '/');
        $query = '';
        $params = array_filter($params, function ($v) {
            return $v !== '' && $v !== null;
        });

        if (!empty($params)) {
            $query = '?' . http_build_query($params, '', '&', PHP_QUERY_RFC3986);
        }

        if(!empty($flags)){
            $queryParts = [];
            foreach ($flags as $flag) {
                $queryParts[] = urlencode($flag);
            }
            $query = $query . (!empty($params) ? '&' : '?') . implode('&', $queryParts);
        }

        return $this->sendRequest($method, $url . $query, $body, [], true);
    }

    private function getFromLocalDb(): ?string
    {
        return Application::getInstance()->getMysqlClient()->value(
            "SELECT value FROM app_settings WHERE param = ?", ['perco_token']
        ) ?: null;
    }

    private function saveToLocalDb(string $token): void
    {
        $db = Application::getInstance()->getMysqlClient();
        $db->updateOrInsert('app_settings', ['value' => $token], ['param' => 'perco_token']);
    }

    public function appendCat(string $cat, array $data): int|null
    {
        $db = Application::getInstance()->getMysqlClient();
        try {
            $db->updateOrInsert($cat, $data, ['name' => $data['name']]);
            $row = $db->first($cat, ['name' => $data['name']]);
            return $row['id'] ?? null;
        } catch (\Throwable $e) {
            EventManager::logParams(Event::ERROR, self::class, "appendCat: ошибка при вставке/обновлении справочника $cat");
            return null;
        }
    }

    private function getToken(): void
    {
        $url = $this->baseUrl . '/system/auth';
        $data = json_encode([
            'login' => $this->username,
            'password' => $this->password
        ]);

        $response = $this->sendRequest('POST', $url, $data, ['Content-Type: application/json'], false);
        $result = json_decode($response, true);

        if (!isset($result['token'])) {
            throw new GeneralException("Авторизация в PERCo-Web не удалась", 500, [
                'detail' => $response
            ]);
        }

        $this->token = $result['token'];
        $this->saveToLocalDb($this->token);

        EventManager::logParams(Event::INFO, self::class, 'Токен PERCo-Web успешно получен');
    }

    private function sendRequest(string $method, string $url, ?string $body = null, array $headers = [], bool $useAuth = true, bool $retry = true): string
    {
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
            EventManager::logParams(EVENT::ERROR, self::class, 'Ошибка соединения с PERCo-Web: ' . curl_error($ch));
            return json_encode([
                'status' => 'error',
                'message' => 'Ошибка соединения с сервером PERCo',
                'detail' => curl_error($ch)
            ]);
        }

        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if ($httpCode === 401 && $useAuth && $retry) {
            $this->disconnect();
            $this->connect();

            if (!$this->token) {
                EventManager::logParams(EVENT::ERROR, self::class, 'Не удалось обновить токен PERCo-Web, ответ 401');
                return json_encode([
                    'status' => 'error',
                    'message' => 'Не удалось обновить токен авторизации PERCo-Web',
                    'detail' => 'Ответ сервера: 401. Повторная авторизация не удалась.'
                ]);
            }

            return $this->sendRequest($method, $url, $body, $headers, $useAuth, false);
        }

        return $response;
    }

    public function checkStatus(): array
    {
        try {
            $this->getToken();
            if ($this->isConnected()) {
                return [
                    'status' => 'ok',
                    'message' => 'PERCo-Web API доступен'
                ];
            } else {
                return [
                    'status' => 'warn',
                    'message' => 'PERCo-Web: не удалось обновить токен авторизации'
                ];
            }
        } catch (\Throwable $e) {
            return [
                'status' => 'fail',
                'message' => 'PERCo-Web недоступен: ' . $e->getMessage(),
                'details' => [
                    'exception' => (string)$e,
                ]
            ];
        }
    }
}
