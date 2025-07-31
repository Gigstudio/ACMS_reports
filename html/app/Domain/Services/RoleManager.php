<?php
namespace GIG\Domain\Services;

defined('_RUNKEY') or die;

use GIG\Infrastructure\Persistence\Database;
use GIG\Core\Application;
use GIG\Domain\Exceptions\GeneralException;

class RoleManager
{
    protected Database $db;

    protected array $roleMap = [
        'табел'       => 'Table',    // табельщик, оператор табельного учета
        'учет'        => 'Table',
        'контрол'     => 'Control',  // контроль, служба контроля, охрана
        'безопас'     => 'Control',
        'охран'       => 'Control',
        'руководител' => 'Lead',     // начальники, заведующие
        'начальник'   => 'Lead',
        'завед'       => 'Lead',
        'директор'    => 'Lead',
        'админ'       => 'Admin',    // системные администраторы
        'систем'      => 'Admin',
        'it '         => 'Admin'
    ];

    public function __construct()
    {
        $this->db = Application::getInstance()->getMysqlClient();
    }

    public function getRoleMap()
    {
        return array_keys($this->roleMap);
    }

    public function assignRole(?int $positionId)
    {
        $name = $this->getPositionName($positionId);
        $role = $this->detectRole($name);
        return $this->getRoleIdByName($role);
    }

    protected function getPositionName(?int $positionId): string
    {
        if (!$positionId) return '';
        $row = $this->db->first('position', ['id' => $positionId]);
        return strtolower($row['name'] ?? '');
    }

    protected function detectRole(string $positionName): string
    {
        foreach ($this->roleMap as $pattern => $role) {
            if (str_contains($positionName, $pattern)) {
                return $role;
            }
        }
        return 'Common';
    }

    public function getRoleIdByName(string $name): int
    {
        $row = $this->db->first('role', ['name' => $name]);
        if (!$row) {
            throw new GeneralException("Роль '$name' не найдена в справочнике role.", 500, [
                'reason' => 'missing_role',
                'detail' => "Роль '$name' не определена. Уточните roleMap или проверьте таблицу roles."
            ]);
        }
        return (int)$row['id'];
    }

    public function getRoleName(int $id)
    {
        return $this->db->first('role', ['id' => $id]);
    }

    public function searchPositionsByKeyword(string $keyword): array
    {
        $path = PATH_STORAGE . '/temp/positions.json';
        if (!file_exists($path)) {
            throw new GeneralException("Файл с позициями не найден: $path");
        }

        $json = file_get_contents($path);
        $positions = json_decode($json, true);

        if (!is_array($positions)) {
            throw new GeneralException("Некорректный формат JSON в файле: $path");
        }

        $result = [];
        foreach ($positions as $row) {
            if (stripos($row['name'], $keyword) !== false) {
                $result[] = $row['name'];
            }
        }

        return $result;
    }
}