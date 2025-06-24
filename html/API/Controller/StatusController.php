<?php
namespace GIG\Api\Controller;

defined('_RUNKEY') or die;

use GIG\Domain\Services\ServiceStatusManager;

class StatusController extends ApiController
{
    protected ServiceStatusManager $manager;

    public function __construct()
    {
        parent::__construct();
        $this->manager = new ServiceStatusManager($this->app->getMysql());
    }

    public function getAll(): void
    {
        $statuses = $this->manager->getAll();
        $this->success(['services' => $statuses]);
    }

    public function stream()
    {
        $manager = $this->manager;
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
                    // @ob_flush();
                    flush();
                    $lastData = $data;
                }
                sleep(5);
            }
        });
    }
}

