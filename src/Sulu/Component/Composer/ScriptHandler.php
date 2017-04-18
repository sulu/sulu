<?php

namespace Sulu\Component\Composer;

use Composer\Script\Event;

class ScriptHandler
{
    /**
     * Asks if the new directory structure should be used, installs the structure if needed.
     *
     * @param CommandEvent $event
     */
    public static function copyDefaultTemplates(Event $event)
    {
        $io = $event->getIO();
        if (false === $io->askConfirmation('<question>Would you like to install the default templates? [Y/n]</> ', true)) {
            return;
        }

        $dirs = [
            getcwd() . '/app/Resources/pages',
            getcwd() . '/app/Resources/snippets',
            getcwd() . '/app/Resources/webspaces',
        ];

        foreach ($dirs as $dir) {
            $iterator = new \DirectoryIterator($dir);

            foreach ($iterator as $file) {
                if (false === $file->isFile()) {
                    continue;
                }

                if ($file->getExtension() != 'dist') {
                    continue;
                }

                $destFilename = substr($file->getPathname(), 0, -5);

                if (file_exists($destFilename)) {
                    $confirmed = $io->askConfirmation(sprintf('<question>File "%s" already exists, overwrite? [y/N]</> ', $destFilename), false);

                    if (false === $confirmed) {
                        $event->getIO()->write(sprintf(' <info>[ ]</> %s', $destFilename));
                        continue;
                    }
                }
                copy($file->getPathname(), $destFilename);
                $event->getIO()->write(sprintf(' <info>[+]</> %s', $destFilename));
            }
        }

    }
}
