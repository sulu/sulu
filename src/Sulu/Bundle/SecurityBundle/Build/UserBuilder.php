<?php

/*
 * This file is part of Sulu.
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
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'user';
    }

    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return ['fixtures', 'database'];
    }

    /**
     * {@inheritdoc}
     */
    public function build()
    {
        $user = 'admin';
        $password = 'admin';
        $roleName = 'User';
        $system = 'Sulu';
        $locale = 'de';
        $doctrine = $this->container->get('doctrine')->getManager();
        $userRep = $this->container->get('sulu.repository.user');
        $userLocales = $this->container->getParameter('sulu_core.locales');

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
            [
                'name' => $roleName,
                'system' => $system,
        ]);
        $this->output->writeln(
            sprintf('Created role "<comment>%s</comment>" in system "<comment>%s</comment>"', $roleName, $system)
        );

        // locale choosen doesn't exist, fallback
        if (!in_array($locale, $userLocales)) {
            $locale = array_shift($userLocales);
        }

        $this->execCommand(
            'Creating user: ' . $user,
            'sulu:security:user:create',
            [
                'username' => $user,
                'firstName' => 'Adam',
                'lastName' => 'Ministrator',
                'email' => 'admin@example.com',
                'locale' => $locale,
                'role' => $roleName,
                'password' => $password,
            ]
        );
        $this->output->writeln(
            sprintf('Created user "<comment>%s</comment>" with password "<comment>%s</comment>"', $user, $password)
        );
    }
}
