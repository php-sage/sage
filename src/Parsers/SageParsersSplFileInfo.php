<?php

/** @internal {@see Sage::$enabledParsers} to enable/disable */
class SageParsersSplFileInfo implements SageCustomParserInterface
{
    public function replacesAllOtherParsers()
    {
        return true;
    }

    public function parse(&$variable)
    {
        if (
            ! SageHelper::php53orLater()
            || ! $variable instanceof SplFileInfo
            || $variable instanceof SplFileObject
        ) {
            return null;
        }

        $result        = new SageParsedVariable();
        $result->type  = get_class($variable);
        $result->value = new SageHtmlable('"' . SageHelper::esc($variable->getPathname()) . '"');

        return self::inspect($variable, $result);
    }

    /**
     * @param SplFileInfo $fileInfo
     * @param SageParsedVariable $result
     *
     * @return SageParsedVariable
     */
    public static function inspect($fileInfo, $result)
    {
        if (! $fileInfo->getPathname() || ! $fileInfo->getRealPath()) {
            $result->size = 'invalid path';

            return $result;
        }

        try {
            $flags = array();
            $perms = $fileInfo->getPerms();

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

            if ($type === 'Directory') {
                $name = 'Existing Directory';
                $size = iterator_count(new FilesystemIterator($fileInfo->getRealPath(), FilesystemIterator::SKIP_DOTS))
                    . ' item(s)';
            } else {
                $name = "Existing {$type}";
                $size = self::humanFilesize($fileInfo->getSize());
            }

            if (SageHelper::isRichMode()) {
                $tab = new SageParsedVariableContents(
                    SageParsedVariableContents::PLAIN_TEXT_ROWS,
                    $name . " [{$size}]"
                );

                if ($fileInfo->getRealPath() !== $fileInfo->getPathname()) {
                    $tab->addRow($fileInfo->getRealPath(), 'realPath');
                }

                $tab->addRow(implode($flags), 'flags');

                if ($fileInfo->getGroup() || $fileInfo->getOwner()) {
                    $tab->addRow($fileInfo->getGroup() . ':' . $fileInfo->getOwner(), 'group&owner');
                }

                $tab->addRow(date('Y-m-d H:i:s', $fileInfo->getCTime()), 'created');
                $tab->addRow(date('Y-m-d H:i:s', $fileInfo->getMTime()), 'modified');
                $tab->addRow(date('Y-m-d H:i:s', $fileInfo->getATime()), 'accessed');

                if ($fileInfo->isLink()) {
                    $tab->addRow('true', 'link');
                    $tab->addRow($fileInfo->getLinkTarget(), 'linkTarget');
                }
                $tab->addRow(SageHelper::ideLink($fileInfo->getRealPath(), 0), 'IDE link');

                // todo add file preview for text files..?

                $result->addExtended($tab);
            } else {
                $tab = new SageParsedVariableContents(SageParsedVariableContents::PLAIN_TEXT_ROWS);
                $tab->addRow($size, $name);

                $result->addExtended($tab);
            }
        } catch (Exception $e) {
            return null;
        }

        return $result;
    }

    private static function humanFilesize($bytes)
    {
        $sizeInBytes = $bytes;
        if ($bytes < 10240) {
            return "{$bytes} bytes";
        }

        $units           = array('B', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB');
        $precisionByUnit = array(0, 1, 1, 2, 2, 3, 3, 4, 4);
        for ($order = 0; ($bytes / 1024) >= 0.9 && $order < count($units); $order++) {
            $bytes /= 1024;
        }

        return $sizeInBytes . ' bytes (' . round($bytes, $precisionByUnit[$order]) . $units[$order] . ')';
    }
}
