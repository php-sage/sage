<?php

describe('the basics', function() {
    it('dumps successfully', function() {
        $n = 123;
        ob_start();
        sage($n);
        $a = ob_get_clean();
        expect($a)
            ->toContain('123')//            ->toContain('$n') // todo fix names
        ;
    });

    it('shows trace successfully', function() {
        function raveren_abcdefgh5()
        {
            Sage::trace();
        }

        ob_start();
        $previously = Sage::enabled(Sage::MODE_TEXT_ONLY);
        raveren_abcdefgh5();
        Sage::enabled($previously);
        $a = ob_get_clean();

        $expected = <<<OUT
            ┌──────────────────────────────────────────────────────────────────────────────┐
            │                               Debug backtrace                                │
            └──────────────────────────────────────────────────────────────────────────────┘
            ────────────────────────────────────────────────────────────────────────────────
            0:  tests/all/basicsTest.php:17
                Sage::trace()
            ────────────────────────────────────────────────────────────────────────────────
            1:  tests/all/basicsTest.php:22
                raveren_abcdefgh5()
            OUT;

        expect($a)
            ->toStartWith($expected)
            ->toContain('raveren_abcdefgh5')
        ;
    });
});
