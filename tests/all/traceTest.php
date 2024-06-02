<?php


// todo test closure parser, file parser


describe('trace', function() {
    sage()
        ->saveOutputTo($var)
        ->trace()
    ;

    dd($var);

    it('puts the dump in the variable', fn() => expect($var)->toBeString()->not()->toBeEmpty());
    it('contains html', fn() => expect($var)->toContain('<script'));
    it('contains trace', fn() => expect($var)->toContain(__FILE__));

    ob_start();
    sage(123);
    $a = ob_get_clean();
    it('does not prevent future dumps from echoing', fn() => expect($a)->toContain('123'));
});


