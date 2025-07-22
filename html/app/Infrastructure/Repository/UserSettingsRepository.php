<?php
namespace GIG\Infrastructure\Repository;

defined('_RUNKEY') or die;

use GIG\Core\Application;
use GIG\Domain\Entities\UserSettings;
use GIG\Domain\Exceptions\GeneralException;
use GIG\Infrastructure\Persistence\MySQLClient;

class UserSettingsRepository
{
    protected MySQLClient $db;
    private array $columns = [];

    public function __construct()
    {
        $this->db = Application::getInstance()->getMysqlClient();
        $this->columns = $this->getColumnNames('user_settings');
    }

    private function getColumnNames(string $table): array
    {
        $result = $this->db->describeTable($table);
        return array_column($result, 'Field');
    }

    public function findByUserId(int $userId): ?UserSettings
    {
        $row = $this->db->first('user_settings', ['user_id' => $userId]);
        return $row ? UserSettings::fromArray($row) : null;
    }

    public function create(UserSettings $settings): void
    {
        $data = $settings->toArray();
        $this->validateFields($data);
        $this->db->insert('user_settings', $data);
    }

    public function update(UserSettings $settings): void
    {
        $data = $settings->toArray();
        $this->validateFields($data);
        $this->db->update('user_settings', $data, ['user_id' => $settings->user_id]);
    }

    public function save(UserSettings $settings): void
    {
        $existing = $this->findByUserId($settings->user_id);
        if ($existing) {
            $this->update($settings);
        } else {
            $this->create($settings);
        }
    }

    private function validateFields(array $data): void
    {
        foreach ($data as $key => $value) {
            if (!in_array($key, $this->columns, true)) {
                throw new GeneralException("Недопустимое поле '$key' при сохранении настроек пользователя.", 500, [
                    'detail' => "Поле '$key' не является полем таблицы user_settings"
                ]);
            }
        }
    }
}
