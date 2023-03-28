<?php

// THIS FILE IS NOT PART OF SAGE, IT IS ONLY USED FOR TEMPORARY TESTING

/* ****
php -S localhost:9876 playground.php
*** */

require 'vendor/autoload.php';

Sage::$returnOutput      = true;
Sage::$expandedByDefault = true;

echo sage(
        SAGE_DIR,
        SAGE_DIR . 'LICENCE',
        'non-existing',
        new SplFileInfo('LICENCE'),
        new SplFileInfo('non-existing'),
        new SplFileInfo(''),
        new SplFileInfo(SAGE_DIR . '.github/img/trace.png')
    )
    . Sage::enabled(Sage::MODE_PLAIN)
    . sage(
        SAGE_DIR,
        SAGE_DIR . 'LICENCE',
        'non-existing',
        new SplFileInfo('LICENCE'),
        new SplFileInfo('non-existing'),
        new SplFileInfo(''),
        new SplFileInfo(SAGE_DIR . '.github/img/trace.png')
    );

function getStructure()
{
    return new class('test') {
        public function __construct(
            public readonly string $deliveryDate,
        ) {
        }

    };
}

