<?php

/* 
 * Copyright (C) 2017 dpjb
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

class EggLib extends Phink\Core\TObject {

    /**
     * Defines the full directory tree of a Phink web application
     */
    private $directories = [
        'app',
        'app/business',
        'app/controllers',
        'app/models',
        'app/rest',
        'app/scripts',
        'app/templates',
        'app/views',
        'app/webservices',
        'cache',
        'cert',
        'config',
        'css',
        'data',
        'docker',
        'fonts',
        'logs',
        'media',
        'media/images',
        'runtime',
        'runtime/js',
        'themes',
        'tmp',
        'tools',
        'web',
        'web/css',
        'web/css/images',
        'web/fonts',
        'web/js',
        'web/js/runtime',
        'web/media',
        'web/media/images'
    ];
    

    protected $appDir = '' ;
    /**
     * Constructor
     */
    public function __construct(\Phink\UI\TConsoleApplication $parent)
    {
        $this->setParent($parent);
        $this->appDir = $parent->getApplicationDirectory();
    }
    

    /**
     * Deletes recursively a tree of directories containing files.
     * It is a workaround for rmdir which doesn't allow the deletion
     * of directories not empty.
     * 
     * @param string $path Top directory of the tree
     * @return boolean TRUE if deletion succeeds otherwise FALSE
     */
    private function _deltree($path)
    {
        $class_func = array(__CLASS__, __FUNCTION__);
        return is_file($path) ?
                @unlink($path) :
                array_map($class_func, glob($path.'/*')) == @rmdir($path);
    }    

    /**
     * Create the skeleton of the application
     */
    public function createTree () 
    {
        $this->parent->writeLine("Current directory %s", $currentDir);
        
        sort($this->directories);
        foreach ($this->directories as $directory){
            if(!file_exists($directory)) {
                $this->parent->writeLine("Creating directory %s", $directory);
                mkdir($directory, 0755, true);
            } else {
                $this->parent->writeLine("Directory %s already exist", $directory);
                
            }
        }
		
    }
    
    /**
     * Deletes recursively all known directories of the application 
     */
    public function deleteTree () 
    {
        $this->parent->writeLine("Current directory %s", $this->appDir);

        rsort($this->directories);
        foreach ($this->directories as $directory){
            $dir = $this->appDir . $directory;
            if(file_exists($dir)) {
                $this->parent->writeLine("Removing directory %s", $dir);
                $this->_deltree($dir);
                        
            } else {
                $this->parent->writeLine("Cannot find directory %s", $dir);
            }
        }
		
    }
    
}
 

