<?php

/**
 * Use it by calling {@see Sage::settings()} or {@see sage()}
 *
 * @method self enabled($newValue) sets SageSettings::$enabled and returns $this
 * @method self theme($newValue) sets SageSettings::$theme and returns $this
 * @method self charEncodings($newValue) sets SageSettings::$charEncodings and returns $this
 * @method self aliases($newValue) sets SageSettings::$aliases and returns $this
 * @method self simplifyOutput($newValue) sets SageSettings::$simplifyOutput and returns $this
 * @method self displayCalledFrom($newValue) sets SageSettings::$displayCalledFrom and returns $this
 * @method self writeableDir($newValue) sets SageSettings::$writeableDir and returns $this
 * @method self expandedByDefault($newValue) sets SageSettings::$expandedByDefault and returns $this
 * @method self translations($newValue) sets SageSettings::$translations and returns $this
 * @method self ideLinkServerPath($newValue) sets SageSettings::$ideLinkServerPath and returns $this
 * @method self ideLinkLocalPath($newValue) sets SageSettings::$ideLinkLocalPath and returns $this
 * @method self returnOutput($newValue) sets SageSettings::$returnOutput and returns $this
 * @method self keysBlacklist($newValue) sets SageSettings::$keysBlacklist and returns $this
 * @method self traceBlacklist($newValue) sets SageSettings::$traceBlacklist and returns $this
 * @method self editor($newValue) sets SageSettings::$editor and returns $this
 * @method self cliDetectionEnabled($newValue) sets SageSettings::$cliDetectionEnabled and returns $this
 * @method self maxLevels($newValue) sets SageSettings::$maxLevels and returns $this
 * @method self cliColors($newValue) sets SageSettings::$cliColors and returns $this
 * @method self enabledParsers($newValue) sets SageSettings::$enabledParsers and returns $this
 * @method self classNameBlacklist($newValue) sets SageSettings::$classNameBlacklist and returns $this
 */
class SageInstance
{
    /** @var bool|string `true` means autodetect */
    public $enabled = true;

    /**
     * @var string theme for rich view
     *
     * Possible values:
     *             Sage::settings()->theme = Sage::THEME_ORIGINAL;
     *             Sage::settings()->theme = Sage::THEME_ORIGINAL_LIGHT;
     *             Sage::settings()->theme = Sage::THEME_LIGHT;
     *             Sage::settings()->theme = Sage::THEME_SOLARIZED;
     *             Sage::settings()->theme = Sage::THEME_SOLARIZED_DARK;
     */
    public $theme = Sage::THEME_ORIGINAL;

    /**
     * @var array possible alternative char encodings in order of probability
     */
    public $charEncodings = array(
        'UTF-8',
        'Windows-1252', // Western; includes iso-8859-1, replace this with windows-1251 if you use Russian
        'euc-jp'       // Japanese
    );

    /**
     * @var array Add new custom Sage wrapper names. Needed for nice backtraces, variable name detection and modifiers.
     * Use `sage()->addDumpFunctionAlias()` to add current method/function.
     *
     * [!] Use notation `Class::method` for methods.
     *
     * Example:
     * ```
     * function doom_dump($args)
     * {
     *     sage()->addDumpFunctionAlias();
     *     echo "DOOOM!";
     *     d(...func_get_args());
     * }
     * ```
     * @see SageDynamicFacade::addDumpFunctionAlias().
     */
    public $aliases = array();

    /**
     * @var bool there are multiple ways to direct sage to display "simpler" view than current mode (e.g. Rich -> PLain)
     * todo must be private
     */
    public $simplifyOutput = false;

    /**
     * @var bool whether to display where Sage was called from.
     */
    public $displayCalledFrom = true;

    /**
     * @var string Write output to this file instead of echoing it. If it ends in `.html` forces output in html mode.
     */
    public $outputToFile = false;

    /**
     * @var ?string Must be globally set, used for internal intercommunication (currently only Sage::serve() with
     * potential to store settings).
     */
    public $writeableDir = null;

    /**
     * @var bool draw rich output already expanded without having to click
     */
    public $expandedByDefault = false;

    // todo more strings
    public $translations = array(
        'key_blacklisted' => 'Redacted'
    );

    /**
     * @var string the full path (not URL) to your project folder on your remote dev server, be this Homestead, Docker,
     *             or in the cloud.
     */
    public $ideLinkServerPath = '';

    /**
     * @var string the full path (not URL) to your project on your local machine, the way your IDE or editor accesses
     *             the files.
     */
    public $ideLinkLocalPath = '';

    /**
     * @var bool|string Sage returns output instead of echo.
     *
     * If true, the return has scripts+css always included, if set to a string, only first time per "group".
     */
    public $returnOutput = false;

    /**
     * @var string[] - Blacklist of array/object keys to censor.
     */
    public $keysBlacklist = array();

    /**
     * @var string[] Patterns of filename paths. Keys don't matter, but you can use them to unset a particular entry.
     */
    public $traceBlacklist = array(
        'vendor'     => '#\/vendor\/#',
        'middleware' => '#\/Middleware\/#'
    );

    /**
     * @var string makes visible source file paths clickable to open your editor.
     *
     * Pre-defined values:
     *   'sublime'                => 'subl://open?url=file://%file&line=%line',
     *   'textmate'               => 'txmt://open?url=file://%file&line=%line',
     *   'emacs'                  => 'emacs://open?url=file://%file&line=%line',
     *   'macvim'                 => 'mvim://open/?url=file://%file&line=%line',
     *   'phpstorm'               => 'phpstorm://open?file=%file&line=%line',
     *   'phpstorm-remote'        => 'http://localhost:63342/api/file/%file:%line',
     *   'idea'                   => 'idea://open?file=%file&line=%line',
     *   'vscode'                 => 'vscode://file/%file:%line',
     *   'vscode-insiders'        => 'vscode-insiders://file/%file:%line',
     *   'vscode-remote'          => 'vscode://vscode-remote/%file:%line',
     *   'vscode-insiders-remote' => 'vscode-insiders://vscode-remote/%file:%line',
     *   'vscodium'               => 'vscodium://file/%file:%line',
     *   'atom'                   => 'atom://core/open/file?filename=%file&line=%line',
     *   'nova'                   => 'nova://core/open/file?filename=%file&line=%line',
     *   'netbeans'               => 'netbeans://open/?f=%file:%line',
     *   'xdebug'                 => 'xdebug://%file@%line'
     *
     * Or pass a custom string where %file should be replaced with full file path, %line with line number
     * to create a custom link. Set to null to disable linking.
     *
     * Example:
     *             // works with for PHPStorm and IDE Remote Control Plugin
     *             Sage::settings()->editor = 'phpstorm-remote';
     * Example:
     *             // same result as above, but explicitly defined
     *             Sage::settings()->editor = 'http://localhost:63342/api/file/f:%line';
     *
     * Default:
     *             ini_get('xdebug.file_link_format') ?: 'phpstorm-remote'
     *
     */
    public $editor = 'phpstorm-remote';

    /**
     * @var bool enable detection when running in command line and adjust output format accordingly.
     */
    public $cliDetectionEnabled = true;

    /**
     * @var int max array/object levels to go deep, set to zero/false to disable.
     */
    public $maxLevels = 7;
    /**
     * @var bool in addition to above setting, enable detection when Sage is run in *UNIX* command line.
     * Attempts to add coloring, but if seen as plain text, the color information is visible as gibberish
     */
    public $cliColors = true;
    /**
     * The ordering matters, each variable and its children are processed by each from top to bottom
     *
     * @var class-string<SageParser>[]
     */
    public $enabledParsers = array(
        // first all parsers that replacesAllOtherParsers() === true:
        'SageParsersSmarty'                    => true,
        'SageParsersSplFileInfo'               => true,
        'SageParsersClosure'                   => true,
        'SageParsersEloquent'                  => true,
        'SageParsersDateTime'                  => true,
        'SageParsersEloquentCollection'        => true,
        'SageParsersEloquentExpression'        => true,
        'SageParsersLaravelCollection'         => true,
        'SageParsersLaravelRequest'            => true,

        // now we run the blacklist
        'SageParsersBlacklist'                 => true,

        // all the rest
        // SageParsersXml'                       => true,
        'SageParsersTrace'                     => true,
        'SageParsersIterable'                  => true,
        'SageParsersPsrStreamInterface'        => true,
        'SageParsersSplObjectStorage'          => true,
        'SageParsersFilePath'                  => true,
        'SageParsersTimestamp'                 => true,
        'SageParsersClassStatics'              => true,
        'SageParsersColor'                     => true,
        'SageParsersJson'                      => true,
        'SageParsersXml'                       => true,
        'SageParsersClassName'                 => true,
        'SageParsersMicrotime'                 => true,
        'SageParsersInvisibleStringCharacters' => true,
    );
    public $classNameBlacklist = array(
        'illuminate' => '/^Illuminate(?!.*(?:Exception|Collection|Expression|Response))/',
        // 'symfony'    => '/^Symfony/'
    );

    private $revert = array();

    public function __call($name, $arguments)
    {
        if ($name === 'resetRevert') {
            $this->revert = array();

            return $this;
        }

        if (! count($arguments)) {
            throw new RuntimeException('Call to undefined method ' . get_class($this) . '->' . $name . '()');
        }

        // act as a setter for method chaining
        $this->saveForRevert($name);
        $this->$name = $arguments[0];

        return $this;
    }

    /**
     * Enables or disables Sage, and forces display mode. {@see Sage::enabled()}
     *
     * @param bool|Sage::MODE_*
     */
    public function setMode($newValue)
    {
        return $this->enabled($newValue);
    }

    public function addTraceBlacklist($blacklist)
    {
        if (! is_array($blacklist)) {
            $blacklist = array($blacklist);
        }

        $this->saveForRevert('traceBlacklist');

        foreach ($blacklist as $entry) {
            if (! in_array($entry, $this->traceBlacklist, true)) {
                $this->traceBlacklist[] = $entry;
            }
        }

        return $this;
    }

    public function addKeysBlacklist($blacklist)
    {
        if (! is_array($blacklist)) {
            $blacklist = array($blacklist);
        }

        $this->saveForRevert('keysBlacklist');

        foreach ($blacklist as $entry) {
            if (! in_array($entry, $this->keysBlacklist, true)) {
                $this->keysBlacklist[] = $entry;
            }
        }

        return $this;
    }

    public function addTranslations($blacklist)
    {
        if (! is_array($blacklist)) {
            $blacklist = array($blacklist);
        }

        $this->saveForRevert('translations');

        foreach ($blacklist as $key => $translation) {
            $this->translations[$key] = $translation;
        }

        return $this;
    }

    public function resetToDefaults()
    {
        Sage::saveState(new self());
    }

    /**
     * Will write output to file.
     *
     * @param null|false|string $fileOrDir by default it's sage.html in directory of the php file it was called from
     */
    public function outputToFile($fileOrDir = null)
    {
        $saveTo = $fileOrDir;
        if ($saveTo === null) {
            $debugBacktrace = SageHelper::php53orLater() ? debug_backtrace(2, 1) : debug_backtrace();
            $file           = isset($debugBacktrace[0]['file']) ? $debugBacktrace[0]['file'] : '';
            $dir            = dirname($file);
            $saveTo         = $dir;
        }

        if (is_dir($saveTo)) {
            $saveTo .= DIRECTORY_SEPARATOR . 'sage.html';
        }

        $this->saveForRevert('outputToFile');
        $this->outputToFile = $saveTo;

        return $this;
    }

    /**
     * @param null|bool $allNodesExpanded
     */
    public function richHtmlMode($allNodesExpanded = null)
    {
        $this->enabled(Sage::MODE_RICH);
        if ($allNodesExpanded !== null) {
            $this->expandedByDefault($allNodesExpanded);
        }

        return $this;
    }

    public function textMode()
    {
        return $this->enabled(Sage::MODE_TEXT_ONLY);
    }

    public function plainHtmlMode()
    {
        return $this->enabled(Sage::MODE_PLAIN_HTML);
    }

    public function cliColorsMode()
    {
        return $this->enabled(Sage::MODE_CLI);
    }

    public function dumpAndRevert($data = null)
    {
        $output = call_user_func_array(array('Sage', 'dump'), func_get_args());

        foreach ($this->revert as $key => $value) {
            $this->$key = $value;
        }
        $this->revert = array();

        return $output;
    }

    public function dump($data = null)
    {
        return call_user_func_array(array('Sage', 'dump'), func_get_args());
    }

    public function trace($onlyFilePaths = false)
    {
        if ($onlyFilePaths) {
            return Sage::dump(2);
        }

        return Sage::trace();
    }

    public function dd($data = null)
    {
        call_user_func_array(array($this, 'dump'), func_get_args());

        die;
    }

    public function serve()
    {
        SageServe::serve();
    }

    /**
     * All calls from within this function will be treated as coming from Sage, e.g. for parameter names and trace.
     *
     * [!] Use notation `Class::method` for methods.
     *
     * ```
     *    sage()->addDumpFunctionAlias();
     *    // is same as
     *    sage()->addDumpFunctionAlias(__CLASS__ . '::' . __FUNCTION__)
     * ```
     *
     * Don't pass any argument to use current method.
     */
    public function addDumpFunctionAlias($alias = null)
    {
        if ($alias === null) {
            $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);

            if (! isset($trace[1])) {
                throw new RuntimeException('Cannot be called from global scope.');
            }

            $caller = $trace[1];

            $alias = isset($caller['class'])
                ? $caller['class'] . '::' . $caller['method']
                : $caller['function'];
        }

        if (! in_array($alias, $this->aliases, true)) {
            $this->aliases[] = ltrim($alias, ':');
        }

        return $this;
    }

    /**
     * Laravel helper. Will dump all DB queries from this point forward.
     */
    public function showEloquentQueries()
    {
        // maintain PHP5.1+ compatibility
        if (! SageHelper::php53orLater()) {
            return $this;
        }

        Sage::dump('Started showing Eloquent queries');

        DB::listen(array('SageHelper', 'eloquentListener'));

        return $this;
    }

    private function saveForRevert($key)
    {
        if (! array_key_exists($key, $this->revert)) {
            $this->revert[$key] = $this->$key;
        }
    }
}
