<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\CoreBundle\Build;

use Doctrine\Bundle\PHPCRBundle\ManagerRegistry;
use Jackalope\Session;
use PHPCR\RepositoryException;

/**
 * Builder for initializing PHPCR.
 */
class PhpcrBuilder extends SuluBuilder
{
    /**
     * {@inheritDoc}
     */
    public function getName()
    {
        return 'phpcr';
    }

    /**
     * {@inheritDoc}
     */
    public function getDependencies()
    {
        return array('database');
    }

    /**
     * {@inheritDoc}
     */
    public function build()
    {
        /** @var ManagerRegistry $phpcr */
        $phpcr = $this->container->get('doctrine_phpcr');

        /** @var Session $session */
        $session = $phpcr->getConnection();

        // create the workspace
        try {
            $session->getWorkspace()->createWorkspace($this->container->getParameter('phpcr_workspace'));
        } catch (RepositoryException $e) {
            // do nothing
        }

        // Reinitialize the PHPCR repository
        $this->execCommand('Initializing PHPCR repository (idempotent)', 'doctrine:phpcr:repository:init');

        // Drop existing data if this is a destroying invocation
        if ($this->input->getOption('destroy')) {
            $session = $phpcr->getConnection();
            $root = $session->getRootNode();

            if ($root->hasNode('cmf')) {
                $this->output->writeln('<info>Removing /cmf node</info>');
                $root->getNode('cmf')->remove();
                $session->save();
            }
        }

        // Initialize Sulu node types
        $this->execCommand('Initializing Sulu Node Types', 'sulu:phpcr:init');

        // Initialize the Sulu webspaces
        $this->execCommand('Initializing Sulu Webspaces', 'sulu:webspace:init');
    }
}
