<?php

use function Spatie\Snapshots\assertMatchesHtmlSnapshot;
use function Spatie\Snapshots\assertMatchesTextSnapshot;

test('display filesizes correctly', function() {
    Sage::enabled(Sage::MODE_RICH);
    Sage::$returnOutput      = true;
    Sage::$expandedByDefault = true;

    assertMatchesHtmlSnapshot(
        sage(
            'LICENCE',
            __FILE__,
            getcwd(),
            __DIR__,
            __DIR__ . '/../.github/img/trace.png',
            new SplFileInfo('LICENCE'),
            new SplFileInfo(__FILE__),
            new SplFileInfo(__DIR__),
            new SplFileInfo(__DIR__ . '/../.github/img/trace.png')
        )
    );
});


test('display filesizes correctly - plain', function() {
    Sage::enabled(Sage::MODE_PLAIN);
    Sage::$returnOutput      = true;

    assertMatchesTextSnapshot(
        sage(
            'LICENCE',
            __FILE__,
            getcwd(),
            __DIR__,
            __DIR__ . '/../.github/img/trace.png',
            new SplFileInfo('LICENCE'),
            new SplFileInfo(__FILE__),
            new SplFileInfo(__DIR__),
            new SplFileInfo(__DIR__ . '/../.github/img/trace.png')
        )
    );
});
