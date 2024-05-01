<?php

describe('new facade', function() {
    it('works in the first place', function() {
        expect(sage())->toBeInstanceOf(SageDynamicFacade::class);

        ob_start();
        sage(123);
        $a = ob_get_clean();
        expect($a)->toContain('123');
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
    sage()
        ->saveOutputTo($var)
        ->displayRichExpanded()
        ->dump([1])
    ;

    it('puts the dump in the variable', fn() => expect($var)->toBeString()->not()->toBeEmpty());
    it('contains html', fn() => expect($var)->toStartWith('<script class="_sage-js">'));
    it('contains expanded node', fn() => expect($var)->toContain(' _sage-show'));

    ob_start();
    sage(123);
    $a = ob_get_clean();
    it('does not prevent future dumps from echoing', fn() => expect($a)->toContain('123'));

    ob_start();
    sage()->d(123);
    $b = ob_get_clean();
    // todo due to broken variable name detection
    //    it('is exactly the same as the shorthand d()', fn() => expect($a)->toEqual($b));
});

describe('displaySimpleHtml()', function() {
    ob_start();
    sage()->displaySimpleHtml(123);
    $a = ob_get_clean();

    it(
        'puts the dump in the variable and is plain html',
        fn() => expect($a)
            ->toStartWith('<pre class="_sage_plain">')
            ->toContain(123)
    );
});

describe('simplest()', function() {
    ob_start();
    sage()->displaySimplest(123);
    $a = ob_get_clean();

    it(
        'puts the dump in the variable and is plain html',
        fn() => expect($a)
            ->toStartWith('┌───')
            ->toContain(123)
    );

    ob_start();
    sage()->displayPlainText()->dump(123);
    $b = ob_get_clean();
    // todo due to broken variable name detection
    //    it('is exactly the same as the alias displayPlainText()', fn() => expect($a)->toEqual($b));
});

describe('settings tests', function() {
    sage()
        ->saveOutputTo($var)
        ->displayRichExpanded()
        ->dump([1])
    ;

    it('puts the dump in the variable', fn() => expect($var)->toBeString()->not()->toBeEmpty());
    it('contains html', fn() => expect($var)->toContain('<script'));
    it('contains expanded node', fn() => expect($var)->toContain(' _sage-show'));

    ob_start();
    sage(123);
    $a = ob_get_clean();
    it('does not prevent future dumps from echoing', fn() => expect($a)->toContain('123'));
});
