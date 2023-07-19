<?php

test('just random stuff', function() {
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
    assertSageSnapshot(
        sage()
    );
    assertSageSnapshot(
        sage(1)
    );
});
