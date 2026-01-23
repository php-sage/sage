<?php

/**
 * stores not just the settings, but also the entire rest of the state - for example if we're asked
 * to write multiple files with output, each of these have to have the css
 *
 * @internal
 * use via {@see Sage::settings()}
 *
 * @method self enabled($newValue)
 * @method self theme($newValue)
 * @method self charEncodings($newValue)
 * @method self aliases($newValue)
 * @method self simplifyOutput($newValue)
 * @method self displayCalledFrom($newValue)
 * @method self outputToFile($newValue)
 * @method self writeableDir($newValue)
 * @method self expandedByDefault($newValue)
 * @method self translations($newValue)
 * @method self ideLinkServerPath($newValue)
 * @method self ideLinkLocalPath($newValue)
 * @method self returnOutput($newValue)
 * @method self keysBlacklist($newValue)
 * @method self traceBlacklist($newValue)
 * @method self editor($newValue)
 * @method self cliDetectionEnabled($newValue)
 * @method self maxLevels($newValue)
 * @method self cliColors($newValue)
 * @method self enabledParsers($newValue)
 * @method self classNameBlacklist($newValue)
 */
class SageSettings
{
    const SKIP = "\x07";

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
     *
     *            [!] Use notation `Class::method` for methods.
     *
     * Example:
     *            function doom_dump($args)
     *            {
     *                sage()->settings()->addAlias(__CLASS__ . '::' . __FUNCTION__);
     *                echo "DOOOM!";
     *                d(...func_get_args());
     *            }
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

    public function __call($name, $arguments)
    {
        // act as a setter for method chaining
        if (count($arguments)) {
            $this->$name = $arguments[0];

            return $this;
        }

        // also as a getter, whatever (static code analyzers WILL complain if invoked without args!)
        return $this->$name;
    }

    public function addAlias($alias)
    {
        if (func_num_args()) {
            if (! in_array($alias, $this->aliases, true)) {
                $this->aliases[] = $alias;
            }

            return $this;
        }

        return $this->aliases;
    }

    public function overrideTranslations($overrideTranslations = null)
    {
        if (func_num_args()) {
            foreach ($overrideTranslations as $k => $val) {
                $this->translations[$k] = $val;
            }

            return $this;
        }

        return $this->getTranslations();
    }
}
