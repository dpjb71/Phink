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
 

namespace Phink\Core;

//$single_server = array(
//    'host' => '192.168.1.8',
//    'port' => 6379,
//    'database' => 15
//);

include 'constants.php';

/**
 * Description of Application
 *
 * @author david
 */

use Phink\Core\TObject;
use Phink\MVC\TView;
use Phink\Auth\TAuthentication;

class TApplication extends TObject
{
    
    //put your code here
    const DEBUG_MODE = 'DEBUG';
    const TEST_MODE = 'TEST';
    const PROD_MODE = 'PROD';
    
    
    private static $_executionMode = self::PROD_MODE;
    private static $_verboseMode = false;
    private static $_useTransactions = true;
    private $_argv;
    private $_argc;
    private $_parameters = array();
    private $_name = 'program';
    protected $appDirectory = '';


    private $redis = null;

    public function __construct($argv = [], $argc = 0, $appDirectory = '') {

        parent::__construct();
//        if(!class_exists('\Phink\TAutoloader')) {
//            include 'phink/autoloader.php';
//            \Phink\TAutoLoader::register();
//        }
        $this->appDirectory = $appDirectory . DIRECTORY_SEPARATOR;
        $path = explode(DIRECTORY_SEPARATOR, $appDirectory);
        
        array_pop($path);
        if(APP_IS_PHAR) {
            array_pop($path);
            $currentDir = $this->appDirectory . '..';
            $currentDir = \Phink\Utils\TFileUtils::relativePathToAbsolute($currentDir);
            $this->appDirectory = str_replace('phar://', '', $currentDir) . DIRECTORY_SEPARATOR;
        }         
        
        $this->_name = array_pop($path);
        
        \Phink\UI\TConsoleApplication::writeLine($this->_name);
        
        //$this->_name = $argv[0];
        $this->_argv = $argv;
        $this->_argc = $argc;
        
        if($this->getArgument('make-phar')) {
            \Phink\UI\TConsoleApplication::writeLine('MAKE PHAR');
            $this->makePhar();
        }
        
        if($this->getArgument('require-master')) {
            $this->_requireMaster();
        }
        
        if($this->getArgument('display-tree')) {
            $this->displayTree($this->appDirectory);
        }
        
        if($this->getArgument('display-master-tree')) {
            try {
                $this->displayTree('master' . DIRECTORY_SEPARATOR . 'Phink-master' . DIRECTORY_SEPARATOR. 'src' . DIRECTORY_SEPARATOR . 'phink');
            } catch (\Throwable $ex) {
                \Phink\UI\TConsoleApplication::writeException($ex);
            } catch (\Exception $ex) {
                \Phink\UI\TConsoleApplication::writeException($ex);
            }
        }
        
        if($this->getArgument('os')) {
            \Phink\UI\TConsoleApplication::writeLine($this->getOS());
        }
        
    }
    
    public function getApplicationDirectory() {
        return $this->appDirectory;
    }

    public function getArgument($long, $short = '')
    {
        $result = false;
        $isFound = false;
        array_push($this->_parameters, ['long' => $long, 'short' => $short]);
        
        if(!APP_IS_WEB) {
            
            $c = count($this->_argv);
            for ($i = 0; $i < $c; $i++) {
                if ($this->_argv[$i] == '--' . $long)  {
                    if(isset($this->_argv[$i+1])) {
                        if(substr($this->_argv[$i+1], 0, 1) == '-') {
                            $result = true;
                        } else {
                            $result = $this->_argv[$i + 1];
                        }
                    } else {
                        $result = true;
                    }
                    $isFound = true;
                    break;
                } else if($this->_argv[$i] == '-' . $short) {

                    $sa = explode('=', $this->_argv[$i]);
                    if(count($sa) > 1) {
                        $result = $sa[1];
                    } else {
                        $result = true;
                    }
                    $isFound = true;
                    break;
                }
            }
        } else {
            if(isset($_REQUEST[$short])) {
                $result = $_REQUEST[$short];
                $isFound = true;
            } elseif(isset($_REQUEST[$long])) {
                $result = $_REQUEST[$long];
                $isFound = true;
            }
        }
        
        if(!$isFound) {
            $lonException = '';
            $several = '';
            if(isset($long)) {
                $lonException = '|' . $long;
                $several = 's';
            }
            
            //throw new InvalidArgumentException("Argument$several introuvable$several : " . $short . $lonException);
        }
        
        return $result;
    }

    public static function getExecutionMode()
    {
        return self::$_executionMode;
    }
    
    public function getOS()
    {
        return PHP_OS;
    }

    public static function setExecutionMode($myExecutionMode)
    {
        if(!$myExecutionMode) {
            $myExecutionMode = (APP_IS_WEB) ?  'debug' : 'prod';
        }
        
        $prod = ($myExecutionMode == 'prod');
        $test = ($myExecutionMode == 'test' || $myExecutionMode == 'devel' || $myExecutionMode == 'dev');
        $debug = ($myExecutionMode == 'debug');
        
        if($prod) self::$_executionMode = self::PROD_MODE;
        if($test) self::$_executionMode = self::TEST_MODE;
        if($debug) self::$_executionMode = self::DEBUG_MODE;
    }
    
    public function setRedis(array $params)
    {
        $this->redis = $params;
    }

    public function getRedis()
    {
        return $this->redis;
    }

    public static function getVerboseMode()
    {
        return self::$_verboseMode;
    }
    
    public static function setVerboseMode($set = false)
    {
        self::$_verboseMode = $set;
    }
    
    public static function getTransactionUse()
    {
        return self::$_useTransactions;
    }

    public static function useTransactions($set = true)
    {
        self::$_useTransactions = $set;
    }
    
    public static function isProd()
    {
        return self::$_executionMode == self::PROD_MODE;
    }

    public static function isTest()
    {
        return self::$_executionMode == self::TEST_MODE;
    }

    public static function isDebug()
    {
        return self::$_executionMode == self::DEBUG_MODE;
    }

    public static function authenticateByToken($token)
    {
        
        // On prend le token en cours
        if(is_string($token)) {
            // avec ce token on récupère l'utilisateur et un nouveau token
            $token = TAuthentication::getUserCredentialsByToken($token);
        }
        
        return $token;
    }
        
    private static function _requireMaster()
    {   
        $result = [];
        $dirname = 'master';
        $filename = $dirname . '.zip';

        \Phink\UI\TConsoleApplication::writeLine(file_exists($filename) ? 'TRUE' : 'FALSE');
        
        if(!file_exists($filename)) {
            \Phink\UI\TConsoleApplication::writeLine('Downloading Phink github master');
            $curl = new \Phink\Web\TCurl();
            $result = $curl->request('https://codeload.github.com/dpjb71/Phink/zip/master');
            file_put_contents($filename, $result->content);   
        }

        if(file_exists($filename)) {
            \Phink\UI\TConsoleApplication::writeLine('Deflating Phink master archive');
            $zip = new \Phink\Utils\TZip();
            $zip->deflat($filename);
            
        }
        
        if(file_exists($dirname)) {
            //$phinkDir = 'master' . DIRECTORY_SEPARATOR . 'Phink-master' . DIRECTORY_SEPARATOR. 'src' . DIRECTORY_SEPARATOR . 'phink';
            $phinkDir = '..' . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'phink';
            $tree = \Phink\TAutoloader::walkTree($phinkDir, ['php']);
            
        }
        
        $result = ['path' => $phinkDir, 'tree' => $tree];
        
        return (object)$result;
        
    }
    
    public function makePhar()
    {
        if(APP_IS_WEB) {
            throw new \Exception('Still cannot make a phar of a web application!');
        }
        ini_set('phar.readonly', 0);
        
         // the current directory must be src
        $srcRoot = $this->appDirectory;
        $buildRoot = $srcRoot . '..' . DIRECTORY_SEPARATOR . 'build' . DIRECTORY_SEPARATOR;
        $pharName = $this->_name . ".phar";
        
        if(file_exists($buildRoot . $pharName)) {
            unlink($buildRoot . $pharName);
        }

        $phar = new \Phar(
            $buildRoot . $pharName
            , \FilesystemIterator::CURRENT_AS_FILEINFO | \FilesystemIterator::KEY_AS_FILENAME
            , $pharName
        );
        
        // start buffering. Mandatory to modify stub.
        $phar->startBuffering();
        
        // Get the default stub. You can create your own if you have specific needs
        $defaultStub = $phar->createDefaultStub("app.php");
        

//        $phar["app.php"] = file_get_contents($srcRoot . "/app.php");
//        $phar["lib.php"] = file_get_contents($srcRoot . "/lib.php");
        $phar->addFile($srcRoot . "app.php", "app.php");
        $phar->addFile($srcRoot . "lib.php", "lib.php");
        
        $master = self::_requireMaster();
        $phink_builder = $srcRoot . '..' . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'phink_library.php';
        $phink_builder = \Phink\Utils\TFileUtils::relativePathToAbsolute($phink_builder);
        $phar->addFile($phink_builder, "phink_library.php");

        foreach($master->tree as $file) {
            $filename = $srcRoot . $master->path . $file;
            
            $filename = \Phink\Utils\TFileUtils::relativePathToAbsolute($filename);
            
            $info = pathinfo($filename, PATHINFO_BASENAME);
//            \Phink\UI\TConsoleApplication::writeLine(print_r($info, true));
            
            \Phink\UI\TConsoleApplication::writeLine("Adding %s as %s", $filename, $info);
            $phar->addFile($filename, $info);
            
//            $phar[$filename] = file_get_contents($master->path . DIRECTORY_SEPARATOR . $file);
        }
//        var_dump($phar->getStub());
        

        // Create a custom stub to add the shebang
        $execHeader = "#!/usr/bin/env php \n";
        if(PHP_OS == 'WINNT') {
            $execHeader = "@echo off\r\nphp.exe\r\n";
        }
        $stub = $execHeader . $defaultStub;

        // Add the stub
        $phar->setStub($stub);

        $phar->stopBuffering();        

        $buildRoot = $this->appDirectory . '..' . DIRECTORY_SEPARATOR . 'build' . DIRECTORY_SEPARATOR;
        $execname = $buildRoot . $this->_name;
        if(PHP_OS == 'WINNT') {
            $execname .= '.bat';
        }

        rename($buildRoot . $this->_name . '.phar', $execname);

        
    }
    
    public function displayTree($path)
    {
        $tree = \Phink\TAutoloader::walkTree($path);
        \Phink\UI\TConsoleApplication::writeLine($tree);
    }
    
}