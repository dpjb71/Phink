<?php
/*
 * Copyright (C) 2016 David Blanchard
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */
 
namespace Phink;

use Phink\Core\TRegistry;
use Phink\Log\TLog;

class TAutoloader
{
    private $directory;
    private $prefix;
    private $prefixLength;
    private static $logger = null;

    /**
     * @param string $baseDirectory Base directory where the source files are located.
     */
    public function __construct($baseDirectory = __DIR__)
    {
        $this->directory = $baseDirectory;
        $this->prefix = __NAMESPACE__ . '\\';
        $this->prefixLength = strlen($this->prefix);
        self::$logger = new TLog();
    }

    /**
     * Registers the autoloader class with the PHP SPL autoloader.
     *
     * @param bool $prepend Prepend the autoloader on the stack instead of appending it.
     */
    public static function register($prepend = false)
    {
        spl_autoload_register(array(new self, 'autoload'), true, $prepend);
    }

    public static function classNameToFilename($className)
    {
        $translated = '';
        $className = substr($className, 1);
        $l = strlen($className);
        for($i = 0; $i < $l; $i++) {
            if(ctype_upper($className[$i])) {
                $translated .= '_' . strtolower($className[$i]);
            } else {
                $translated .= $className[$i];
            }
        }

        $translated = substr($translated, 1);

        return $translated;
    }

    public static function cacheFilenameFromView($viewName) {
        return strtolower('app_controllers_' . $viewName . '_' . $viewName . CLASS_EXTENSION);
    }

    public static function cacheJsFilenameFromView($viewName) {
        return strtolower('app_controllers_' . $viewName . '_' . $viewName . JS_EXTENSION);
    }

    public static function cachePath($filepath) {
        return  str_replace(DIRECTORY_SEPARATOR, '_', $filepath);
    }

    /**
     * Loads a class from a file using its fully qualified name.
     *
     * @param string $className Fully qualified name of a class.
     */
    public function autoload($className)
    {
        if (0 === strpos($className, $this->prefix)) {
            $parts = explode('\\', substr($className, $this->prefixLength));
            $className = array_pop($parts);
            
            $translated = self::classNameToFilename($className);
            
            $filepath = $this->directory . DIRECTORY_SEPARATOR . strtolower(implode(DIRECTORY_SEPARATOR, $parts) . DIRECTORY_SEPARATOR . $translated);
            $filepath .= (file_exists($filepath . PREHTML_EXTENSION)) ? CLASS_EXTENSION : '.php';
            
            //file_put_contents(STARTER_FILE, "include '$filepath';"  . "\n", FILE_APPEND);
            include $filepath;
            
        }
    }
    
    private static function _includeInnerClass($viewName, $info, $withCode = true)
    {
        $className = ucfirst($viewName);
        $filename = ROOT_PATH . $info->path . \Phink\TAutoloader::classNameToFilename($viewName) . CLASS_EXTENSION;
        //self::$logger->debug('INCLUDE INNER PARTIAL CONTROLLER : ' . $filename, __FILE__, __LINE__);

        $code = file_get_contents($filename, FILE_USE_INCLUDE_PATH);
        
        $file = str_replace('\\', '_', $info->namespace . '\\' . $viewName) . '.php';
        if(!file_exists(RUNTIME_DIR . $file)) {
            include $filename;
        }
       
        if($withCode) {
            $code = substr(trim($code), 0, -2) . CR_LF . CONTROL_ADDITIONS;
            TRegistry::setCode($filename, $code);
        }
        
        return ['file' => $file, 'type' => $info->namespace . '\\' . $className, 'code' => $code];
        
          
    }
    
    /**
     * Load the controller file, parse it in search of namespace and classname.
     * Alternatively execute the code if the class is not already declared 
     * 
     * @param string $filename The controller filename 
     * @param int $params The bitwise constants values that determine the behavior
     *                    INCLUDE_FILE : execute the code
     *                    RETURN_CODE : ...
     * @return boolean
     */
    public static function includeClass($filename, $params = 0)
    {

        if(!file_exists(APP_ROOT . $filename)) {
            //self::$logger->debug('INCLUDE CLASS : FILE ' . $filename . ' DOES NOT EXIST');
            return false;
        }
        
        $classText = file_get_contents(APP_ROOT . $filename, FILE_USE_INCLUDE_PATH);
        
        $code = $classText;
        
        $classText = str_replace("\r", '', $classText);
        $classText = str_replace("\n", '', $classText);
        
        $start = strpos($classText, 'namespace');
        $namespace = '';
        if ($start > 0) {
            $start += 10;
            $end = strpos($classText, ';', $start);
            $namespace = substr($classText, $start, $end - $start);
        }
        
        $start = strpos($classText, 'class');
        $className = '';
        if ($start > 0) {
            $start += 6;
            $end = strpos($classText, '{', $start);
            $className = substr($classText, $start, $end - $start);
            $className = trim($className);
            $sa = explode(' ', $className);
            $className = $sa[0];
        }
        
        $fqcn = $namespace . '\\' . $className;
        $file = str_replace('\\', '_', $fqcn) . '.php';
        
        $file = $filename;
        if(isset($params) && ($params && RETURN_CODE === RETURN_CODE)) {
            $code = substr(trim($code), 0, -2) . CR_LF . CONTROL_ADDITIONS;
            TRegistry::setCode($filename, $code);
        }
        
        self::$logger->debug(__METHOD__ . '::' . $file, __FILE__, __LINE__);
        
        if((isset($params) && ($params && INCLUDE_FILE === INCLUDE_FILE)) && !class_exists('\\' . $fqcn))  {
            include APP_ROOT . $filename;
        }
        
        return ['file' => $file, 'type' => $fqcn, 'code' => $code];
        
    }

    public static function includeModelByName($modelName)
    {
        $result = false;
        
        //self::$logger->debug('MODEL NAME : ' . $modelName, __FILE__, __LINE__);
        
        $modelFileName = 'app' . DIRECTORY_SEPARATOR . 'models' . DIRECTORY_SEPARATOR . $modelName . CLASS_EXTENSION;
        
        $result = self::includeClass($modelFileName, INCLUDE_FILE);
        if(!$result) {
            $result['type'] = DEFALT_MODEL;
        }
        $result['file'] = $modelFileName;
        
        return $result;
    }
    
    public static function includeControllerByName($viewName)
    {
        $result = false;
        $controllerFileName = 'app' . DIRECTORY_SEPARATOR . 'controllers' . DIRECTORY_SEPARATOR . $viewName . DIRECTORY_SEPARATOR . $viewName . CLASS_EXTENSION;
        
        $result = self::includeClass($controllerFileName, RETURN_CODE | INCLUDE_FILE);
        if(!$result) {
            $sa = explode('.', SERVER_NAME);
            array_pop($sa);
            if(count($sa) == 2) {
                array_shift($sa);
            }
            $namespace = ucfirst($sa[0]);
            $namespace .= '\\Controllers'; 
            $className = ucfirst($viewName);
            
            $result = self::includeDefaultController($namespace, $className);
            TRegistry::setCode('app' . DIRECTORY_SEPARATOR . 'controllers' . DIRECTORY_SEPARATOR . $viewName . CLASS_EXTENSION, $result['code']);
        }

        return $result;
    }

    public static function includePartialControllerByName($viewName)
    {
        $result = false;
        $controllerFileName = 'app' . DIRECTORY_SEPARATOR . 'controllers' . DIRECTORY_SEPARATOR . $viewName . DIRECTORY_SEPARATOR . $viewName . CLASS_EXTENSION;
        if(file_exists($controllerFileName)) {
            //self::$logger->debug('INCLUDE CUSTOM PARTIAL CONTROLLER : ' . $controllerFileName, __FILE__, __LINE__);
            $result = self::includeClass($controllerFileName, RETURN_CODE | INCLUDE_FILE);
        } elseif ($info = Core\TRegistry::classInfo($viewName)) {
            $result = self::_includeInnerClass($viewName, $info, true);
        } else {
            //self::$logger->debug('INCLUDE DEFAULT PARTIAL CONTROLLER : ' . $controllerFileName, __FILE__, __LINE__);
            $namespace = self::getDefaultNamespace();
            $className = ucfirst($viewName);
            $result = self::includeDefaultPartialController($namespace, $className);
            TRegistry::setCode('app' . DIRECTORY_SEPARATOR . 'controllers' . DIRECTORY_SEPARATOR . $viewName . CLASS_EXTENSION, $result['code']);
        }

        return $result;
    }
    
    public static function includeDefaultController($namespace, $className)
    {
        $result['type'] = DEFAULT_CONTROLLER;
        $result['code'] = self::controllerTemplate($namespace, $className);
        $result['code'] = substr(trim($result['code']), 0, -2) . CONTROL_ADDITIONS;

        return $result;
    }

    public static function includeDefaultPartialController($namespace, $className)
    {
        $result['type'] = DEFAULT_PARTIAL_CONTROLLER;
        $result['code'] = self::partialControllerTemplate($namespace, $className);
        $result['code'] = substr(trim($result['code']), 0, -2) . CONTROL_ADDITIONS;

        return $result;
    }

    public static function import(Web\UI\TCustomControl $ctrl, $viewName)
    {
        $result = false;
        $cacheFilename = '';
        //$classFilename = '';
        $cacheJsFilename = '';
        
        if($info = Core\TRegistry::classInfo($viewName)) {
            //$classFilename = ROOT_PATH . $info->path . \Phink\TAutoloader::classNameToFilename($viewName) . CLASS_EXTENSION;
            $cacheFilename = RUNTIME_DIR . str_replace(DIRECTORY_SEPARATOR, '_', ROOT_PATH . $info->path . \Phink\TAutoloader::classNameToFilename($viewName)) . CLASS_EXTENSION;
        } else {
            //$classFilename = 'app' . DIRECTORY_SEPARATOR . 'controllers' . DIRECTORY_SEPARATOR . $viewName . DIRECTORY_SEPARATOR . $viewName . CLASS_EXTENSION;
            $cacheFilename = RUNTIME_DIR . \Phink\TAutoloader::cacheFilenameFromView($viewName);
            $cacheJsFilename = RUNTIME_JS_DIR . \Phink\TAutoloader::cacheJsFilenameFromView($viewName);
            $cacheJsFilename = str_replace(CLASS_EXTENSION, JS_EXTENSION, $cacheJsFilename);
            self::$logger->debug('CACHED JS FILENAME: ' . $cacheJsFilename, __FILE__, __LINE__);
            
        }
        
        if(file_exists($cacheFilename)) {

            if(file_exists($cacheJsFilename)) {
                self::$logger->debug('INCLUDE CACHED JS CONTROL: ' . $cacheJsFilename, __FILE__, __LINE__);
                $ctrl->getResponse()->addScript($cacheJsFilename);
            }
            self::$logger->debug('INCLUDE CACHED CONTROL: ' . $cacheFilename, __FILE__, __LINE__);
            self::includeClass($cacheFilename, RETURN_CODE | INCLUDE_FILE);

            
        } else {
            //self::$logger->debug('INCLUDE NEW CONTROL : ' . $viewName, __FILE__, __LINE__);
            //include 'app' . DIRECTORY_SEPARATOR . 'controllers' . DIRECTORY_SEPARATOR . $viewName . DIRECTORY_SEPARATOR . $viewName . CLASS_EXTENSION;
            $result = self::includePartialControllerByName($viewName);
        }

        return $result;
    }
    
    public static function getDefaultNamespace()
    {
        $sa = explode('.', SERVER_NAME);
        array_pop($sa);
        if(count($sa) == 2) {
            array_shift($sa);
        }

        return ucfirst(str_replace('-', '_', $sa[0])) . '\\Controllers';
    }
    
    public static function controllerTemplate($namespace, $className)
    {
        $result = <<<CONTROLLER
<?php
namespace $namespace;

use Phink\MVC\TController;

class $className extends TController
{
    
       
}
CONTROLLER;
        
        return $result;
        
    }

    public static function partialControllerTemplate($namespace, $className)
    {
        $result = <<<CONTROLLER
<?php
namespace $namespace;

use Phink\MVC\TPartialController;

class $className extends TPartialController
{
    
       
}
CONTROLLER;
        
        return $result;
        
    }
    
}
