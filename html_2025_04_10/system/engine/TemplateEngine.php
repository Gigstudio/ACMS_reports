<?php
namespace GigReportServer\System\Engine;

defined('_RUNKEY') or die;

use GigReportServer\System\Engine\Exceptions\GeneralException;

class TemplateEngine
{
    protected static string $layout = 'default';
    protected static array $parameters = [];

    public static function setLayout($layout) {
        self::$layout = $layout;
    }

    private static function getLayout() {
        $file = PATH_LAYOUTS . self::$layout . '.php';
        if (!file_exists($file)) {
            // throw new \Exception("Template file not found: $file", 404);
            throw new GeneralException("Страница не найдена", 404, [
                'detail' => "Шаблон ".self::$layout." не найден",
            ]);
        }
        ob_start();
        include($file);
        $layout = ob_get_clean();
        return $layout;
    }

    protected static function getContent(array $data) {
        $file = PATH_VIEWS . $data['content'] . '.php';
        if (!file_exists($file)) {
            // throw new GeneralException("View file not found: $file", 404);
            throw new GeneralException("Страница не найдена", 404, [
                'detail' => "Страница {$data['content']} не найдена",
            ]);
    }
        ob_start();
        $datacopy = json_decode(json_encode($data), true);
        if (!empty($datacopy)) {
            extract($datacopy, EXTR_OVERWRITE);
        }

        include_once($file);
        $content = ob_get_clean();
        return $content;
    }

    public static function render(array $data = []): string {
        $data['js'] = array_merge(
            is_array($commonJs = array_pop_by_key($data, 'common.js')) ? $commonJs : [],
            is_array($addJs = array_pop_by_key($data, 'add_js')) ? $addJs : []
        );

        $data['css'] = array_merge(
            is_array($commonCss = array_pop_by_key($data, 'common.css')) ? $commonCss : [],
            is_array($addCss = array_pop_by_key($data, 'add_css')) ? $addCss : []
        );

        $template = self::getLayout();
        $data['content'] = self::parseTemplate(self::getContent($data), $data);
        return self::parseTemplate($template, $data);
    }

    protected static function parseTemplate(string $tmpl, array $data): string {
        $getValue = function (string $key) use ($data) {
            $keys = explode('.', $key);
            $value = $data;
            foreach ($keys as $k) {
                if (!is_array($value) || !array_key_exists($k, $value)) {
                    return '';
                }
                $value = $value[$k];
            }
            return is_scalar($value) ? (string) $value : '';
        };

        // Рекурсивный парсер foreach
        $tmpl = preg_replace_callback('/{{foreach (\w+) as (\w+)(?: => (\w+))?}}(.*?){{endforeach}}/sU', 
            function ($matches) use ($data) {
                return self::handleForeach($matches, $data);
            }, 
        $tmpl);

        // Обработка {{insert.*}}
        $tmpl = preg_replace_callback('/{{insert\.(\w+)}}/', function ($matches) use ($data) {
            return self::handleInsert($matches[1], $data);
        }, $tmpl);

        // Обработка переменных {{content}}, {{title}}, {{user.name}}, и т.д.
        return preg_replace_callback('/\{\{([\w\.]+)\}\}/', function ($matches) use ($getValue) {
            return $getValue($matches[1]);
        }, $tmpl);
    }

    private static function handleForeach(array $matches, array $data): string {
        $arrayName = $matches[1];
        $keyVar = $matches[2];
        $valueVar = $matches[3] ?? null;
        $content = $matches[4];
    
        if (!isset($data[$arrayName]) || !is_array($data[$arrayName])) {
            return '';
        }
    
        $result = '';
        foreach ($data[$arrayName] as $key => $value) {
            $loopData = $data; // копируем оригинальные данные
            $loopData[$keyVar] = $key; // записываем ключ
            if ($valueVar !== null) {
                $loopData[$valueVar] = $value; // записываем значение
            } else {
                $loopData[$keyVar] = $value; // если нет valueVar, считаем, что это одномерный массив
            }
            $result .= self::parseTemplate($content, $loopData);
        }

        return $result;
    }

    private static function handleInsert(string $key, array $data): string {
        $value = $data[$key] ?? $data['common'][$key] ?? $data['app'][$key] ?? null;

        if (!is_array($value)) return '';

        if ($key === 'css') {
            return implode("\n", array_map(fn($css) => "<link rel=\"stylesheet\" href=\"".HOME_URL."assets/css/$css.css\">", $value));
        }

        if ($key === 'js') {
            return implode("\n", array_map(fn($js) => "<script type=\"module\" src=\"".HOME_URL."assets/js/$js.js\"></script>", $value));
        }

        if (str_ends_with($key, 'menu')) {
            $currentUrl = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
            return implode("\n", array_map(
                function ($item) use ($currentUrl) {
                    $isActive = ($currentUrl === parse_url($item['link'], PHP_URL_PATH)) ? ' active' : ''; 
                    return sprintf(
                        "<a title='%s' class='menu-item%s' href='%s'><span class='fadable1000'>%s</span>%s</a>",
                        htmlspecialchars($item['title'], ENT_QUOTES),
                        $isActive,
                        htmlspecialchars($item['link'], ENT_QUOTES),
                        htmlspecialchars($item['title'], ENT_QUOTES),
                        !empty($item['icon']) ? "<i class='" . htmlspecialchars($item['icon'], ENT_QUOTES) . "'></i>" : ''
                    );
                },
                $value
            )) . "\n";
        }

        return '';
    }
}
