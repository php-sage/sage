<?php

describe('set defaults', function() {
    sage()
        ->saveOutputTo($a)
        ->setDefaults()
        ->displayRichHtml()
    ;

    sage(123);

    it('contains html', fn() => expect($a)->toStartWith('<script class="_sage-js">'));

    sage()->resetToDefaults();

    ob_start();
    Sage::dump();
    $z = ob_get_clean();
    it('again echoes plain output', fn() => expect($z)->toContain('┌───'));
});
