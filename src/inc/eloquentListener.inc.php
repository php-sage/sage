<?php

$queryNumber = 0;
\DB::listen(function ($query) use (&$queryNumber) {
    // todo ideally we should detect where Sage::showEloquentQueries() was invoked from and display that.
    $state                   = Sage::saveState();
    Sage::$displayCalledFrom = false;

    $callee = 'unknown';
    $trace  = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
    foreach ($trace as $step) {
        if (array_key_exists('file', $step) && strpos($step['file'], '/vendor/laravel/') === false) {
            $callee = $step['file'];
            if (array_key_exists('line', $step)) {
                $callee .= ':' . $step['line'];
            }
            break;
        }
    }

    $EloquentQuery = [
        '#'           => $queryNumber++,
        'sql'         => $query->sql,
        'bindings'    => $query->bindings,
        'called_from' => $callee,
    ];
    Sage::dump($EloquentQuery);

    Sage::saveState($state);
});
