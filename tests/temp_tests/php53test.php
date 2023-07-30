<?php

// THIS FILE IS NOT PART OF SAGE, IT IS ONLY USED FOR TEMPORARY TESTING

/* ****
php -S localhost:9876 playground.php
*** */

//require 'vendor/autoload.php';
require_once __DIR__ . '/../../sage.phar';

Sage::$outputFile = __DIR__ . '/../../sage.html';


require __DIR__ . '/../../.github/examples/overview.php';


