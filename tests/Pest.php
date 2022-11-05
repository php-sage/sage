<?php

uses()
    ->beforeEach(function() {
        Sage::$returnOutput      = true;
        Sage::$expandedByDefault = true;
    })
    ->in('all')
;

function assertSageSnapshot($sageOutput)
{
    Spatie\Snapshots\assertMatchesHtmlSnapshot(
        '<meta charset="utf-8">' . $sageOutput
    );
}
