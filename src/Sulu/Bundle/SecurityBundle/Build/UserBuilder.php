<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\SecurityBundle\Build;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Sulu\Bundle\CoreBundle\Build\SuluBuilder;

/**
 * Builder for creating users
 */
class UserBuilder extends SuluBuilder
{
    /**
     * {@inheritDoc}
     */
    public function getName()
    {
        return 'user';
    }

    /**
     * {@inheritDoc}
     */
    public function getDependencies()
    {
        return array('fixtures', 'database');
    }

    /**
     * {@inheritDoc}
     */
    public function build()
    {
        $user = 'admin';
        $password = 'admin';
        $doctrine = $this->container->get('doctrine')->getManager();
        $userRep = $doctrine->getRepository('SuluSecurityBundle:User');

        $existing = $userRep->findOneByUsername($user);

        if ($existing && $this->input->getOption('destroy')) {
            $this->output->writeln('Found existing user ' . $user . ' and destroy has been specified, removing');
            $doctrine->remove($existing);
            $doctrine->flush();
        } elseif ($existing) {
            $this->output->writeln('Found existing user ' . $user . ', skipping');
            return;
        }

        $this->execCommand('Creating user: ' . $user, 'sulu:security:user:create', array(
            'username' => $user,
            'firstName' => 'Adam',
            'lastName' => 'Ministrator',
            'email' => 'admin@example.com',
            'locale' => 'de',
            'password' => $password,
        ));

        $this->output->writeln(sprintf('Created user "<comment>%s</comment>" with password "<comment>%s</comment>"', $user, $password));
    }
}

