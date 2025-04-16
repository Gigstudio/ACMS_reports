<?php
defined('_RUNKEY') or die;

if(!function_exists('var_dump')){
	function debug_dump($var){
		echo '<pre>' . htmlspecialchars(print_r($var, true)) . '</pre>';
	}
}

if (!function_exists('str_contains')) {
	function str_contains($haystack, $needle) {
		if (!function_exists('mb_strpos')) {
			return strpos($haystack, $needle) !== false;
		}
		return $needle !== '' && mb_strpos($haystack, $needle) !== false;
	}
}

function trimSpaces($str, $separator = ',', $key = 'string') {
	$arr = array_filter(array_map('trim', preg_split('/[;,]/', $str)));
	return $key === 'array' ? $arr : implode($separator . ' ', $arr);
}

function combine_arr($a, $b) {
	$size = min(count($a), count($b));
	$a = array_slice($a, 0, $size);
	$b = array_slice($b, 0, $size);
	return array_combine($a, $b) ?: [];
}

function flattenArray(array $array, string $prefix = ''): array
{
    $result = [];

    foreach ($array as $key => $value) {
        $fullKey = $prefix ? "{$prefix}.{$key}" : $key;

        if (is_array($value)) {
            $result += flattenArray($value, $fullKey);
        } else {
            $result[$fullKey] = $value;
        }
    }

    return $result;
}

function array_pop_by_key(array &$array, string $path, string $delimiter = '.'): mixed
{
    $keys = explode($delimiter, $path);
    $lastKey = array_pop($keys);
    $target = &$array;

    foreach ($keys as $key) {
        if (!isset($target[$key]) || !is_array($target[$key])) {
            return null;
        }
        $target = &$target[$key];
    }

    if (!array_key_exists($lastKey, $target)) {
        return null;
    }

    $value = $target[$lastKey];
    unset($target[$lastKey]);
    return $value;
}

function system_warn(string $msg): void {
    trigger_error($msg, E_USER_WARNING);
}
