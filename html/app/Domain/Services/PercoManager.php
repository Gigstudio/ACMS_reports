<?php
namespace GIG\Domain\Services;

defined('_RUNKEY') or die;

use GIG\Core\Application;
use GIG\Infrastructure\Persistence\PercoWebClient;
use GIG\Domain\Entities\Event;
use GIG\Domain\Services\EventManager;

class PercoManager
{
    protected PercoWebClient $client;

    protected array $divisionsCache = [];
    protected array $positionsCache = [];

    public function __construct()
    {
        $this->client = Application::getInstance()->getPercoWebClient();
    }

    public function fetchAllDivisions(array $params = []): array
    {
        if (!empty($this->divisionsCache)) {
            return array_values($this->divisionsCache);
        }

        $response = $this->client->request('divisions/list', $params);
        $data = json_decode($response, true);

        if (!is_array($data)) {
            EventManager::logParams(Event::ERROR, self::class, 'Некорректный ответ API при запросе списка подразделений');
            return [];
        }

        foreach ($data as $division) {
            $this->divisionsCache[$division['id']] = $division;
        }

        return $data;
    }

    public function fetchAllPositions(array $params = []): array
    {
        if (!empty($this->positionsCache)) {
            return array_values($this->positionsCache);
        }

        $response = $this->client->request('positions/list', $params);
        $data = json_decode($response, true);

        if (!is_array($data)) {
            EventManager::logParams(Event::ERROR, self::class, 'Некорректный ответ API при запросе списка должностей');
            return [];
        }

        foreach ($data as $position) {
            $this->positionsCache[$position['id']] = $position;
        }

        return $data;
    }

    public function fetchUsersFromTable(string $search = '', ?string $div = null, bool $isActive = true): array
    {
        $allUsers = [];
        $page = 1;
        $totalPages = 1;
        $status = $isActive ? 'active' : 'dismissed';
        $division = $div ? $this->getDivisionByName($div) : '';

        do {
            $params = [
                'status'        => $status,
                'division'      => !empty($division) ? $division['id'] : '',//$div ?: '',
                'searchString'  => $search,
                'page'          => $page
            ];
            $response = $this->client->request('users/staff/table', $params);
            $data = json_decode($response, true);

            if(!is_array($data) || !isset($data['rows'])){
                EventManager::logParams(Event::WARNING, self::class, "Некорректный ответ при получении пользователей PERCo: $response");
                return [];
            }

            $allUsers = array_merge($allUsers, $data['rows']);
            $totalPages = (int)($data['total'] ?? 1);
            $page++;
        } while ($page <= $totalPages);
        return $allUsers;
    }

    public function fetchUsersFromList(string $search = '', string $div = '', bool $withEmail = null, bool $withCards = null, bool $isActive = true): array
    {
        $allUsers = [];
        $page = 1;
        $totalPages = 1;
        $status = $isActive ? 'active' : 'dismissed';
        $division = $div ? $this->getDivisionByName($div) : '';

        do {
            $params = [
                'status'        => $status,
                'division'      => !empty($division) ? $division['id'] : '',
                'searchString'  => $search,
                'withEmail'     => $withEmail,
                'withCards'     => $withCards,
                'page'          => $page
            ];
            $response = $this->client->request('users/staff/list', $params);
            $data = json_decode($response, true);

            if(!is_array($data) || !isset($data['rows'])){
                EventManager::logParams(Event::WARNING, self::class, "Некорректный ответ при получении пользователей PERCo: $response");
                return [];
            }

            $allUsers = array_merge($allUsers, $data['rows']);
            $totalPages = (int)($data['total'] ?? 1);
            $page++;
        } while ($page <= $totalPages);
        return $allUsers;
    }

    public function getDivision(int $id): array
    {
        if (isset($this->divisionsCache[$id])) {
            return $this->divisionsCache[$id];
        }

        $response = $this->client->request("divisions/" . $id);
        $data = json_decode($response, true);
        if (is_array($data)) {
            $this->divisionsCache[$id] = $data;
        }
        return $data ?? [];
    }

    public function getPosition(int $id): array
    {
        if (isset($this->positionsCache[$id])) {
            return $this->positionsCache[$id];
        }

        $response = $this->client->request("positions/" . $id);
        $data = json_decode($response, true);
        if (is_array($data)) {
            $this->positionsCache[$id] = $data;
        }
        return $data ?? [];
    }

    public function getDivisionByName(string $name): array
    {
        foreach ($this->divisionsCache as $division) {
            if ($division['name'] === $name) {
                return $division;
            }
        }

        $params = ['searchString' => $name];
        $response = $this->client->request('divisions/list', $params);
        $data = json_decode($response, true);
        if(!is_array($data)){
            EventManager::logParams(Event::WARNING, self::class, "Некорректный ответ при получении подразделений PERCo");
            return [];
        }

        $division = $data[0] ?? [];
        if (!empty($division['id'])) {
            $this->divisionsCache[$division['id']] = $division;
        }
        return $division;
    }

    public function getPositionByName(string $name): array
    {
        $trimmedName = trim($name);
        $upperName = mb_strtoupper($trimmedName);

        foreach ($this->positionsCache as $position) {
            if (mb_strtoupper(trim($position['name'])) === $upperName) {
                return $position;
            }
        }

        $params = ['searchString' => $trimmedName];
        $response = $this->client->request('positions/table', $params);
        $data = json_decode($response, true);
        if(!is_array($data) || !isset($data['rows']) || !is_array($data['rows'])){
            EventManager::logParams(Event::WARNING, self::class, "Некорректный ответ при получении должностей PERCo");
            return [];
        }
        
        foreach ($data['rows'] as $pos) {
            if (mb_strtoupper(trim($pos['name'])) === $upperName) {
                $this->positionsCache[$pos['id']] = $pos;
                return $pos;
            }
        }

        $id = $this->client->appendCat('position', ['name' => $trimmedName]);
        return ['id' => $id, 'name' => $trimmedName];
    }

    public function getRootDivision(int $id): array
    {
        $current = $this->getDivision($id);

        while ($current && $current['parent_id']) {
            $next = $this->getDivision($current['parent_id']);
            if ($next['name'] === 'Сторонние организации') {
                return $current;
            }
            $current = $next;
        }
        return $current;
    }

    public function findUserByIdentifier(string $badgeNumber): ?int
    {
        $query = [
            'card' => $badgeNumber,
            'target' => 'staff'
        ];

        $response = $this->client->request('users/searchCard', $query);
        $data = json_decode($response, true);

        if (!empty($data['id'])) {
            return $data['id'];
        }

        return null;
    }

    public function getUserInfoById(int $userId): array
    {
        $response = $this->client->request('users/staff/' . $userId);
        $data = json_decode($response, true);

        return $data ?? [];
    }

    public function getUserByBadge(string $badge): ?array
    {
        $userId = $this->findUserByIdentifier($badge);
        if (!$userId) {
            return null;
        }
        $percoData = $this->getUserInfoById($userId);
        if (empty($percoData) || empty($percoData['id'])) {
            return null;
        }
        return $this->normalizeUserData($percoData);
    }

    public function findUserByName(string $name, ?string $divisionName = null): array
    {
        $candidates = $this->fetchUsersFromTable($name, $divisionName);
        if (empty($candidates)) {
            EventManager::logParams(Event::INFO, self::class, "Пользователь с ФИО '$name' не найден в PERCo.");
            return [];
        }
        if (count($candidates) > 1) {
            EventManager::logParams(Event::INFO, self::class, "Найдено несколько совпадений по ФИО '$name' в PERCo (" . count($candidates) . ").");
        }
        return $this->getActualUser($candidates);
    }

    private function getActualUser(array $candidates): array
    {
        if (count($candidates) === 1) {
            return $this->normalizeUserData($candidates[0]);
        }
        $withDates = array_filter($candidates, function ($c) {
            return !empty($c['hiring_date']);
        });
        usort($withDates, function ($a, $b) {
            return strtotime($b['hiring_date']) <=> strtotime($a['hiring_date']);
        });
        return $this->normalizeUserData($withDates[0]);
    }

    public function normalizeUserData(array $percoData): array
    {
        $details = $this->getUserInfoById($percoData['id']);
        $positionName = $percoData['position_name'] ?? (is_array($percoData['position']) ? reset($percoData['position']) : null);
        $position = $positionName ? $this->getPositionByName($positionName) : null;

        $normalized = [
            'name' => $percoData['fio'] ?? implode(' ', [mb_strtoupper($percoData['last_name']), mb_strtoupper($percoData['first_name']), mb_strtoupper($percoData['middle_name'])]),
            'email' =>$percoData['email_for_send'] ?? null,
            'company_id' => null,
            'division_id' => $percoData['division_id'] ?? (is_array($percoData['division']) ? (int)array_key_first($percoData['division']) : null),
            'position_id' => $position['id'],
            'perco_id' => $percoData['id'],
            'card_number' => (!empty($details['identifier']) && is_array($details['identifier'])) ? $details['identifier'][0]['identifier'] : null,
            'tabel_number' => $percoData['tabel_number'] ?: null,
            'birth_date' => $percoData['birth_date'] ?: null,
            'mobyle' => null,
            'source' => 'perco',
        ];

        $extra = $details['additional_fields']['text'] ?? [];
        $mapped = array_filter($extra, function ($v) {
            return $v['text'] !== '' && $v['text'] !== null;
        });
        foreach ($mapped as $field) {
            $name = mb_strtolower($field['name'] ?? '');
            $text = trim($field['text'] ?? '');
            switch ($name) {
                case 'email': 
                    $normalized['email'] = $text;
                    break;
                case 'должность (сторонняя организация)':
                    $position = $this->getPositionByName($text);
                    break;
                case 'номер телефона':
                    $normalized['mobyle'] = $text;
                    break;
            }
        }
        if (is_array($position)) {
            $normalized['position_id'] = $position['id'] ?? null;
        }
        $normalized['company_id'] = $normalized['division_id'] ? $this->getRootDivision((int)$normalized['division_id'])['id'] : null;
        
        return $normalized;
    }
}