<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\SecurityBundle\Build;

use Sulu\Bundle\AdminBundle\Admin\Admin;
use Sulu\Bundle\CoreBundle\Build\SuluBuilder;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Question\Question;

/**
 * Builder for creating users.
 */
class UserBuilder extends SuluBuilder
{
    public function getName()
    {
        return 'user';
    }

    public function getDependencies()
    {
        return ['fixtures', 'database'];
    }

    public function build()
    {
        $user = 'admin';
        $roleName = 'User';
        $system = Admin::SULU_ADMIN_SECURITY_SYSTEM;
        $locale = 'en';
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

        // Running after the admin user check, otherwise we don't need the password.
        $helper = new QuestionHelper();
        $question = new Question("Please provide an admin password (default: admin)\n");

        /** @var string $password */
        $password = $helper->ask($this->input, $this->output, $question) ?: 'admin';

        $this->execCommand(
            'Creating role: ' . $roleName,
            'sulu:security:role:create',
            [
                'name' => $roleName,
                'system' => $system,
            ]
        );
        $this->output->writeln(
            \sprintf('Created role "<comment>%s</comment>" in system "<comment>%s</comment>"', $roleName, $system)
        );

        // locale chosen doesn't exist, fallback
        if (!\in_array($locale, $userLocales)) {
            $locale = \array_shift($userLocales);
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
            \sprintf('Created user "<comment>%s</comment>" with password "<comment>%s</comment>"', $user, $password)
        );
    }
}
