<?php

/**
 * @internal
 */
class SageParsersSplFileInfo implements SageParserInterface
{
    public function replacesAllOtherParsers()
    {
        return true;
    }

    public function parse(&$variable, $varData)
    {
        if (! SageHelper::php53orLater()
            || ! $variable instanceof SplFileInfo
            || $variable instanceof SplFileObject
        ) {
            return false;
        }

        return $this->run($variable, $varData, $variable);
    }

    /**
     * @param mixed            $variable
     * @param SageVariableData $varData
     * @param SplFileInfo      $fileInfo
     *
     * @return bool
     */
    protected function run(&$variable, $varData, $fileInfo)
    {
        $varData->value = '"' . SageHelper::esc($fileInfo->getPathname()) . '"';
        $varData->type  = get_class($fileInfo);

        if (! $fileInfo->getPathname() || ! $fileInfo->getRealPath()) {
            $varData->size = 'invalid path';

            return true;
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

            $varData->type = get_class($fileInfo);

            if ($type === 'Directory') {
                $name = 'Existing Directory';
                $size = iterator_count(
                        new FilesystemIterator($fileInfo->getRealPath(), FilesystemIterator::SKIP_DOTS)
                    ) . ' item(s)';
            } else {
                $name = "Existing {$type}";
                $size = $this->humanFilesize($fileInfo->getSize());
            }

            $extra = array();

            if ($fileInfo->getRealPath() !== $fileInfo->getPathname()) {
                $extra['realPath'] = $fileInfo->getRealPath();
            }

            if (SageHelper::isRichMode()) {
                $extra['flags'] = implode($flags);

                if ($fileInfo->getGroup() || $fileInfo->getOwner()) {
                    $extra['group&owner'] = $fileInfo->getGroup() . ':' . $fileInfo->getOwner();
                }

                $extra['created']  = date('Y-m-d H:i:s', $fileInfo->getCTime());
                $extra['modified'] = date('Y-m-d H:i:s', $fileInfo->getMTime());
                $extra['accessed'] = date('Y-m-d H:i:s', $fileInfo->getATime());

                if ($fileInfo->isLink()) {
                    $extra['link']       = 'true';
                    $extra['linkTarget'] = $fileInfo->getLinkTarget();
                }

                $varData->addTabToView($variable, $name . " [{$size}]", $extra);
            } else {
                if ($type === 'Directory') {
                    $extended = array('Existing Directory' => $fileInfo->getFilename());
                } else {
                    $extended = array("Existing {$type}" => $this->humanFilesize($fileInfo->getSize()));
                }

                $varData->extendedValue = array($name => $size) + $extra;
            }
        } catch (Exception $e) {
            return false;
        }

        return true;
    }

    private function humanFilesize($bytes)
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
