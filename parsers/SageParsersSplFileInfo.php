<?php

/**
 * @internal
 * @noinspection AutoloadingIssuesInspection
 */
class SageParsersSplFileInfo extends SageParser
{
    protected static function parse(&$variable, $varData)
    {
        if (! SageHelper::php53orLater()
            || ! $variable instanceof SplFileInfo
            || $variable instanceof SplFileObject
        ) {
            return false;
        }

        try {
            $flags = array();
            $perms = $variable->getPerms();

            if (($perms & 0xC000) === 0xC000) {
                $type    = 'File socket';
                $flags[] = 's';
            } elseif (($perms & 0xA000) === 0xA000) {
                $type    = 'File symlink';
                $flags[] = 'l';
            } elseif (($perms & 0x8000) === 0x8000) {
                $type    = 'File';
                $flags[] = '-';
            } elseif (($perms & 0x6000) === 0x6000) {
                $type    = 'Block special file';
                $flags[] = 'b';
            } elseif (($perms & 0x4000) === 0x4000) {
                $type    = 'Directory';
                $flags[] = 'd';
            } elseif (($perms & 0x2000) === 0x2000) {
                $type    = 'Character special file';
                $flags[] = 'c';
            } elseif (($perms & 0x1000) === 0x1000) {
                $type    = 'FIFO pipe file';
                $flags[] = 'p';
            } else {
                $type    = 'Unknown file';
                $flags[] = 'u';
            }

            // owner
            $flags[] = (($perms & 0x0100) ? 'r' : '-');
            $flags[] = (($perms & 0x0080) ? 'w' : '-');
            $flags[] = (($perms & 0x0040) ? (($perms & 0x0800) ? 's' : 'x') : (($perms & 0x0800) ? 'S' : '-'));

            // group
            $flags[] = (($perms & 0x0020) ? 'r' : '-');
            $flags[] = (($perms & 0x0010) ? 'w' : '-');
            $flags[] = (($perms & 0x0008) ? (($perms & 0x0400) ? 's' : 'x') : (($perms & 0x0400) ? 'S' : '-'));

            // world
            $flags[] = (($perms & 0x0004) ? 'r' : '-');
            $flags[] = (($perms & 0x0002) ? 'w' : '-');
            $flags[] = (($perms & 0x0001) ? (($perms & 0x0200) ? 't' : 'x') : (($perms & 0x0200) ? 'T' : '-'));

            $size  = self::humanFilesize($variable->getSize());
            $flags = implode($flags);
            $path  = $variable->getRealPath();

            $varData->value = '"' . $variable->getBasename() . '"';
            $varData->size  = null;

            if (SageHelper::isRichMode()) {
                if ($type === 'Directory') {
                    $name = "Existing {$type}";
                } else {
                    $name = "Existing {$type} ($size)";
                }

                $varData->addTabToView($variable, $name, $flags . "    " . $path);
            } else {
                if ($type === 'Directory') {
                    $varData->extendedValue = array(
                        ' path' => $path,
                        ' type' => $type,
                        'flags' => $flags,
                    );
                } else {
                    $varData->extendedValue = array(
                        ' path' => $path,
                        ' type' => $type,
                        ' size' => $size,
                        'flags' => $flags,
                    );
                }
            }
        } catch (Exception $e) {
            return false;
        }
    }

    private static function humanFilesize($bytes)
    {
        if ($bytes < 10240) {
            return "{$bytes} bytes";
        }

        $units           = array('B', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB');
        $precisionByUnit = array(0, 1, 1, 2, 2, 3, 3, 4, 4);
        for ($order = 0; ($bytes / 1024) >= 0.9 && $order < count($units); $order++) {
            $bytes /= 1024;
        }

        return round($bytes, $precisionByUnit[$order]) . $units[$order];
    }
}
