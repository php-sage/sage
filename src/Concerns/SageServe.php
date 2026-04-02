<?php

/** @internal */
class SageServe
{
    const DIR = 'sage-server';

    public static function isServing($getUrl = false)
    {
        $sageSettings = Sage::settings();

        if (
            $sageSettings->returnOutput
            || ($sageSettings->outputToFile && strpos($sageSettings->outputToFile, self::getFilesDir()) === 0)
        ) {
            return false;
        }

        $file = self::getServingStatusFile();
        if (file_exists($file) && filemtime($file) > (time() - 3)) {
            return $getUrl
                ? file_get_contents($file)
                : true;
        }

        return false;
    }

    public static function setSettings()
    {
        Sage::settings()->enabled      = Sage::MODE_RICH;
        Sage::settings()->outputToFile = self::getFilesDir() . uniqid('sage-', true);
    }

    public static function getFilesDir()
    {
        $baseDir = Sage::settings()->writeableDir;
        if (! $baseDir && function_exists('storage_path')) {
            Sage::settings()->writeableDir = $baseDir = storage_path();
        }

        // todo won't work without laravel
        return $baseDir . DIRECTORY_SEPARATOR . self::DIR . DIRECTORY_SEPARATOR;
    }

    public static function serve()
    {
        if (isset($_GET['_sage_get_new_content'])) {
            header('Content-Type: application/json');
            touch(self::getServingStatusFile());

            $dir = self::getFilesDir();

            foreach (scandir($dir) as $filename) {
                $fullPath = $dir . '/' . $filename;

                // Skip directories, this script, and hidden files
                if (
                    is_file($fullPath)
                    && $filename !== basename(__FILE__)
                    && substr($filename, 0, 1) !== '.'
                ) {
                    echo file_get_contents($fullPath);

                    // Delete the file
                    unlink($fullPath);
                }
            }

            exit;
        }

        if (ob_get_level() > 0) {
            ob_clean();
        }

        $directory = self::getFilesDir();
        if (! is_dir($directory) && ! mkdir($directory, 0755) && ! is_dir($directory)) {
            throw new RuntimeException(sprintf('Could not create directory "%s"', $directory));
        }
        file_put_contents(self::getServingStatusFile(), self::getCurrentUrl());

        echo file_get_contents(SAGE_DIR . 'inc/server.html');
        $d = new SageDecoratorsRich();
        echo $d->init();

        exit;
    }

    private static function getCurrentUrl()
    {
        $protocol = ((! empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || $_SERVER['SERVER_PORT'] == 443)
            ? 'https://'
            : 'http://';
        $host     = $_SERVER['HTTP_HOST'];
        $uri      = $_SERVER['REQUEST_URI'];

        return $protocol . $host . $uri;
    }

    private static function getServingStatusFile()
    {
        return self::getFilesDir() . '.serving';
    }
}
