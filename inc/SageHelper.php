<?php

/**
 * @internal
 */
class SageHelper
{
    public static function errorHandler($errno, $errstr, $errfile = null, $errline = null, $errcontext = null)
    {
        if (error_reporting() & $errno) {
            // This error is not suppressed by current error reporting settings
            // Convert the error into an ErrorException
            throw new ErrorException($errstr, $errno, 0, $errfile, $errline);
        }

        // Do not execute the PHP error handler
        return true;
    }

    public static function exceptionText(Exception $e)
    {
        return sprintf(
            '%s [ %s ]: %s ~ %s [ %d ]',
            get_class($e),
            $e->getCode(),
            strip_tags($e->getMessage()),
            str_replace(SAGE_DIR, 'SAGE_DIR/', $e->getFile()),
            $e->getLine()
        );
    }

    public static function getIdeLink($file, $line)
    {
        return str_replace(['%f', '%l'], [$file, $line], Sage::$fileLinkFormat);
    }

    /**
     * generic path display callback, can be configured in the settings; purpose is to show relevant path info and hide
     * as much of the path as possible.
     *
     * @param string $file
     *
     * @return string
     */
    public static function shortenPath($file)
    {
        $file = str_replace('\\', '/', $file);
        $shortenedName = $file;
        $replaced = false;
        if (is_array(Sage::$appRootDirs)) {
            foreach (Sage::$appRootDirs as $path => $replaceString) {
                if (empty($path)) {
                    continue;
                }

                $path = str_replace('\\', '/', $path);

                if (strpos($file, $path) === 0) {
                    $shortenedName = $replaceString.substr($file, strlen($path));
                    $replaced = true;
                    break;
                }
            }
        }

        # fallback to find common path with Sage dir
        if (! $replaced) {
            $pathParts = explode('/', str_replace('\\', '/', SAGE_DIR));
            $fileParts = explode('/', $file);
            $i = 0;
            foreach ($fileParts as $i => $filePart) {
                if (! isset($pathParts[$i]) || $pathParts[$i] !== $filePart) {
                    break;
                }
            }

            $shortenedName = ($i ? '.../' : '').implode('/', array_slice($fileParts, $i));
        }

        return $shortenedName;
    }
}