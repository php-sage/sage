<?php

Sage::dump('Started showing Eloquent queries');

$queryNumber = 1;
DB::listen(function ($query) use (&$queryNumber) {
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

    $EloquentQuery = array(
        '#'             => $queryNumber++,
        'sql'           => PHP_EOL . SageSqlFormatter::format($query->sql, $query->bindings, false),
        'plain_sql'     => PHP_EOL . $query->sql,
        'bindings'      => $query->bindings,
        'called_from'   => $callee,
        'duration_in_s' => $query->time / 1000,
        'connection'    => $query->connectionName,
    );
    sage()
        ->displayCalledFrom(false)
        ->dumpAndRevert($EloquentQuery)
    ;
});
