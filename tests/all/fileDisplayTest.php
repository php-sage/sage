<?php

test('display filesizes correctly', function() {
    Sage::enabled(Sage::MODE_RICH);

    // putenv('UPDATE_SNAPSHOTS=true');

    assertSageSnapshot(
        sage(
            'LICENCE',
            'non-existing',
            new SplFileInfo('LICENCE'),
            new SplFileInfo('non-existing'),
            new SplFileInfo(__DIR__ . '/../.github/')
        )
    );
});

test('display filesizes correctly - plain', function() {
    Sage::enabled(Sage::MODE_PLAIN);

    // putenv('UPDATE_SNAPSHOTS=true');

    assertSageSnapshot(
        sage(
            'LICENCE',
            'non-existing',
            new SplFileInfo('LICENCE'),
            new SplFileInfo('non-existing'),
            new SplFileInfo(__DIR__ . '/../.github/')
        )
    );
});
