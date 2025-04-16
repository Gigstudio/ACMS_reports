<?php
namespace GigReportServer\System;

class Autoloader
{
    protected $prefixes = array();
    private $cache = [];

    public function register()
    {
        spl_autoload_register(array($this, 'loadClass'));
    }

    public function addNamespace($prefix, $base_dir, $prepend = false)
    {
        $prefix = trim($prefix, '\\') . '\\';
        $base_dir = rtrim($base_dir, DS) . '/';
        if (isset($this->prefixes[$prefix]) === false) {
            $this->prefixes[$prefix] = array();
        }
        if ($prepend) {
            array_unshift($this->prefixes[$prefix], $base_dir);
        } else {
            array_push($this->prefixes[$prefix], $base_dir);
        }
    }

    public function getNamespaces()
    {
        return $this->prefixes;
    }

    public function loadClass($class)
    {
        $prefix = $class;
        while (false !== $pos = strrpos($prefix, '\\')) {
            $prefix = substr($class, 0, $pos + 1);
            $relative_class = substr($class, $pos + 1);
            $mapped_file = $this->loadMappedFile($prefix, $relative_class);
            if ($mapped_file) {
                return $mapped_file;
            }
            $prefix = rtrim($prefix, '\\');
        }
        return false;
    }

    protected function loadMappedFile($prefix, $relative_class)
    {
        if (isset($this->prefixes[$prefix]) === false) {
            return false;
        }
        foreach ($this->prefixes[$prefix] as $base_dir) {
            $file = $base_dir
                  . str_replace('\\', DS, $relative_class)
                  . '.php';

                  if (isset($this->cache[$file])) {
                    require $this->cache[$file];
                    return $this->cache[$file];
                }
    
                if (!file_exists($file)) {
                    continue;
                }
    
                $realPath = realpath($file);
                if (!$realPath) {
                    error_log("Autoload failed: {$file} (realpath failed)");
                    continue;
                }
    
                $this->cache[$file] = $realPath;
                require $realPath;
                return $realPath;
            }
        return false;
    }

    protected function requireFile($file)
    {
        if (isset($this->cache[$file])){
            require $this->cache[$file];
            return true;
        }
        if(!file_exists($file)){
            error_log("Autoload failed: {$file} (file does not exist)");
            return false;
        }
        $realPath = realpath($file);
        if (!$realPath) {
            error_log("Autoload failed: {$file} (realpath failed)");
            return false;
        }
    
        $this->cache[$file] = $realPath;
        require $realPath;
        return true;
    }
}
