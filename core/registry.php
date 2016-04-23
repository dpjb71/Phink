<?php

namespace Phoenix\Core;

/**
 * Description of registry
 *
 * @author david
 */

class TRegistry
{
    
    private static $_classRegistry = null;
    private static $_code = [];
    private static $_items = [];
    
    public static function init()
    {
        if(self::$_classRegistry) return;
        
        self::$_classRegistry = array (
            'TPager' => array(
                'alias' => 'pager',
                'path' => DIRECTORY_SEPARATOR . 'web' . DIRECTORY_SEPARATOR . 'ui' . DIRECTORY_SEPARATOR . 'widget' . DIRECTORY_SEPARATOR . 'pager' . DIRECTORY_SEPARATOR, 
                'namespace' => ROOT_NAMESPACE . '\Web\UI\Widget\Pager', 
                'hasTemplate' => true, 
                'canRender' => true
            )
            , 'TPluginRenderer' => array(
                'alias' => 'pluginrenderer',
                'path' => DIRECTORY_SEPARATOR . 'web' . DIRECTORY_SEPARATOR . 'ui' . DIRECTORY_SEPARATOR, 
                'namespace' => ROOT_NAMESPACE . '\Web\UI', 
                'hasTemplate' => false, 
                'canRender' => true
            ) 
            , 'TPlugin' => array(
                'alias' => 'plugin',
                'path' => DIRECTORY_SEPARATOR . 'web' . DIRECTORY_SEPARATOR . 'ui' . DIRECTORY_SEPARATOR . 'widget' . DIRECTORY_SEPARATOR . 'plugin' . DIRECTORY_SEPARATOR, 
                'namespace' => ROOT_NAMESPACE . '\Web\UI\Widget\Plugin', 
                'hasTemplate' => true, 
                'canRender' => true
            )
            , 'TPluginChild' => array(
                'alias' => 'pluginchild',
                'path' => DIRECTORY_SEPARATOR . 'web' . DIRECTORY_SEPARATOR . 'ui' . DIRECTORY_SEPARATOR . 'widget' . DIRECTORY_SEPARATOR . 'plugin' . DIRECTORY_SEPARATOR, 
                'namespace' => ROOT_NAMESPACE . '\Web\UI\Widget\Plugin', 
                'hasTemplate' => false, 
                'canRender' => false
            )
        );
    
    }
    
    public static function classInfo($className = '')
    {
        self::init();
        return (isset(self::$_classRegistry[$className]) ? (object) self::$_classRegistry[$className] : false);
    }
    
    public static function classPath($className = '')
    {
        $classInfo = self::classInfo($className);
        return ($classInfo) ? $classInfo->path : '';
    }    

    public static function classNamespace($className = '')
    {
        $classInfo = self::classInfo($className);
        return ($classInfo) ? $classInfo->namespace : '';
    }    

    public static function classHasTemplate($className = '')
    {
        $classInfo = self::classInfo($className);
        return ($classInfo) ? $classInfo->hasTemplate : '';
    }    
    
    public static function classCanRender($className = '')
    {
        $classInfo = self::classInfo($className);
        return ($classInfo) ? $classInfo->canRender : '';
    }
    
    public static function getRegisteredCode($id)
    {
        return self::$_code[$id];
    }
    
    public static function registerCode($id, $value)
    {
        self::$_code[$id] = $value;
        //$id = str_replace(DIRECTORY_SEPARATOR, '_', $id);
        //file_put_contents(TMP_DIR . DIRECTORY_SEPARATOR . $id . PREHTML_EXTENSION, $value);
        //$keys = array_keys(self::$_code);
        //\Phoenix\Log\TLog::debug('CODE REGISTRY : ' . print_r($keys, true));
    }
    
    public static function write($item, $key, $value) {

        if (self::$_items[$item] === null) {
            self::$_items[$item] = [];
        }
        self::$_items[$item][$key] = value;

    }

    public static function read($item, $key, $defaultValue) {
        $result = null;

        if (self::$_items[$item] !== null) {
            $result = (self::$_items[$item][$key] !== null) ? self::$_items[$item][$key] : (($defaultValue !== null) ? $defaultValue : null);
        }

        return $result;
    }

    public static function remove($item) {
        if(array_key_exists($item, self::$_items)) {
            unset(self::$_items[$item]);
        }
    }
    
    public static function keys($item = null) {
        if($item === null) {
            return array_keys(self::$_items);
        } else if(is_array(self::$_items)) {
            return array_keys(self::$_items[$item]);
        } else {
            return [];
        }
     }

    public static function item($item) {
        if($item === '' || $item === null) return $item;

        array_push(self::$_items, $item);
        
        if(self::$_items[$item] !== null) {
            return self::$_items[$item];
        } else {
            self::$_items[$item] = [];
            return self::$_items[$item];
        }
    }

    public static function clear() {
        TRegistry::$_items = [];
    }
    
    
}
