<?php

namespace Phink;

use Phink\JavaScript\PhinkBuilder;
use Phink\Utils\TZip;
use Phink\Web\TCurl;

class Setup
{

    private $_rewriteBase = '/';

    public static function create(): Setup
    {
        return new Setup();
    }

    public function __construct()
    {
        $this->_rewriteBase =  pathinfo($_SERVER['SCRIPT_NAME'], PATHINFO_DIRNAME) . DIRECTORY_SEPARATOR;
    }

    public function getRewriteBase(): string
    {
        return $this->_rewriteBase;
    }

    public function installPhinkJS(): bool
    {
        $zipname = 'phinkjs.zip';
        $curl = new TCurl();
        $result = $curl->request('https://github.com/CodePhoenixOrg/PhinkJS/archive/master.zip');
        file_put_contents('framework'  . DIRECTORY_SEPARATOR . $zipname, $result->content);
        $zip = new TZip();
        $zip->inflate($zipname, false, false, true);
        chdir('framework');
        $ok = rename('phinkjs-master', 'phinkjs');
        unlink($zipname);

        return $ok;
    }

    public function fixRewritBase(): bool
    {
        if (($htaccess = file_get_contents('.htaccess')) && file_exists('bootstrap.php')) {
            $htaccess = str_replace(PHP_EOL, ';', $htaccess);
            $text = strtolower($htaccess);

            $ps = strpos($text, 'rewritebase');
            if ($ps > -1) {
                $pe = strpos($htaccess, ' ', $ps);
                $rewriteBaseKey = substr($htaccess, $ps, $pe - $ps);
                $pe = strpos($htaccess, ';', $ps);
                $rewriteBaseEntry = substr($htaccess, $ps, $pe - $ps);

                $htaccess = str_replace($rewriteBaseEntry, $rewriteBaseKey . ' ' . $this->_rewriteBase, $htaccess);
                $htaccess = str_replace(';', PHP_EOL, $htaccess);

                $ok = false !== file_put_contents('.htaccess', $htaccess);
                $ok = $ok && false !== file_put_contents('..' . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'rewrite_base', $this->_rewriteBase);

                return $ok;
            }
        }
    }

    public function makeIndex(): bool
    {
        $index = <<<INDEX
        <?php
        include 'bootstrap.php';
        
        Phink\Web\TWebApplication::create();
    
        INDEX;

        return false !== file_put_contents('index.php', $index);
    }
}
