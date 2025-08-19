<?php
namespace GIG\Infrastructure\Persistence;

defined('_RUNKEY') or die;

use GIG\Core\Application;
use GIG\Domain\Exceptions\GeneralException;

class SupervisorClient
{
    private string $endpoint;
    private string $user;
    private string $pass;
    private int $timeout = 5;
    protected array $config;
    private array $lastStatus = [
        'available' => false,
        'statename' => null,
        'statecode' => null,
        'api'       => null,
        'note'      => null,
    ];

    public function __construct(array $config = [])
    {
        $config = $config ?: $this->getDefaultConfig();

        $this->endpoint = rtrim((string)($config['endpoint'] ?? ''), '/');
        $this->user     = (string)($config['sv_user'] ?? '');
        $this->pass     = (string)($config['sv_pass'] ?? '');

        if ($this->endpoint === '') {
            throw new GeneralException("Эндпоинт супервизора не задан", 500, [
                'detail' => "Проверьте конфигурацию в init.json: services.Supervisor.endpoint"
            ]);
        }
        if ($this->user === '' || $this->pass === '') {
            throw new GeneralException("Пользователь супервизора не задан", 500, [
                'detail' => "Проверьте конфигурацию в init.json: services.Supervisor.sv_user/sv_pass"
            ]);
        }
    }

    protected function getDefaultConfig(): array
    {
        return Application::getInstance()->getConfig('services.Supervisor') ?? [];
    }

    private function makeRpcUrl(): string
    {
        $u = rtrim($this->endpoint, '/');
        // Удаляем уже присутствующий хвост /RPC2 (с любым регистром)
        $u = preg_replace('~/(RPC2)$~i', '', $u);
        return $u . '/RPC2';
    }

    private function rpc(string $method, array $params = []): \SimpleXMLElement
    {
        $xml = $this->buildRequest($method, $params);

        $url = $this->makeRpcUrl();
        // if (preg_match('~/RPC2/?~', $url)) {
        //     $url = rtrim($url, '/') . '/RPC2/';
        // }
        // file_put_contents(PATH_LOGS . 'sv.log', print_r($xml, true) . PHP_EOL, FILE_APPEND);
        // file_put_contents(PATH_LOGS . 'sv.log', print_r($url, true) . PHP_EOL, FILE_APPEND);
        

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST            => true,
            CURLOPT_POSTFIELDS      => $xml,
            CURLOPT_RETURNTRANSFER  => true,
            CURLOPT_HTTPHEADER      => ['Content-Type: text/xml'],
            CURLOPT_USERPWD         => "{$this->user}:{$this->pass}",
            CURLOPT_TIMEOUT         => $this->timeout,
        ]);

        $response = curl_exec($ch);
        if ($response === false) {
            $err = curl_error($ch);
            curl_close($ch);
            throw new GeneralException('Supervisor RPC network error', 500, [
                'detail' => $err]
            );
        }
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        // file_put_contents(PATH_LOGS . 'sv.log', $ch . PHP_EOL, FILE_APPEND);
        curl_close($ch);

        if ($code !== 200) {
            throw new GeneralException('Supervisor RPC HTTP error', $code, [
                'detail' => "HTTP $code"]
            );
        }

        $sx = @simplexml_load_string($response);
        if ($sx === false) {
            throw new GeneralException('Invalid XML-RPC response', 500, [
                'detail' => $response]
            );
        }
        if (isset($sx->fault)) {
            $fault = $this->xmlFaultToString($sx->fault);
            throw new GeneralException('Supervisor RPC fault', 500, [
                'detail' => $fault]
            );
        }

        return $sx;
    }

    private function buildRequest(string $method, array $params): string{
        $paramsXml = '';
        foreach ($params as $p) {
            $paramsXml .= '<param>' . $this->xmlValue($p) . '</param>';
        }
        return <<<XML
<?xml version="1.0"?>
<methodCall>
    <methodName>{$method}</methodName>
    <params>{$paramsXml}</params>
</methodCall>
XML;        
    }

    private function xmlValue($v): string{
        switch (gettype($v)) {
            case 'boolean': return '<value><boolean>' . ($v ? '1' : '0') . '</boolean></value>';
            case 'integer': return '<value><int>' . $v . '</int></value>';
            case 'double':  return '<value><double>' . $v . '</double></value>';
            case 'array':
                $isAssoc = array_keys($v) !== range(0, count($v) - 1);
                if ($isAssoc) {
                    $members = '';
                    foreach ($v as $k => $val) {
                        $members .= '<member><name>' . htmlspecialchars((string)$k, ENT_XML1|ENT_QUOTES, 'UTF-8') .
                                    '</name>' . $this->xmlValue($val) . '</member>';
                    }
                    return '<value><struct>' . $members . '</struct></value>';
                }
                $items = '';
                foreach ($v as $item) $items .= $this->xmlValue($item);
                return '<value><array><data>' . $items . '</data></array></value>';
            default:
                return '<value><string>' . htmlspecialchars((string)$v, ENT_XML1|ENT_QUOTES, 'UTF-8') . '</string></value>';
        }
    }

    private function xmlFaultToString(\SimpleXMLElement $fault): string{
        $code = null;
        $str = null;
        foreach ($fault->value->struct->member as $m) {
            $n = (string)$m->name;
            if ($n === 'faultCode')     $code = (string)($m->value->int ?? $m->value);
            if ($n === 'faultString')   $str = (string)($m->value->string ?? $m->value);
        }
        return trim(($code !== null ? "[$code] " : '') . (string)$str);
    }

    private function sxGet(\SimpleXMLElement $root, string $membersXpath, string $name): ?string
    {
        foreach ($root->xpath($membersXpath) ?? [] as $m) {
            if ((string)$m->name === $name) {
                return (string)($m->value->string ?? $m->value->int ?? $m->value->boolean ?? $m->value);
            }
        }
        return null;
    }

    public function getAllProcesses(): array{
        $resp = $this->rpc('supervisor.getAllProcessInfo');
        $out = [];

        foreach ($resp->params->param->value->array->data->value ?? [] as $v) {
            $item = [];
            foreach ($v->struct->member as $m) {
                $k = (string)$m->name;
                $item[$k] = (string)($m->value->string ?? $m->value->int ?? $m->value->boolean ?? $m->value);
            }
            $out[] = $item;
        }
        return $out;
    }

    public function getProcessInfo(string $program): array{
        $resp = $this->rpc('supervisor.getProcessInfo', [$program]);
        $item = [];
        foreach ($resp->params->param->value->struct->member ?? [] as $m) {
            $k = (string)$m->name;
            $item[$k] = (string)($m->value->string ?? $m->value->int ?? $m->value->boolean ?? $m->value);
        }
        return $item;
    }

    public function start(string $program, bool $wait = true): bool
    {
        $resp = $this->rpc('supervisor.startProcess', [$program, $wait]);
        return ((string)$resp->params->param->value->boolean) === '1';
    }

    public function stop(string $program, bool $wait = true): bool
    {
        $resp = $this->rpc('supervisor.stopProcess', [$program, $wait]);
        return ((string)$resp->params->param->value->boolean) === '1';
    }

    public function restart(string $program): bool
    {
        try { $this->stop($program, true); } catch (\Throwable $e) {}
        usleep(300_000);
        return $this->start($program, true);
    }

    public function checkStatus()
    {
        try {
            $api = $this->rpc('supervisor.getAPIVersion');
            $state = $this->rpc('supervisor.getState'); // struct { statecode:int, statename:string }

            $code = $this->sxGet($state, 'params.param.value.struct.member', 'statecode');
            $name = $this->sxGet($state, 'params.param.value.struct.member', 'statename');
            $apiVer = (string)($api->params->param->value->string ?? null);

            $this->lastStatus = [
                'available' => true,
                'statename' => $name,
                'statecode' => is_numeric($code) ? (int)$code : null,
                'api'       => $apiVer,
                'note'      => null,
            ];
                return [
                    'status' => 'ok',
                    'message' => 'Supervisor доступен'
                ];
        } catch (\Throwable $e) {
            $msg = $e->getMessage();
            $this->lastStatus = [
                'available' => false,
                'statename' => null,
                'statecode' => null,
                'api'       => null,
                'note'      => $msg,
            ];
            return [
                'status'  => 'fail',
                'message' => 'Supervisor недоступен: ' . $msg,
                // чтобы не ломать твой воркер (он читает 'detail'), кладём оба ключа:
                'detail'  => (string)$e,
                'details' => ['exception' => (string)$e],
            ];
        }
    }
}