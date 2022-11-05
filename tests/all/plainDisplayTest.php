<?php

test('just random stuff', function() {
    Sage::enabled(Sage::MODE_PLAIN);
    Sage::$returnOutput = true;

    $example = [
        'foo' => 'far',
        'bar' => [
            'baz'  => 0,
            'buzz' => INF,
        ]
    ];
    assertSageSnapshot(
        sage(
            $example,
            microtime(),
            new SplFileInfo(__DIR__ . '/../../LICENCE')
        )
    );
});
