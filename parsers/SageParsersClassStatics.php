<?php

/**
 * @internal
 */
class SageParsersClassStatics extends SageParser
{
    private static $constsCache = array();

    protected static function parse(&$variable, $varData)
    {
        if (!SageHelper::isRichMode() || ! SageHelper::php53() || ! is_object($variable)) {
            return false;
        }

        $statics = array();
        $class = \get_class($variable);
        $reflection = new ReflectionClass($class);

        // first show static values
        foreach ($reflection->getProperties(ReflectionProperty::IS_STATIC) as $property) {
            $property->setAccessible(true);
            $_ = $property->getValue();
            $output = SageParser::process($_, '$'.$property->getName());

            if (method_exists($property, 'isInitialized') && ! $property->isInitialized()) {
                $output->type .= ' (uninitialized)';
            }

            if ($property->isPrivate()) {
                if (! method_exists($property, 'setAccessible')) {
                    break;
                }
                $property->setAccessible(true);
                $output->access = "private";
            } elseif ($property->isProtected()) {
                $property->setAccessible(true);
                $output->access = "protected";
            } else {
                $output->access = 'public';
            }

            $output->operator = '::';
            $statics[] = $output;
        }

        if (! isset(self::$constsCache[$class])) {
            $constants = [];

            if (method_exists($reflection, 'getReflectionConstants')) {
                foreach ($reflection->getReflectionConstants() as $constant) {
                    $val = $constant->getValue();
                    $output = SageParser::process($val, $constant->getName());
                    $output->access = '';
                    if (method_exists($constant, 'isFinal') && $constant->isFinal()) {
                        $output->access .= 'final ';
                    }
                    if ($constant->isPrivate()) {
                        $output->access .= 'private ';
                    }
                    if ($constant->isPrivate()) {
                        $output->access .= 'protected ';
                    }
                    $output->access .= 'const';
                    $output->operator = '::';

                    $constants[] = $output;
                }
            } else {
                foreach ($reflection->getConstants() as $name => $val) {
                    $output = SageParser::process($val, $name);
                    $output->access = 'const';
                    $output->operator = '::';

                    $constants[] = $output;
                }
            }

            self::$constsCache[$class] = $constants;
        }

        $statics = array_merge($statics, self::$constsCache[$class]);

        if (empty($statics)) {
            return false;
        }

        $varData->addTabToView(
            'Static class properties ('.count($statics).')',
            $statics
        );
    }
}