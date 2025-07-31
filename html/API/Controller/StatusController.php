<?php
namespace GIG\Api\Controller;

defined('_RUNKEY') or die;

use GIG\Domain\Services\ServiceStatusManager;
use GIG\Infrastructure\Repository\UserTokenRepository;

class StatusController extends ApiController
{
    protected ServiceStatusManager $manager;

    public function __construct()
    {
        parent::__construct();
        $this->manager = new ServiceStatusManager($this->app->getMysqlClient());
    }

    public function getAll(): void
    {
        $statuses = $this->manager->getAll();
        $this->success(['services' => $statuses]);
    }

    public function stream()
    {
        $manager = $this->manager;
        $token = $this->param('token');
        $user = null;
        if ($token) {
            $repo = new UserTokenRepository();
            $user = $repo->findUserByToken($token);
        }
        $this->app->setCurrentUser($user);

        $this->response->stream(function() use ($manager) {
            if (function_exists('apache_setenv')) apache_setenv('no-gzip', '1');
            @ini_set('zlib.output_compression', 0);
            @ini_set('output_buffering', 'off');
            @ini_set('implicit_flush', 1);
            ob_implicit_flush(1);

            $lastData = null;
            $iterations = 0;
            $maxIterations = 720;
            while (connection_status() === CONNECTION_NORMAL && $iterations++ < $maxIterations) {
                $all = $manager->getAllKeyed();
                $payload = [
                    'status' => 'success',
                    'services' => $all,
                    'timestamp' => time(),
                ];
                $data = json_encode($payload, JSON_UNESCAPED_UNICODE);

                if ($data !== $lastData || $iterations % 12 === 0) {
                    echo "data: $data\n\n";
                    flush();
                    $lastData = $data;
                }
                usleep(5000000);
            }
        });
    }
}

