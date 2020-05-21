<?php

/*
 * Copyright (C) 2019 David Blanchard
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

namespace Phink\Cache;

use Phink\Utils\TFileUtils;
use Phink\Core\TStaticObject;

class TCache extends TStaticObject
{

    public static function cacheFilenameFromView(string $viewName, bool $isFrameworkComponent = false): string
    {
        return REL_RUNTIME_DIR . ($isFrameworkComponent ? 'framework_' : '') . strtolower('controller_' . $viewName . CLASS_EXTENSION);
    }

    public static function cacheJsFilenameFromView(string $viewName, bool $isFrameworkComponent = false): string
    {
        return REL_RUNTIME_JS_DIR . ($isFrameworkComponent ? 'framework_' : '') . strtolower('javascript_' . $viewName . JS_EXTENSION);
    }

    public static function cacheCssFilenameFromView(string $viewName, bool $isFrameworkComponent = false): string
    {
        return  REL_RUNTIME_CSS_DIR . ($isFrameworkComponent ? 'framework_' : '') . strtolower('stylesheet_' . $viewName . CSS_EXTENSION);
    }

    public static function cacheFile($filename, $content): void
    {
        $filename = CACHE_DIR . $filename;
        file_put_contents($filename, $content);
    }

    public static function createRuntimeDirs(): bool
    {
        $result = false;
        $error_dir = [];

        try {
            $runtime_dir = dirname(RUNTIME_DIR . '_');
            if (!file_exists($runtime_dir)) {
                $ok = mkdir($runtime_dir, 0755, true);
                $result = $result || $ok;
            }
            if (!file_exists($runtime_dir)) {
                $error_dir[] = RUNTIME_DIR;
            }

            $runtime_js_dir = dirname(RUNTIME_JS_DIR . '_');
            if (!file_exists($runtime_js_dir)) {
                $ok = mkdir($runtime_js_dir, 0755, true);
                $result = $result || $ok;
            }
            if (!file_exists($runtime_js_dir)) {
                $error_dir[] = RUNTIME_JS_DIR;
            }

            $runtime_css_dir = dirname(RUNTIME_CSS_DIR . '_');
            if (!file_exists($runtime_css_dir)) {
                $ok = mkdir($runtime_css_dir, 0755, true);
                $result = $result || $ok;
            }
            if (!file_exists($runtime_css_dir)) {
                $error_dir[] = RUNTIME_CSS_DIR;
            }

            if (count($error_dir) > 0) {
                $result = false;

                $message = 'An error occured while creating ' . implode(', ', $error_dir);
                throw new \Exception($message, 0);
            }
        } catch (\Throwable $ex) {
            self::getLogger()->error($ex);
        }

        return $result;
    }

    public static function deleteRuntimeDirs(): bool
    {
        $result = false;
        $error_dir = [];

        try {

            if (file_exists(RUNTIME_DIR)) {
                $ok = TFileUtils::delTree(RUNTIME_DIR);
                $result = $result || $ok;
            } else {
                $error_dir[] = RUNTIME_DIR;
            }

            if (file_exists(RUNTIME_JS_DIR)) {
                $ok = TFileUtils::delTree(RUNTIME_JS_DIR);
                $result = $result || $ok;
            } else {
                $error_dir[] = RUNTIME_JS_DIR;
            }

            if (file_exists(RUNTIME_CSS_DIR)) {
                $ok = TFileUtils::delTree(RUNTIME_CSS_DIR);
                $result = $result || $ok;
            } else {
                $error_dir[] = RUNTIME_CSS_DIR;
            }

            if (count($error_dir) > 0) {
                $result = false;

                $message = 'An error occured while deleting ' . implode(', ', $error_dir);
                throw new \Exception($message, 0);
            }
        } catch (\Exception $ex) {
            self::getLogger()->error($ex);
        }

        return $result;
    }

    public static function clearRuntime(): bool
    {
        $result = false;
        try {
            $result = $result || self::deleteRuntimeDirs();
            $result = $result || self::createRuntimeDirs();
            $result = $result || self::deleteCacheDir();
            $result = $result || self::createCacheDir();

        } catch (\Throwable $ex) {
            self::writeException($ex);

            $result = false;
        }
        return $result;
    }

    public static function createCacheDir(): bool
    {
        $result = false;
        $error_dir = [];

        try {
            $cache_dir = dirname(CACHE_DIR . '_');
            if (!file_exists($cache_dir)) {
                $ok = mkdir($cache_dir, 0755, true);
                $result = $result || $ok;
            }
            if (!file_exists($cache_dir)) {
                $error_dir[] = CACHE_DIR;
            }

            if (count($error_dir) > 0) {
                $result = false;

                $message = 'An error occured while creating ' . implode(', ', $error_dir);
                throw new \Exception($message, 0);
            }
        } catch (\Throwable $ex) {
            self::getLogger()->error($ex);
        }

        return $result;
    }

    public static function deleteCacheDir(): bool
    {
        $result = false;
        $error_dir = [];

        try {

            if (file_exists(CACHE_DIR)) {
                $ok = TFileUtils::delTree(CACHE_DIR);
                $result = $result || $ok;
            } else {
                $error_dir[] = CACHE_DIR;
            }

            if (count($error_dir) > 0) {
                $result = false;

                $message = 'Permission denied while deleting ' . implode(', ', $error_dir);
                throw new \Exception($message, 0, $ex);
            }
        } catch (\Exception $ex) {
            self::getLogger()->error($ex);
        }

        return $result;
    }
}
