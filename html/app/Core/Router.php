<?php
namespace GIG\Core;

defined('_RUNKEY') or die;

use GIG\Domain\Exceptions\GeneralException;

class Router
{
    private Request $request;
    protected array $routes = [];

    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    public function getAllRoutes(){
        return $this->routes;
    }

    /**
     * Загружает маршруты из файла или БД.
     */
    public function loadRoutes(): void
    {
        static $cachedRoutes = null;

        if ($cachedRoutes === null) {
            $cachedRoutes = [];
            $api = $this->getApiRoutesFromFile();
            $ui  = $this->getRoutesFromFile();

            // Слияние по методам
            foreach (['GET', 'POST', 'PUT', 'PATCH', 'DELETE'] as $method) {
                $cachedRoutes[$method] = [];
                if (isset($api[$method])) $cachedRoutes[$method] += $api[$method];
                if (isset($ui[$method]))  $cachedRoutes[$method] += $ui[$method];
            }
        }
        $this->routes = $cachedRoutes;
    }

    private function getApiRoutesFromFile(): array
    {
        $routesFile = PATH_CONFIG . 'apimap.php';
        return file_exists($routesFile) ? include $routesFile : [];
    }
    
    private function getRoutesFromFile(): array
    {
        $routesFile = PATH_CONFIG . 'routes.php';
        return file_exists($routesFile) ? include $routesFile : [];
    }

    /**
     * Основной диспатчер.
     */
    public function dispatch(): void
    {
        try {
            $method = $this->request->getMethod();
            $uri    = $this->request->getPath();

            if (!isset($this->routes[$method])) {
                throw new GeneralException("Недопустимый метод $method", 405, [
                    'detail' => "URI: $uri, Проверьте routes.php. Убедитесь в наличии метода $method."
                ]);
            }

            $routeParams = [];
            $controller  = null;
            $action      = null;

            foreach ($this->routes[$method] as $pattern => $callback) {
                if (preg_match($this->convertPattern($pattern), $uri, $matches)) {
                    // Только именованные параметры (без числовых ключей)
                    $routeParams = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);

                    if (is_array($callback) && count($callback) >= 2) {
                        $controller = $callback[0];
                        $action     = $callback[1];
                        break;
                    }
                }
            }

            if (!$controller || !$action) {
                throw new GeneralException("Страница не найдена", 404, [
                    'detail' => "URI: $uri, Проверьте routes.php"
                ]);
            }

            if (!method_exists($controller, $action)) {
                throw new GeneralException("Страница не найдена", 404, [
                    'detail' => "URI: $uri, Метод '$action' не найден в контроллере $controller"
                ]);
            }

            $instance = new $controller();
            $this->request->setRouteParams($routeParams);
            $instance->$action($this->request);

        } catch (GeneralException $e) {
            ErrorHandler::handleException($e);
        }
    }

    /**
     * Преобразует маршрут /foo/{id} в регулярку.
     */
    protected function convertPattern(string $pattern): string
    {
        return "#^" . preg_replace('/\{(\w+)\}/', '(?P<$1>[^/]+)', $pattern) . "$#";
    }
}
