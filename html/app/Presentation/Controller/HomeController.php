<?php
namespace GIG\Presentation\Controller;

use GIG\Domain\Exceptions\GeneralException;
use GIG\Infrastructure\Repository\LocalUserRepository;

defined('_RUNKEY') or die;

use GIG\Core\Controller;
use GIG\Core\Request;
use GIG\Core\Block;
use GIG\Domain\Services\PercoManager;
use GIG\Domain\Services\RoleManager;

class HomeController extends Controller
{
    public function index(): void
    {
        $modals = [
            'login' => Block::make('partials/modal_login', ['show' => false])
        ];

        $data['title'] = 'Добро пожаловать!';

        /*
        Данные для тестирования
        FireBird:
        */
        // $firebird = Application::getInstance()->getFirebirdClient();
        
        // $tables = $firebird->exec("SELECT TRIM(RDB\$RELATION_NAME) AS NAME FROM RDB\$RELATIONS WHERE RDB\$SYSTEM_FLAG = 0");
        // $tableNames = [];
        // foreach ($tables as $tbl) {
        //     $tableNames[] = $tbl['NAME'];
        // }
        // $data['firebird_tables'] = $tableNames;

        // $staff = $firebird->get('STAFF', [], ['*'], 200);
        // $data['staff'] = $staff;

        /*
        Тестирование
        PERCo-Web:
        */
        $percoUserManager = new PercoManager();
        // $percoWeb = $this->app->getPercoWebClient();

        $bageNumber = '2820176';
        $percoUserId = $percoUserManager->findUserByIdentifier($bageNumber);
        $data['percoUser'] = $percoUserId;

        // $percoUserInfo = $percoUserManager->getUserInfoById($percoUserId['user_id']);
        // $data['percoUserInfo'] = $percoUserInfo;

        // $divisions = $percoWeb->fetchAllDivisions();
        // $data['percoDivisions'] = $divisions;

        /*
        Тестирование
        LDAP:
        */
        $ldap = $this->app->getLdapClient();
        $ldapUserInfo1 = $ldap->getUserData('a.abdilmanov');
        $lc = $ldap->search('sn=Тулисов');
        $ldapUserInfo2 = $ldap->getUserData('v.tulisov');
        $data['ldapUserInfo1'] = $ldapUserInfo1;
        $data['ldapUserInfo2'] = $lc;

        /*
        Тестирование
        TaskManager:
        */
        $tm = new \GIG\Domain\Services\TaskManager();
        $tasks = $tm->getRunnableTasks();
        $data['bgtasks'] = $tasks;

        /*
        Тестирование
        RoleManager:
        */
        $roleManager = new RoleManager();
        $map = $roleManager->getRoleMap();
        $data['roles'] = [];
        foreach ($map as $key) {
            $matches = $roleManager->searchPositionsByKeyword($key);
            $data['roles'][] = $key;
            // print($key.'\n');
            $data['roles'][] = $matches;
            // print_r($matches);
            $data['roles'][] = ' ';
            // print('\n');
        }

        // НАЧАЛО РЕНДЕРА. Все переменные должны быть определены ДО этого блока.

        $head = Block::make('partials/head');
        $mainmenu = Block::make('partials/mainmenu', ['user' => 'Admin']);
        $content = Block::make('blocks/home', $data);
        $bottommenu = Block::make('partials/bottommenu', ['user' => 'Admin']);
        $statusbar = Block::make('partials/statusbar', ['user' => 'Admin']);
        $modals = Block::make('partials/modals', ['modals' => $modals]);

        $page = Block::make('layouts/default', $data)
            ->with([
                'head' => $head,
                'mainmenu' => $mainmenu,
                'content' => $content,
                'bottommenu' => $bottommenu,
                'statusbar' => $statusbar,
                'modals' => $modals,
            ]);

        $this->render($page);
    }

    public function reports()
    {
        $modals = [
            'login' => Block::make('partials/modal_login', ['show' => false])
        ];

        $data['title'] = 'Добро пожаловать!';

        $path = PATH_STORAGE . '/temp/result_s20.json';
        if (!file_exists($path)) {
            throw new GeneralException('Файл не найден', 404, [
                'detail' => 'Не найден файл с результатами выборки из PERCo-S20'
            ]);
        }

        $raw = file_get_contents($path);
        $entries = json_decode($raw, true);
        if (!is_array($entries)) {
            throw new GeneralException('Неверный формат JSON', 400, [
                'detail' => 'Файл с результатами выборки не содержит корректный JSON'
            ]);
        }

        $ldap = $this->app->getLdapClient();
        $perco = new PercoManager(); 
        $localUserRepo = new LocalUserRepository();
        $roleManager = new RoleManager();

        $notFound = [];

        foreach ($entries as $entry) {
            $fio = $entry['display name'] ?? '';
            $expectedRole = $entry['role'] ?? '';
            $login = $entry['Primary Windows NT Account'];
            $source = '';
            $position = null;
            $resolvedRole = null;

            $found = $ldap->findUser($login);
            if ($found && isset($found['title'])) {
                $position = $found['title'];
                $positionId = $localUserRepo->resolveDictionaryEntry('position', $position);
                $source = 'ldap';
            }

            if (!$position) {
                $percoUser = $perco->findUserByName($fio);
                if (is_array($percoUser)) {
                    $positionId = $percoUser['position_id'];
                    $position = $perco->getPosition($positionId)['name'] ?? null;
                    $source = 'perco';
                }
            }

            if ($position) {
                // $positionId = $localUserRepo->resolveDictionaryEntry('position', $position);
                $roleId = $roleManager->assignRole($positionId);
                $resolvedRole = $roleManager->getRoleName($roleId);
            } else {
                $notFound[] = ['fio' => $fio, 'reason' => "Не найдена должность '$positionId' в $source"];
                $notFound[] = $percoUser;
                continue;
            }

            $data['analyse'][] = [
                'Fio'           => $fio,
                'login'         => $login,
                'position'      => $position,
                'source'        => $source,
                'expectedRole'  => $expectedRole,
                'resolved_role' => $resolvedRole['description'],
                'match'         => str_contains($resolvedRole['description'],$expectedRole) ? '✔' : '✘'
            ];
            // break;
        }
        $data['not_found'] = $notFound;

        // НАЧАЛО РЕНДЕРА. Все переменные должны быть определены ДО этого блока.

        $head = Block::make('partials/head');
        $mainmenu = Block::make('partials/mainmenu', ['user' => 'Admin']);
        $content = Block::make('blocks/analyse', $data);
        $bottommenu = Block::make('partials/bottommenu', ['user' => 'Admin']);
        $statusbar = Block::make('partials/statusbar', ['user' => 'Admin']);
        $modals = Block::make('partials/modals', ['modals' => $modals]);

        $page = Block::make('layouts/default', $data)
            ->with([
                'head' => $head,
                'mainmenu' => $mainmenu,
                'content' => $content,
                'bottommenu' => $bottommenu,
                'statusbar' => $statusbar,
                'modals' => $modals,
            ]);

        $this->render($page);
    }
}
