<?php

namespace Sulu\Component\Maintenance;

use Symfony\Component\Console\Output\OutputInterface;

/**
 * Interface to be implemented by classes which maintain the
 * data in the databases (PHPCR or conventional).
 */
interface MaintainerInterface
{
    /**
     * Perform the maintenance task
     *
     * @param OutputInterface $output
     */
    public function maintain(OutputInterface $output);

    /**
     * Return the name for this matinainer
     *
     * @return string
     */
    public function getName();

}
