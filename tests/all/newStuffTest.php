<?php

describe('new facade', function() {
    it('works in the first place', function() {
        expect(sage())->toBeInstanceOf(SageDynamicFacade::class);
    });
});

describe('saving output to passed variable', function() {
    ob_start();
    sage()
        ->saveOutputTo($var)
        ->dump()
    ;
    $a = ob_get_clean();

    it('does not echo anything', fn() => expect($a)->toBeEmpty());

    it('puts the dump in the variable', fn() => expect($var)->toBeString()->not()->toBeEmpty());

    ob_start();
    Sage::dump();
    $a = ob_get_clean();

    it('does not prevent future dumps from echoing', fn() => expect($a)->not()->toBeEmpty());
});

describe('expandAll test', function() {
    ob_start();

    sage()
        ->saveOutputTo($var)
        ->expandAll()
        ->dump([1])
    ;

    it('puts the dump in the variable', fn() => expect($var)->toBeString()->not()->toBeEmpty());
    it('contains html', fn() => expect($var)->toContain('<script'));
});
