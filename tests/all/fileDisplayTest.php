<?php

test('display filesizes correctly', function() {
    Sage::enabled(Sage::MODE_RICH);

    // todo damn file creation time & docker! :)
    putenv('UPDATE_SNAPSHOTS=true');

    // set the file modification times to be static so the snapshot don't change every time :)
    // touch(SAGE_DIR, 1600000000, 1600000000);
    // touch(SAGE_DIR . 'LICENCE', 1600000000, 1600000000);
    // touch(SAGE_DIR . '.github/img/trace.png', 1600000000, 1600000000);

    assertSageSnapshot(
        sage(
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
        )
    );
});
