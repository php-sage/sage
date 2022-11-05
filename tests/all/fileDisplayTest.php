<?php

test('display filesizes correctly', function() {
    Sage::enabled(Sage::MODE_RICH);


    assertSageSnapshot(
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

    assertSageSnapshot(
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
