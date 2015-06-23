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

use Sulu\Bundle\CoreBundle\Build\SuluBuilder;

/**
 * Builder for creating users.
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
        $roleName = 'User';
        $system = 'Sulu';
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

        $this->execCommand(
            'Creating role: ' . $roleName,
            'sulu:security:role:create',
            array(
                'name' => $roleName,
                'system' => $system,
        ));
        $this->output->writeln(
            sprintf('Created role "<comment>%s</comment>" in system "<comment>%s</comment>"', $roleName, $system)
        );

        $this->execCommand(
            'Creating user: ' . $user,
            'sulu:security:user:create',
            array(
                'username' => $user,
                'firstName' => 'Adam',
                'lastName' => 'Ministrator',
                'email' => 'admin@example.com',
                'locale' => 'de',
                'role' => $roleName,
                'password' => $password,
            )
        );
        $this->output->writeln(
            sprintf('Created user "<comment>%s</comment>" with password "<comment>%s</comment>"', $user, $password)
        );
    }
}
