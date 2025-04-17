<?php

require_once 'SageSqlFormatter.php';

$queryNumber = 0;
DB::listen(function ($query) use (&$queryNumber) {
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

    $substituteBindings = function ($sql, $bindings) {
        foreach ($bindings as $binding) {
            if ($binding instanceof DateTime) {
                $bindings[] = $binding->format('Y-m-d H:i:s');
                continue;
            }

            if ($binding === null) {
                $binding = 'NULL';
            }

            $bindings[] = (string) $binding;
        }

        /** @noinspection PhpLanguageLevelInspection we are only including this file if Laravel is detected, so PHP53 support is unnecesary */
        return sprintf(str_replace('?', "'%s'", $sql), ...$bindings);
    };

    $EloquentQuery = array(
        '#'             => $queryNumber++,
        'sql'           => PHP_EOL
            . SageSqlFormatter::format($substituteBindings($query->sql, $query->bindings), false),
        'plain_sql'     => PHP_EOL . $query->sql,
        'bindings'      => $query->bindings,
        'called_from'   => $callee,
        'duration_in_s' => $query->time / 1000,
        'connection'    => $query->connectionName,
    );
    Sage::dump($EloquentQuery);

    Sage::saveState($state);
});
