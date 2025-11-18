<?php

/** @internal {@see Sage::$enabledParsers} to enable/disable */
class SageParsersPsrStreamInterface implements SageCustomParserInterface
{
    public function replacesAllOtherParsers()
    {
        return false;
    }

    public function parse(&$variable)
    {
        if (! is_a($variable, '\Psr\Http\Message\StreamInterface')) {
            return null;
        }

        $result = new SageParsedVariable();
        try {
            $result->addTabView__Dump($variable->getContents(), 'Stream contents');
        } catch (Throwable $e) {
        } catch (Exception $e) {
        }

        return $result;
    }
}
