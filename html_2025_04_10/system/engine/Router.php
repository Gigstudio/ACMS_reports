<?php
namespace GigReportServer\System\Engine;

defined('_RUNKEY') or die;

use GigReportServer\System\Http\Request;
use GigReportServer\System\Engine\Exceptions\GeneralException;

class Router
{
    private Request $request;
    protected array $routes = [];

    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    public function loadRoutes(): void {
        $routesFile = PATH_CONFIG . 'routes.php';
        $this->routes = file_exists($routesFile) ? include $routesFile : [];
    }

    public function dispatch()
    {
        try {
            $method = $this->request->getMethod();
            $uri = $this->request->getPath();
            $queryParams = $this->request->getBody();
    
            if (!isset($this->routes[$method])) {
                throw new GeneralException("Недопустимый метод $method", 405, [
                    'detail' => "URI: $uri, Проверьте routes.php. Убедитесь в наличии метода $method.",
                ]);
            }

            $routeParams = [];
            $controller = null;
            $action = null;
    
            foreach ($this->routes[$method] as $pattern => $callback) {
                if (preg_match($this->convertPattern($pattern), $uri, $matches)) {
                    array_shift($matches);
                    $routeParams = $matches;

                    if (is_array($callback) && count($callback) >= 2 && class_exists($callback[0])) {
                        $controller = $callback[0];
                        $action = $callback[1];
                        break;
                    }
                }
            }

            if (!$controller || !$action) {
                throw new GeneralException("Страница не найдена", 404, [
                    'detail' => "URI: $uri, Проверьте routes.php",
                ]);
            }

            try {
                $instance = new $controller();
            } catch (\Throwable $e) {
                throw new GeneralException("Ошибка контроллера", 500, [
                    'detail' => "URI: $uri, Проверьте $controller.php",
                ]);
            }

            if (!method_exists($instance, $action)) {
                throw new GeneralException("Страница не найдена", 404, [
                    'detail' => "URI: $uri, Метод '$action' не найден в контроллере $controller",
                ]);
            }

            $params = array_merge($routeParams, ['query' => $queryParams]);
            call_user_func_array([$instance, $action], [$params]);
        } catch (GeneralException $e) {
            ErrorHandler::handleException($e);
        }
    }
        
    protected function convertPattern(string $pattern): string
    {
        return "#^" . preg_replace('/\{(\w+)\}/', '(?P<$1>[^/]+)', $pattern) . "$#";
    }
}
