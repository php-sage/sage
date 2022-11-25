# Sage - Insightful PHP debugging assistant ☯

At first glance **Sage** is just a pretty replacement
for **[var_dump()](http://php.net/manual/en/function.var-dump.php)**
and **[debug_backtrace()](http://php.net/manual/en/function.debug-backtrace.php)**.

However, it's much, *much* more.

---
For an overview of Sage's outstanding features jump to the [F.A.Q.](#faq)

## Installation

#### [Download the phar](https://github.com/php-sage/sage/raw/main/sage.phar) and simply

```php
<?php
require 'sage.phar';

sage('Hello, 🌎!');
```

#### Or, if your project uses `composer`

```bash
composer require php-sage/sage --dev
```

## Usage

```php
sage($GLOBALS, $_SERVER); // dump any number of parameters

saged($i); // alias for sage();die;

sage(1); // shortcut for dumping trace
```

![](.github/img/main-screenshot.png)


| Function  | Shorthand             |                   |
|-----------|-----------------------|-------------------|
| `sage`    | `s`                   | Dump              |
| `saged`   | `sd`                  | Dump & die        |
| `ssage`   | `ss`                  | Simple dump       |
| `ssaged`  | `ssd`                 | Simple dump & die |
| `sage(1)` | `s(1)`/`sd(1)`        | Debug backtrace   |

### Simple dump:

![simple mode example](.github/img/simple-mode.png)

### Debug backtrace:

![Trace view](.github/img/trace.png)

To output plain-text mode (NO STYLING) prefix with `~`, to return instead of echoing, prefix with `@`:

```php
~s($var); // outputs plain text
$output = @ss(); // returns output
```


----

# Customization options

Sage is designed with the utmost care to be as usable and useful out of the box, however there are several customization options available for advanced users.

### Where to store customization?

#### If you use the `phar` version it does not get simpler:

```php
require 'sage.phar';
Sage::$theme = Sage::THEME_LIGHT;
```

#### If using `composer` you have several options:

1. Create a separate PHP config file and ask composer to autoload it for you:

   Add this entry to the `autoload.files` configuration key in `composer.json`:

```json
"autoload": {
  /* ... */
  "files": [
    "config/sage.php" /* <--------------- this line */
  ]
},
```

2. Put settings inside of `php.ini`:

```ini
; change sage theme:
sage.theme = solarized-dark
; always display all dump levels, almost always crashes the browser:
sage.maxLevels = 0
; it's been 10 years, and phpstorm:// is still not working, Jetbrains, PLEASE!
sage.editor = phpstorm-remotecall
; disable Sage unless explicitly enabled
sage.enabled = 0 
```

3. Include the desired settings in your bootstrap process anywhere™.

# All available customization options

```php
Sage::$theme = Sage::THEME_ORIGINAL;
```

Currently available themes are:
  * `Sage::THEME_ORIGINAL`
  * `Sage::THEME_LIGHT`
  * `Sage::THEME_SOLARIZED`
  * `Sage::THEME_SOLARIZED_DARK`
  
---

```php
Sage::$editor = ini_get('xdebug.file_link_format');
```

Make visible source file paths clickable to open your editor. Available options are:
  * `'sublime'`
  * `'textmate'`
  * `'emacs'`
  * `'macvim'`
  * `'phpstorm'`
  * `'phpstorm-remotecall'` - this is the one I have been using for the past 8 years. Requires [Remote call](https://plugins.jetbrains.com/plugin/6027-remote-call) plugin.
  * `'idea'`
  * `'vscode'`
  * `'vscode-insiders'`
  * `'vscode-remote'`
  * `'vscode-insiders-remote'`
  * `'vscodium'`
  * `'atom'`
  * `'nova'`
  * `'netbeans'`
  * `'xdebug'`

Or pass a custom string where %f should be replaced with full file path, %l with line number to create a custom link. Set to null to disable linking.

---

```php
Sage::$displayCalledFrom = true;
```

Whether to display where Sage was called from

---
 ```php
Sage::$maxLevels = 7;
```
Max array/object levels to go deep, set to zero/false to disable

---

```php
Sage::$appRootDirs = [ $_SERVER['DOCUMENT_ROOT'] => 'ROOT' ];
```
Directories of your application that will be displayed instead of the full path. Keys are paths, values are replacement strings.

Use this if you need to hide the access path from output.

```php
        // Example (for Kohana framework (R.I.P.)):
        Sage::appRootDirs = [
             SYSPATH => 'SYSPATH',
             MODPATH => 'MODPATH',
             DOCROOT => 'DOCROOT',
        ];
```

---

```php
Sage::$expandedByDefault = false;
```
Draw rich output already expanded without having to click

---

```php
Sage::$cliDetection = true; 
```
Enable detection when running in command line and adjust output format accordingly.

---
```php
Sage::$cliColors = true;
```

In addition to above setting, enable detection when Sage is run in *UNIX* command line. Attempts to add coloring, but if opened as plain text, the color information is visible as gibberish.

---

```php
Sage::$charEncodings =  [
    'UTF-8',
    'Windows-1252', # Western; includes iso-8859-1, replace this with windows-1251 if you have Russian code
    'euc-jp',       # Japanese
]
```

Possible alternative char encodings in order of probability.

---
```php
Sage::$returnOutput = false;
```
Sage returns output instead of printing it.

---

```php
Sage::$aliases;
```
Add new custom Sage wrapper names. Optional, but needed for backtraces, variable name detection and modifiers to work properly. Accepts array or comma separated string. Use notation `Class::method` for methods.
```php
// example, returns text-only output
function MY_dump($args)
{
    Sage::enabled(Sage::MODE_TEXT_ONLY);
    Sage::$returnOutput = true; // this configuration will persist for ALL subsequent dumps BTW!
    return d(...func_get_args());
}
Sage::$aliases[] = 'my_dump'; // let Sage know about it. In lowercase please.
```

---
# 🧙 Advanced Tips & Tricks
> this section is under construction :)
```php
// we already saw:
sage($GLOBALS, $_SERVER); 
// you can also go shorter for the same result:
s($GLOBALS, $_SERVER);
// or you can go the verbose way, it's all equivalent:
Sage::dump($GLOBALS, $_SERVER); 


// ss() will display a more basic, javascript-free display (but with colors)
ss($GLOBALS, $_SERVER);

// prepending a tilde will make the output *even more basic* (rich->basic and basic->plain text)
~d($GLOBALS, $_SERVER); // more on modifiers below

// show a trace
Sage::trace();
s(1); // shorthand works too!
Sage::dump( debug_backtrace() ); // you can even pass a custom result from debug_trace and it will be recognized

// dump and die debugging
sd($GLOBALS, $_SERVER); // dd() might be taken by your framework
saged($GLOBALS, $_SERVER); // so this is an equivalent altenrative
ssd($GLOBALS, $_SERVER); // available for plain display too!

// this will disable Sage completely
Sage::enabled(false);
sd('Get off my lawn!'); // no effect
```

* Sage supports keyboard shortcuts! Just press <kbd>d</kbd> when viewing output and the rest is self-explanatory (p.s. vim-style `hjkl` works as well);
* Call `Sage::enabled(Sage::MODE_PLAIN);` to switch to a  simpler, js-free output.
* Call `Sage::enabled(Sage::MODE_TEXT_ONLY);` for pure-plain text output which you can save or pass around by first setting `Sage::$returnOutput = true;`
* Sage can provide a plain-text version of its output and does so automatically when invoked via PHP running in command line mode.

  ![](.github/img/cli-output.png)

* Double clicking the `[+]` sign in the output will expand/collapse ALL nodes; **triple clicking** a big block of text
  will select it all.
* Clicking the tiny arrow on the **right** of the output will open it in a separate window where you can keep it for comparison.
* Sage supports themes:

  ![](.github/img/theme-preview.png)

  For customization instructions read the section below.
* If a variable is an object, its classname can be clicked to open the class in your IDE.
* There are a couple of real-time modifiers you can use:
  * `~s($var)` this call will output in plain text format.
  * `+s($var)` will disregard depth level limits and output everything (careful, this can hang your browser on huge
    objects)
  * `!s($var)` will show uncollapsed rich output.
  * `-s($var)` will attempt to `ob_clean` the previous output - useful when Sage is obscured by already present HTML.

  [Here's a little bit](https://stackoverflow.com/a/69890023/179104) on how it works.


* Sage also includes a naïve profiler you may find handy. It's for determining relatively which code blocks take longer
  than others:

```php
Sage::dump( microtime() ); // just pass microtime()
sleep( 1 );
Sage::dump( microtime(), 'after sleep(1)' );
sleep( 2 );
sd( microtime(), 'final call, after sleep(2)' );
```

![](.github/img/profiling.png)

---

## F.A.Q.

### 💬 How is it different or better than [symfony/var-dumper](https://symfony.com/doc/current/components/var_dumper.html)?

* Visible **Variable name**
* Keyboard shortcuts. Type <kbd>d</kbd> and the rest is just self-explanatory (use arrows, space, tab, enter, you'll get it!).
* **Debug backtraces** with full insight of arguments, callee objects and more.
* Custom display for a lot of recognized types:
  ![custom types](.github/img/alternative-view.png)
* Has text-only, plain and rich views, has several visual themes - actually created by a pro designer.
* A huge amount of small usability enhancements - like the (clickable) **call trace** in the footer of each output.
* Supports convenience modifiers, for example `@sage($var);` will return instead of outputting, `-sage($var);` will `ob_clean` all output to be the only thing on page (see advanced section above for more).
* Compatibility! Fully works on **PHP 5.1 - 8.1+**!
* Code is way less complex - to read and contribute to.
* Sage came first - developed since [pre-2012](https://github.com/php-sage/sage/commit/3c49968cb912fb627c6650c4bfd4673bb1b44277). It inspired the now ubiquitous [dd](https://github.com/php-sage/sage/commit/fa6c8074ea1870bb5c6a080e94f7130e9a0f2fda#diff-2cdf3c423d47e373c75638c910674ec68c5aa434e11d4074037c91a543d9cb58R549) shorthand, pioneered a lot of the concepts in the tool niche. 

### 💬 What are the other dumpers out there

 * [Symfony/var-dumper](https://symfony.com/doc/current/components/var_dumper.html)
 * [yii\helpers\VarDumper](https://www.yiiframework.com/doc/api/2.0/yii-helpers-vardumper)
 * [Tracy](https://tracy.nette.org/)
 * [PHP Debug Bar](https://github.com/maximebf/php-debugbar)
 * [Kint](https://kint-php.github.io/kint/) - sage superseeds Kint.

### 💬 Why does Sage look so much like Kint? A.K.A. Why does this have so few stars?

Because it <b>is</b> Kint, and I am its author, however the project was [**forcibly taken over**](https://github.com/kint-php/kint/commit/1ea81f3add81b586756515673f8364f60feb86a3) from me by a malicious contributor!

Instead of fighting DMCA windmills, I chose to fork and rename the last **good** version and continue under a new name!

### 💬 How is `var_dump` - style debugging still relevant when we have Xdebug?

1. In practice, Xdebug is quite often very difficult and time-consuming to install and configure.
2. There's many use-cases where dump&die is just faster to bring up.
3. There is no way you can visualise a timeline of changed data with XDebug. For example, all values dumped from within a loop.
4. And there's more subtle use-cases, eg. if you stepped over something there's no way to go back, but with var-dumping the values are still there...

I use xdebug almost daily, by the way. Side by side with Sage.

---

### Contributing

#### 🎲 Prerequisites

* Install [Docker Compose](https://docs.docker.com/compose/install/#install-compose').
* If you're on **Windows** 10+ you need to use WSL2:
  1. Setup: `wsl --install`
  2. Set Ubuntu as your default wsl shell: `wsl --set-version Ubuntu 2`.
  3. All commands listed below must be run from inside wsl shell: `wsl`

Do your changes but before committing run

```bash
 docker compose run php composer build
```

To compile resources and build the phar file.

## Author

**Rokas Šleinius** ([Raveren](https://github.com/raveren))

Contributors: https://github.com/php-sage/sage/graphs/contributors

### License

Licensed under the MIT License

---

Hope you'll love using Sage as much as I love creating it!
