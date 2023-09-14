<?php

/*
 *    ███████╗██╗  ██╗ ██████╗ ██████╗ ████████╗██╗  ██╗ █████╗ ███╗   ██╗██████╗ ███████╗
 *    ██╔════╝██║  ██║██╔═══██╗██╔══██╗╚══██╔══╝██║  ██║██╔══██╗████╗  ██║██╔══██╗██╔════╝
 *    ███████╗███████║██║   ██║██████╔╝   ██║   ███████║███████║██╔██╗ ██║██║  ██║███████╗
 *    ╚════██║██╔══██║██║   ██║██╔══██╗   ██║   ██╔══██║██╔══██║██║╚██╗██║██║  ██║╚════██║
 *    ███████║██║  ██║╚██████╔╝██║  ██║   ██║   ██║  ██║██║  ██║██║ ╚████║██████╔╝███████║
 *    ╚══════╝╚═╝  ╚═╝ ╚═════╝ ╚═╝  ╚═╝   ╚═╝   ╚═╝  ╚═╝╚═╝  ╚═╝╚═╝  ╚═══╝╚═════╝ ╚══════╝
 *
 *      |-------------|-----------|--------------------------------------------------|
 *      | Function    | Shorthand |                                                  |
 *      |-------------|-----------|--------------------------------------------------|
 *      | `sage`      | `s`       | Dump (same as `\Sage::dump()`)                   |
 *      | `saged`     | `sd`      | Dump & die                                       |
 *      | `ssage`     | `ss`      | Simple dump                                      |
 *      | `ssaged`    | `ssd`     | Simple dump & die                                |
 *      | `sagetrace` | `s(1)`    | Debug backtrace  (same as `\Sage::trace()`)      |
 *      |  ---        | `s(2)`    | Backtrace without the arguments - just the paths |
 *      |-------------|-----------|--------------------------------------------------|
 *
 *            ███╗   ███╗ ██████╗ ██████╗ ██╗███████╗██╗███████╗██████╗ ███████╗
 *            ████╗ ████║██╔═══██╗██╔══██╗██║██╔════╝██║██╔════╝██╔══██╗██╔════╝
 *            ██╔████╔██║██║   ██║██║  ██║██║█████╗  ██║█████╗  ██████╔╝███████╗
 *            ██║╚██╔╝██║██║   ██║██║  ██║██║██╔══╝  ██║██╔══╝  ██╔══██╗╚════██║
 *            ██║ ╚═╝ ██║╚██████╔╝██████╔╝██║██║     ██║███████╗██║  ██║███████║
 *            ╚═╝     ╚═╝ ╚═════╝ ╚═════╝ ╚═╝╚═╝     ╚═╝╚══════╝╚═╝  ╚═╝╚══════╝
 *
 *                  |-------|----------------------------------------------|
 *                  |       | Example:    `+ saged('magic');`              |
 *                  |-------|----------------------------------------------|
 *                  | !     | Dump ignoring depth limits for large objects |
 *                  | print | Puts output into current DIR as sage.html    |
 *                  | ~     | Simplifies sage output (rich->html->plain)   |
 *                  | -     | Clean up any output before dumping           |
 *                  | +     | Expand all nodes (in rich view)              |
 *                  | @     | Return output instead of displaying it       |
 *
 */

if (! function_exists('sage')) {
    /**
     * Alias of Sage::dump()
     *
     * @return string|int
     *
     * @see Sage::dump()
     */
    function sage()
    {
        if (! Sage::enabled()) {
            return 5463;
        }

        Sage::$aliases[] = __FUNCTION__;

        $params = func_get_args();

        return call_user_func_array(array('Sage', 'dump'), $params);
    }
}

if (! function_exists('s')) {
    /**
     * Alias of Sage::dump()
     *
     * @return string|int
     *
     * @see Sage::dump()
     */
    function s()
    {
        if (! Sage::enabled()) {
            return 5463;
        }

        Sage::$aliases[] = __FUNCTION__;

        $params = func_get_args();

        return call_user_func_array(array('Sage', 'dump'), $params);
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

        Sage::$aliases[] = __FUNCTION__;

        $params = func_get_args();
        call_user_func_array(array('Sage', 'dump'), $params);
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

        Sage::$aliases[] = __FUNCTION__;

        $params = func_get_args();
        call_user_func_array(array('Sage', 'dump'), $params);
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
        if (! Sage::enabled()) {
            return 5463;
        }

        $simplify              = Sage::$simplifyDisplay;
        Sage::$simplifyDisplay = true;
        Sage::$aliases[]       = __FUNCTION__;

        $params = func_get_args();
        $dump                  = call_user_func_array(array('Sage', 'dump'), $params);
        Sage::$simplifyDisplay = $simplify;

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
        if (! Sage::enabled()) {
            return 5463;
        }

        $simplify              = Sage::$simplifyDisplay;
        Sage::$simplifyDisplay = true;
        Sage::$aliases[]       = __FUNCTION__;

        $params = func_get_args();
        $dump                  = call_user_func_array(array('Sage', 'dump'), $params);
        Sage::$simplifyDisplay = $simplify;

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
        if (! Sage::enabled()) {
            return 5463;
        }

        Sage::$simplifyDisplay = true;
        Sage::$aliases[]       = __FUNCTION__;
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
        if (! Sage::enabled()) {
            return 5463;
        }

        Sage::$simplifyDisplay = true;
        Sage::$aliases[]       = __FUNCTION__;
        $params = func_get_args();
        call_user_func_array(array('Sage', 'dump'), $params);
        die;
    }
}

if (! function_exists('d')) {
    /**
     * Alias of Sage::dump()
     *
     * Same as sage(), here just to allow drop-in replacement for Kint.
     *
     * @return string|int @see Sage::dump()
     */
    function d()
    {
        if (! Sage::enabled()) {
            return 5463;
        }

        Sage::$aliases[] = __FUNCTION__;

        $params = func_get_args();

        return call_user_func_array(array('Sage', 'dump'), $params);
    }
}

if (! function_exists('sagetrace')) {
    /**
     * Alias of Sage::dump()
     *
     * Same as sage(), here just to allow drop-in replacement for Kint.
     *
     * @return string|int @see Sage::dump()
     */
    function sagetrace()
    {
        if (! Sage::enabled()) {
            return 5463;
        }

        Sage::$aliases[] = __FUNCTION__;

        return Sage::trace();
    }
}
