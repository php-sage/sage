<?php

describe('trace', function() {
    $output = sage()
        ->richHtmlMode()
        ->returnOutput(true)
        ->trace()
    ;

    it('puts the dump in the variable', fn() => expect($output)->toBeString()->not()->toBeEmpty());
    it('contains html', fn() => expect($output)->toContain('<script'));
    it('contains trace', fn() => expect($output)->toContain(__FILE__));

    sage()->resetToDefaults();
    ob_start();
    sage(123);
    $a = ob_get_clean();
    it('does not prevent future dumps from echoing', fn() => expect($a)->toContain('123'));
});


