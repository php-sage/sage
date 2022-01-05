<?php

use Seld\PharUtils\Timestamps;
use Symfony\Component\Finder\Finder;

require_once __DIR__.'/../../vendor/autoload.php';
$pharPath = SAGE_DIR.'sage.phar';

if (is_file($pharPath)) {
    unlink($pharPath);
}

$rootPathLength = strlen(SAGE_DIR);

$phar = new Phar($pharPath);
$phar->setStub("<?php require 'phar://'.__FILE__.'/Sage.php'; __HALT_COMPILER();");
$phar->addFile(SAGE_DIR.'Sage.php', 'Sage.php');

$includeInPhar = [
    SAGE_DIR.'/decorators',
    SAGE_DIR.'/inc',
    SAGE_DIR.'/parsers',
    SAGE_DIR.'/view/compiled'
];
foreach (Finder::create()->files()->in($includeInPhar)->sortByName() as $file) {
    $local = substr($file, $rootPathLength);
    $phar->addFile($file, $local);
}

$phar = new Timestamps($pharPath);
$phar->updateTimestamps();
$phar->save($pharPath, Phar::SHA512);

saged("Success!!! /sage.phar updated!");
