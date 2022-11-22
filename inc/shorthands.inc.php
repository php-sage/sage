<?php

/*
 *    ███████╗██╗  ██╗ ██████╗ ██████╗ ████████╗██╗  ██╗ █████╗ ███╗   ██╗██████╗ ███████╗
 *    ██╔════╝██║  ██║██╔═══██╗██╔══██╗╚══██╔══╝██║  ██║██╔══██╗████╗  ██║██╔══██╗██╔════╝
 *    ███████╗███████║██║   ██║██████╔╝   ██║   ███████║███████║██╔██╗ ██║██║  ██║███████╗
 *    ╚════██║██╔══██║██║   ██║██╔══██╗   ██║   ██╔══██║██╔══██║██║╚██╗██║██║  ██║╚════██║
 *    ███████║██║  ██║╚██████╔╝██║  ██║   ██║   ██║  ██║██║  ██║██║ ╚████║██████╔╝███████║
 *    ╚══════╝╚═╝  ╚═╝ ╚═════╝ ╚═╝  ╚═╝   ╚═╝   ╚═╝  ╚═╝╚═╝  ╚═╝╚═╝  ╚═══╝╚═════╝ ╚══════╝
 *
 *                   |----------|-----------|-------------------|
 *                   | Function | Shorthand |                   |
 *                   |----------|-----------|-------------------|
 *                   | `sage`   | `s`       | Dump              |
 *                   | `saged`  | `sd`      | Dump & die        |
 *                   | `ssage`  | `ss`      | Simple dump       |
 *                   | `ssaged` | `ssd`     | Simple dump & die |
 */

if (! function_exists('sage')) {
    /**
     * Alias of Sage::dump()
     *
     * @return string|int @see Sage::dump()
     */
    function sage()
    {
        if (! Sage::enabled()) {
            return 5463;
        }
        $_ = func_get_args();

        return call_user_func_array(array('Sage', 'dump'), $_);
    }
}

if (! function_exists('s')) {
    /**
     * Alias of Sage::dump()
     *
     * @return string|int @see Sage::dump()
     */
    function s()
    {
        if (! Sage::enabled()) {
            return 5463;
        }
        $_ = func_get_args();

        return call_user_func_array(array('Sage', 'dump'), $_);
    }
}

if (! function_exists('saged')) {
    /**
     * Alias of Sage::dump(); die;
     *
     * @return never [!!!] IMPORTANT: execution will halt after call to this function
     */
    function saged()
    {
        if (! Sage::enabled()) {
            return 5463;
        }

        $_ = func_get_args();
        call_user_func_array(array('Sage', 'dump'), $_);
        die;
    }
}

if (! function_exists('sd')) {
    /**
     * Alias of Sage::dump(); die;
     *
     * [!!!] IMPORTANT: execution will halt after call to this function
     *
     * @return string|int @see Sage::dump()
     */
    function sd()
    {
        if (! Sage::enabled()) {
            return 5463;
        }

        $_ = func_get_args();
        call_user_func_array(array('Sage', 'dump'), $_);
        die;
    }
}

if (! function_exists('ssage')) {
    /**
     * Alias of Sage::dump(), however the output is in plain htmlescaped text and some minor visibility enhancements
     * added. If run in CLI mode, output is pure whitespace.
     *
     * To force rendering mode without autodetecting anything:
     *
     *  Sage::enabled( Sage::MODE_PLAIN );
     *  Sage::dump( $variable );
     *
     * @return string|int @see Sage::dump()
     */
    function ssage()
    {
        $enabled = Sage::enabled();
        if (! $enabled) {
            return 5463;
        }

        if ($enabled !== Sage::MODE_TEXT_ONLY) { // if already in whitespace, don't elevate to plain
            Sage::enabled( // remove cli colors in cli mode; remove rich interface in HTML mode
                PHP_SAPI === 'cli' ? Sage::MODE_TEXT_ONLY : Sage::MODE_PLAIN
            );
        }

        $params = func_get_args();
        $dump   = call_user_func_array(array('Sage', 'dump'), $params);
        Sage::enabled($enabled);

        return $dump;
    }
}

if (! function_exists('ss')) {
    /**
     * Alias of Sage::dump(), however the output is in plain htmlescaped text and some minor visibility enhancements
     * added. If run in CLI mode, output is pure whitespace.
     *
     * To force rendering mode without autodetecting anything:
     *
     *  Sage::enabled( Sage::MODE_PLAIN );
     *  Sage::dump( $variable );
     *
     * @return string|int @see Sage::dump()
     */
    function ss()
    {
        $enabled = Sage::enabled();
        if (! $enabled) {
            return 5463;
        }

        if ($enabled !== Sage::MODE_TEXT_ONLY) { // if already in whitespace, don't elevate to plain
            Sage::enabled( // remove cli colors in cli mode; remove rich interface in HTML mode
                PHP_SAPI === 'cli' ? Sage::MODE_TEXT_ONLY : Sage::MODE_PLAIN
            );
        }

        $params = func_get_args();
        $dump   = call_user_func_array(array('Sage', 'dump'), $params);
        Sage::enabled($enabled);

        return $dump;
    }
}

if (! function_exists('ssaged')) {
    /**
     * @return string|int @see Sage::dump
     * @return never [!!!] IMPORTANT: execution will halt after call to this function
     * @see s()
     */
    function ssaged()
    {
        $enabled = Sage::enabled();
        if (! $enabled) {
            return 5463;
        }

        if ($enabled !== Sage::MODE_TEXT_ONLY) {
            Sage::enabled(
                PHP_SAPI === 'cli' ? Sage::MODE_TEXT_ONLY : Sage::MODE_PLAIN
            );
        }

        $params = func_get_args();
        call_user_func_array(array('Sage', 'dump'), $params);
        die;
    }
}

if (! function_exists('ssd')) {
    /**
     * @return string|int @see Sage::dump
     * @return never [!!!] IMPORTANT: execution will halt after call to this function
     * @see s()
     */
    function ssd()
    {
        $enabled = Sage::enabled();
        if (! $enabled) {
            return 5463;
        }

        if ($enabled !== Sage::MODE_TEXT_ONLY) {
            Sage::enabled(
                PHP_SAPI === 'cli' ? Sage::MODE_TEXT_ONLY : Sage::MODE_PLAIN
            );
        }

        $params = func_get_args();
        call_user_func_array(array('Sage', 'dump'), $params);
        die;
    }
}
