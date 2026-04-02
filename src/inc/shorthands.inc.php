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
 */

if (! function_exists('sage')) {
    /**
     * @see Sage::dump() if you pass any argument
     * @see Sage::settings() if not
     */
    function sage()
    {
        Sage::settings()->addDumpFunctionAlias(__FUNCTION__);

        $_ = func_get_args();
        if ($_) {
            return call_user_func_array(array('Sage', 'dump'), $_);
        }

        /** @noinspection PhpUndefinedMethodInspection internal method {@see SageInstance::__call()} */
        return Sage::settings()->resetRevert();
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
            return Sage::STATUS_ERROR;
        }

        Sage::settings()->addDumpFunctionAlias(__FUNCTION__);

        $_ = func_get_args();

        return call_user_func_array(array('Sage', 'dump'), $_);
    }
}

if (! function_exists('saged')) {
    /**
     * Alias of Sage::dump(); die;
     *
     * @return int|never [!!!] IMPORTANT: execution will halt after call to this function
     */
    function saged()
    {
        if (! Sage::enabled()) {
            return Sage::STATUS_ERROR;
        }

        Sage::settings()->addDumpFunctionAlias(__FUNCTION__);

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
            return Sage::STATUS_ERROR;
        }

        Sage::settings()->addDumpFunctionAlias(__FUNCTION__);

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
        if (! Sage::enabled()) {
            return Sage::STATUS_ERROR;
        }

        $restore = Sage::saveState();
        Sage::settings()->addDumpFunctionAlias(__FUNCTION__);
        if (Sage::enabled() === Sage::MODE_RICH) {
            Sage::enabled(Sage::MODE_PLAIN_HTML);
        } else {
            Sage::enabled(Sage::MODE_TEXT_ONLY);
        }

        $_    = func_get_args();
        $dump = call_user_func_array(array('Sage', 'dump'), $_);

        Sage::saveState($restore);

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
        $_ = func_get_args();

        return call_user_func_array('ssage', $_);
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
        $_ = func_get_args();
        call_user_func_array('ssage', $_);
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
        $_ = func_get_args();
        call_user_func_array('ssage', $_);
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
        return call_user_func_array('sage', func_get_args());
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
    function sageTrace()
    {
        if (! Sage::enabled()) {
            return Sage::STATUS_ERROR;
        }

        Sage::settings()->addDumpFunctionAlias(__FUNCTION__);

        return Sage::trace();
    }
}
