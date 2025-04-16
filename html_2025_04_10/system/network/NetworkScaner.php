<?php
namespace GigReportServer\System\Network;

defined('_RUNKEY') or die;

class NetworkScaner {
    private static array $servers = [];
    
    public static function getServers(): array{
        if (count(self::$servers) == 0){
            $file = PATH_CONFIG . 'servers.php';
            if (file_exists($file)) {
                self::$servers = include $file;
            }
        }
        return self::$servers;
    }

    public static function getServerStats(): array{
        $stats = [];
        foreach(self::getServers() as $name => $params){
            $stats[$name] = array_merge([...$params], ['state'=>self::isServerOnline($params['host'], $params['port']) ? 'online' : 'offline']);
        }
        return $stats;
    }
    
    public static function isServerOnline($host, $port = 80, $timeout = 1) {
        $connection = @fsockopen($host, $port, $errno, $errstr, $timeout);
        if ($connection) {
            fclose($connection);
            return true;
        }
        return false;
    }
}

// $percoStatus = ServerChecker::isServerOnline('10.86.68.126', 80) ? 'Online' : 'Offline';
// $oneCStatus = ServerChecker::isServerOnline('192.168.214.102', 80) ? 'Online' : 'Offline';

// echo "PERCo: $percoStatus\n";
// echo "1C: $oneCStatus\n";
