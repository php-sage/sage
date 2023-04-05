<?php

use function Spatie\Snapshots\assertMatchesHtmlSnapshot;

test('example', function() {
    Sage::enabled(Sage::MODE_RICH);
    Sage::$returnOutput = true;

    $dom = new DOMDocument();

    $dom->loadHTML(
        <<<HTML
<html>
    <body>
        <div>
            <p id="id1">first span</p>
            <p id="id2" title="Fancy span">second span</p>
            <p id="id3">third span</p>        
        </div>
    </body>
</html>
HTML
    );

    $xpath = new DOMXPath($dom);

    $div = $xpath->query('//div')->item(0);

    assertMatchesHtmlSnapshot(sage($div));

    //    Sage::$theme = Sage::THEME_SOLARIZED_DARK;
    //
    //    assertMatchesHtmlSnapshot(sage($xpath->query('//p')));
    //
    //    assertMatchesHtmlSnapshot(sage($xpath->query("//*[@id='id2']/@title")->item(0)));
});
