<?php

describe('the basics', function() {
    it('dumps successfully', function() {
        $thisIsMyVariable = 123;
        ob_start();
        sage($thisIsMyVariable);
        $a = ob_get_clean();
        expect($a)
            ->toContain('123')
            ->toContain('$thisIsMyVariable')
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

        expect($a)
            ->toContain(
                <<<'OUT'
                0:  tests/all/basicsTest.php:18
                    Sage::trace()
                OUT
            )
            ->toContain('raveren_abcdefgh5')
        ;
    });
});
