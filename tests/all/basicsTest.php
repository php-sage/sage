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
        raveren_abcdefgh5();
        $a = ob_get_clean();
        expect($a)
            ->toContain('TRACE')
            ->toContain('raveren_abcdefgh5')
        ;
    });
});
