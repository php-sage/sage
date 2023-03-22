<?php

test('just random stuff', function() {
    Sage::enabled(Sage::MODE_TEXT_ONLY);
    Sage::$returnOutput = true;

    // putenv('UPDATE_SNAPSHOTS=true');

    $example = [
        'foo' => 'far',
        'bar' => [
            'baz'  => 0,
            'buzz' => INF,
        ]
    ];
    assertSageSnapshot(
        sage(
            $example
        )
    );
});
