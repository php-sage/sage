<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once dirname(__FILE__) . '/../../Sage.php';

$file             = dirname(__FILE__) . "/../../sage.html";
Sage::$outputFile = $file;

require dirname(__FILE__) . '/overview.php';

Sage::$outputFile = null;

Sage::trace();
Sage::dump(PHP_VERSION . ' PHP version verified working! Open following file to view output:', $file);
