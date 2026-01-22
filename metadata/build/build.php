<?php

use Seld\PharUtils\Timestamps;
use Symfony\Component\Finder\Finder;

$sageRoot = __DIR__ . '/../../';
require_once $sageRoot . 'vendor/autoload.php';
$pharPath = $sageRoot . 'sage.phar';

if (is_file($pharPath)) {
    unlink($pharPath);
}

$rootPathLength = strlen($sageRoot);

$phar = new Phar($pharPath);
$phar->setStub("<?php require 'phar://'.__FILE__.'/Sage.php'; __HALT_COMPILER();");
$phar->addFile($sageRoot . 'Sage.php', 'Sage.php');

$includeInPhar = array(
    $sageRoot . 'src/Concerns',
    $sageRoot . 'src/DataStructure',
    $sageRoot . 'src/Decorators',
    $sageRoot . 'src/inc',
    $sageRoot . 'src/Parsers',
    $sageRoot . 'src/resources/compiled'
);
foreach (Finder::create()->files()->in($includeInPhar)->sortByName() as $file) {
    $local = substr($file, $rootPathLength);
    $phar->addFile($file, $local);
}

$phar = new Timestamps($pharPath);
$phar->updateTimestamps();
$phar->save($pharPath, Phar::SHA512);

saged('Success!!! JS+CSS compiled, /sage.phar updated, tests will be run now!');
