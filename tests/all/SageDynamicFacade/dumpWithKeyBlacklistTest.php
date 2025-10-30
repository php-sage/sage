<?php

describe('dump with key blacklist test', function() {
    sage()
        ->saveOutputTo($variable)
        ->displaySimplest()
        ->dumpWithKeyBlacklist(array('trace'), new Exception())
    ;

    it('contains redacted trace', fn() => expect($variable)->toContain('private trace -> Skipped'));
});
