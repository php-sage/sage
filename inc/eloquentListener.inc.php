<?php

$queryNumber = 0;
\DB::listen(function ($query) use (&$queryNumber) {
    // todo ideally we should detect where Sage::showEloquentQueries() was invoked from and display that.
    $state                   = Sage::saveState();
    Sage::$displayCalledFrom = false;

    $EloquentQuery = array('#' => $queryNumber++, 'sql' => $query->sql, 'bindings' => $query->bindings);
    Sage::dump($EloquentQuery);

    Sage::saveState($state);
});
