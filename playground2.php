<?php

require 'vendor/autoload.php';
saged('123');



// THIS FILE IS NOT PART OF SAGE, IT IS ONLY USED FOR TEMPORARY TESTING

/* ****
php -S localhost:9876 playground.php
*** */

//require 'vendor/autoload.php';

require __DIR__ . '/.github/examples/overview.php';

sage()->showEloquentQueries();
sage()->d();
sage()->dump();
sage()->displaySimpleHtml();
sage()->displaySimplest();
sage()->saveOutputTo(&$var);
sage()->trace();
sage()->simpleTrace();
sage()->traceWithoutArguments();
sage()->simpleTraceWithoutArguments();
sage()->tabularDump();
sage()->displayRichExpanded($var);
sage()->noDepthLimit($var);
sage()->setDepthLimit($var);
sage()->writeOutputToFile($var);
sage()->writeOutputToFileInCurrentDir($var);
sage()->richMode();
sage()->displayRichHtml();
sage()->plainMode();
sage()->cliMode();
sage()->textOnlyMode();
sage()->themeOriginal();
sage()->setDefaults()->themeOriginal();
sage()->setDefaults();
sage()->resetToDefaults();
sage()->themeOriginalLight();
sage()->themeLight();
sage()->themeSolarizedDark();
sage()->themeSolarizedLight();
sage()->disable();
sage()->enable();
sage()->saveState();
sage()->setEditor($editor);
sage()->addPathMapping($serverPath, $localPath);
sage()->shouldNotDisplayCalledFrom();
sage()->shouldDetectCliMode();
sage()->shouldColorCliOutput();
sage()->setCharEncoding($encoding, $encoding2);
sage()->addAlias($classOrFunction, $class);
sage()->addTracePathBlacklist($path);
sage()->clearTracePathBlacklist();
sage()->addKeysBlacklist($path);
sage()->clearKeysBlacklist();
sage()->addClassBlacklist($path);
sage()->clearClassBlacklist();
sage()->disableParser($parserClassName);
sage()->enableParser();
sage()->resetEnabledParsers();
